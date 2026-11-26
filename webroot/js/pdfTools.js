/**
 * Merging and splitting of pdf files in the browser with pdf-lib, so the files stay on the computer.
 *
 * The server (Ghostscript) is used only for what pdf-lib cannot do: compression and PDF/A when merging, and
 * encrypted files, which pdf-lib cannot decrypt; for the latter the user is asked first.
 */
(function () {
    const PDF_TYPE = { description: 'PDF', accept: { 'application/pdf': ['.pdf'] } };
    const ZIP_TYPE = { description: 'ZIP', accept: { 'application/zip': ['.zip'] } };

    // ---- shared helpers -----------------------------------------------------------------------------------------

    function isPdf(file) {
        return file && (file.type === 'application/pdf' || /\.pdf$/i.test(file.name));
    }

    function isAbort(e) {
        return e && e.name === 'AbortError';
    }

    function baseName(name) {
        return String(name || 'document').replace(/\.pdf$/i, '');
    }

    function formatSize(size) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let i = 0;
        let value = size;
        while (value >= 1024 && i < units.length - 1) {
            value /= 1024;
            i++;
        }

        return (i === 0 ? value : value.toFixed(1)) + ' ' + units[i];
    }

    /**
     * Reads a pdf with pdf-lib. Returns { doc, pages } or { encrypted: true } for a file pdf-lib cannot decrypt;
     * anything that is not a readable pdf throws.
     */
    async function readPdf(bytes) {
        // the error pdf-lib throws for encrypted files does not survive the es5 build as a class that
        // instanceof could test, so the file is always loaded and the flag is read afterwards; the page
        // count is readable either way, only the content streams of an encrypted file are unusable
        const doc = await PDFLib.PDFDocument.load(bytes, { updateMetadata: false, ignoreEncryption: true });
        const encrypted = doc.isEncrypted;

        return { doc: encrypted ? null : doc, pages: doc.getPageCount(), encrypted };
    }

    function download(blob, name) {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 10000);
    }

    async function writeHandle(handle, blob) {
        // createWritable() writes to a temporary file and swaps it in on close(), so an interrupted
        // write cannot leave the target truncated
        const stream = await handle.createWritable();
        try {
            await stream.write(blob);
        } catch (e) {
            try {
                await stream.abort();
            } catch (abortError) {
                console.error(abortError);
            }

            throw e;
        }
        await stream.close();
    }

    /**
     * Hands a finished file to the user through the save dialog, or as a plain download where the File System
     * Access api is missing or the dialog refuses the location. Returns false when the user cancelled the dialog.
     */
    async function saveFile(blob, name, type, messages) {
        if (typeof window.showSaveFilePicker !== 'function') {
            download(blob, name);

            return true;
        }

        let handle;
        try {
            handle = await window.showSaveFilePicker({ suggestedName: name, types: [type] });
        } catch (e) {
            if (isAbort(e)) {
                return false;
            }
            console.error(e);
            alert(messages.saveFallback);
            download(blob, name);

            return true;
        }

        try {
            await writeHandle(handle, blob);
        } catch (e) {
            console.error(e);
            alert(messages.saveError);
            download(blob, name);
        }

        return true;
    }

    function csrfToken() {
        const meta = document.querySelector('meta[name="csrfToken"]');

        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Posts form data to the server with an upload progress bar. Resolves with the XMLHttpRequest once the
     * response is in; any status other than 2xx rejects.
     */
    function upload(url, formData, responseType, progressBar) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            xhr.responseType = responseType;
            xhr.setRequestHeader('X-CSRF-Token', csrfToken());
            if (progressBar) {
                progressBar.style.width = '0%';
                xhr.upload.addEventListener('progress', (ev) => {
                    if (ev.lengthComputable) {
                        progressBar.style.width = Math.round(ev.loaded / ev.total * 100) + '%';
                    }
                });
            }
            xhr.onload = () => (xhr.status >= 200 && xhr.status < 300 ? resolve(xhr) : reject(new Error('HTTP ' + xhr.status)));
            xhr.onerror = () => reject(new Error('network error'));
            try {
                xhr.send(formData);
            } catch (e) {
                reject(e);
            }
        });
    }

    function setBusy(button, busy) {
        button.classList.toggle('disabled', busy);
        button.setAttribute('aria-busy', busy ? 'true' : 'false');
        const spinner = button.querySelector('.preloader-wrapper');
        const caption = button.querySelector('.pdft-caption');
        if (spinner) {
            spinner.style.display = busy ? '' : 'none';
            spinner.classList.toggle('active', busy);
        }
        if (caption) {
            caption.style.display = busy ? 'none' : '';
        }
    }

    /**
     * Wires a drop zone and a file input to a callback that receives the chosen files. A file dropped next to
     * the zone would navigate away from the page, so the document swallows drops.
     */
    function bindFileSource(zone, input, onFiles) {
        zone.addEventListener('click', (ev) => {
            if (ev.target.closest('.pdft-item, button, input, label')) {
                return;
            }
            input.click();
        });
        zone.addEventListener('dragover', (ev) => {
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'copy';
            zone.classList.add('drag-over');
        });
        zone.addEventListener('dragleave', (ev) => {
            if (!zone.contains(ev.relatedTarget)) {
                zone.classList.remove('drag-over');
            }
        });
        zone.addEventListener('drop', (ev) => {
            ev.preventDefault();
            zone.classList.remove('drag-over');
            // sorting the list with jQuery UI is a drag as well, but without files
            if (ev.dataTransfer.files.length) {
                onFiles(Array.from(ev.dataTransfer.files));
            }
        });
        input.addEventListener('change', () => {
            const files = Array.from(input.files);
            input.value = '';
            if (files.length) {
                onFiles(files);
            }
        });

        document.addEventListener('dragover', (ev) => ev.preventDefault());
        document.addEventListener('drop', (ev) => ev.preventDefault());
    }

    // ---- zip ----------------------------------------------------------------------------------------------------
    //
    // Pdf files are compressed already, so the archive only stores them (method 0); that keeps the writer small.

    const CRC_TABLE = (() => {
        const table = new Uint32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) {
                c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
            }
            table[n] = c >>> 0;
        }

        return table;
    })();

    function crc32(bytes) {
        let c = 0xffffffff;
        for (let i = 0; i < bytes.length; i++) {
            c = CRC_TABLE[(c ^ bytes[i]) & 0xff] ^ (c >>> 8);
        }

        return (c ^ 0xffffffff) >>> 0;
    }

    /**
     * Builds a zip archive from [{ name, data: Uint8Array }]. Names are written as UTF-8 (flag bit 11).
     */
    function makeZip(entries) {
        const encoder = new TextEncoder();
        const now = new Date();
        const time = (now.getHours() << 11) | (now.getMinutes() << 5) | (now.getSeconds() >> 1);
        const date = ((Math.max(1980, now.getFullYear()) - 1980) << 9) | ((now.getMonth() + 1) << 5) | now.getDate();
        const UTF8 = 0x0800;

        const parts = [];
        const central = [];
        let offset = 0;
        let centralSize = 0;

        entries.forEach((entry) => {
            const name = encoder.encode(entry.name);
            const size = entry.data.length;
            const crc = crc32(entry.data);

            const local = new DataView(new ArrayBuffer(30));
            local.setUint32(0, 0x04034b50, true);
            local.setUint16(4, 20, true);
            local.setUint16(6, UTF8, true);
            local.setUint16(8, 0, true);
            local.setUint16(10, time, true);
            local.setUint16(12, date, true);
            local.setUint32(14, crc, true);
            local.setUint32(18, size, true);
            local.setUint32(22, size, true);
            local.setUint16(26, name.length, true);
            local.setUint16(28, 0, true);
            parts.push(new Uint8Array(local.buffer), name, entry.data);

            const header = new DataView(new ArrayBuffer(46));
            header.setUint32(0, 0x02014b50, true);
            header.setUint16(4, 20, true);
            header.setUint16(6, 20, true);
            header.setUint16(8, UTF8, true);
            header.setUint16(10, 0, true);
            header.setUint16(12, time, true);
            header.setUint16(14, date, true);
            header.setUint32(16, crc, true);
            header.setUint32(20, size, true);
            header.setUint32(24, size, true);
            header.setUint16(28, name.length, true);
            header.setUint16(30, 0, true);
            header.setUint16(32, 0, true);
            header.setUint16(34, 0, true);
            header.setUint16(36, 0, true);
            header.setUint32(38, 0, true);
            header.setUint32(42, offset, true);
            central.push(new Uint8Array(header.buffer), name);

            offset += 30 + name.length + size;
            centralSize += 46 + name.length;
        });

        const end = new DataView(new ArrayBuffer(22));
        end.setUint32(0, 0x06054b50, true);
        end.setUint16(4, 0, true);
        end.setUint16(6, 0, true);
        end.setUint16(8, entries.length, true);
        end.setUint16(10, entries.length, true);
        end.setUint32(12, centralSize, true);
        end.setUint32(16, offset, true);
        end.setUint16(20, 0, true);

        return new Blob([...parts, ...central, new Uint8Array(end.buffer)], { type: 'application/zip' });
    }

    // ---- merge --------------------------------------------------------------------------------------------------

    function initMerge(root) {
        const messages = JSON.parse(root.dataset.messages || '{}');
        const zone = root.querySelector('.pdft-drop');
        const input = root.querySelector('.pdft-file');
        const list = root.querySelector('.pdft-list');
        const empty = root.querySelector('.pdft-empty');
        const filename = root.querySelector('.pdft-filename');
        const compression = root.querySelector('.pdft-compression');
        const pdfa = root.querySelector('.pdft-pdfa');
        const submit = root.querySelector('.pdft-submit');
        const progress = root.querySelector('.pdft-progress .determinate');

        let busy = false;

        function refresh() {
            empty.hidden = list.children.length > 0;
        }

        async function addFile(file) {
            if (!isPdf(file)) {
                alert(messages.notPdf + '\n' + file.name);

                return;
            }

            let info;
            try {
                info = await readPdf(new Uint8Array(await file.arrayBuffer()));
            } catch (e) {
                console.error(e);
                alert(messages.loadError + '\n' + file.name);

                return;
            }

            const item = document.createElement('div');
            item.className = 'pdft-item upload-thumbnail row';
            item.pdftFile = file;
            item.pdftEncrypted = info.encrypted;

            const cells = [
                ['name col s6 truncate', file.name],
                ['col s2 truncate', info.pages + ' ' + messages.pagesShort],
                ['col s1 truncate', formatSize(file.size)],
                ['col s2 truncate', new Date(file.lastModified).toLocaleString()],
            ];
            cells.forEach(([cls, text]) => {
                const span = document.createElement('span');
                span.className = cls;
                span.textContent = text;
                item.appendChild(span);
            });
            if (info.encrypted) {
                item.classList.add('pdft-encrypted');
                item.title = messages.encryptedHint;
                item.querySelector('.name').textContent = '\u{1F512} ' + file.name;
            }

            const cell = document.createElement('span');
            cell.className = 'remove col s1';
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'btn-small filled remove-item';
            remove.innerHTML = '<i class="material-icons">delete</i>';
            remove.addEventListener('click', (ev) => {
                ev.stopPropagation();
                item.remove();
                refresh();
            });
            cell.appendChild(remove);
            item.appendChild(cell);

            list.appendChild(item);
            refresh();
        }

        async function addFiles(files) {
            // one after another, so the list keeps the order the files were chosen in
            for (const file of files) {
                await addFile(file);
            }
        }

        function items() {
            return Array.from(list.querySelectorAll('.pdft-item'));
        }

        async function mergeInBrowser(files) {
            const { PDFDocument } = PDFLib;
            const out = await PDFDocument.create();
            for (const file of files) {
                const src = await PDFDocument.load(new Uint8Array(await file.arrayBuffer()), { updateMetadata: false });
                const pages = await out.copyPages(src, src.getPageIndices());
                pages.forEach((page) => out.addPage(page));
            }

            return out.save();
        }

        async function mergeOnServer(files, name) {
            const fd = new FormData();
            files.forEach((file, i) => fd.append('file[' + i + ']', file));
            fd.append('filename', name);
            fd.append('pdfa', pdfa.checked ? '1' : '');
            fd.append('compression', compression.value);

            const xhr = await upload(root.dataset.url, fd, 'json', progress);
            const response = xhr.response;
            if (!response || !response.filename) {
                throw new Error('unexpected response');
            }
            // the ".pdf" extension makes Pages::pdf() send the merged file as a download
            document.location.assign(root.dataset.viewer + '/' + encodeURIComponent(response.filename));
        }

        async function run() {
            if (busy) {
                return;
            }
            const all = items();
            if (!all.length) {
                alert(messages.noFiles);

                return;
            }
            let name = filename.value.trim();
            if (!name) {
                alert(messages.noFilename);

                return;
            }
            if (!/\.pdf$/i.test(name)) {
                name += '.pdf';
                filename.value = name;
            }

            const files = all.map((item) => item.pdftFile);
            const needsServer = compression.value !== 'default' || pdfa.checked;
            const encrypted = all.some((item) => item.pdftEncrypted);
            if (!needsServer && encrypted && !confirm(messages.encryptedMerge)) {
                return;
            }

            busy = true;
            setBusy(submit, true);
            try {
                if (needsServer || encrypted) {
                    await mergeOnServer(files, name);
                } else {
                    const bytes = await mergeInBrowser(files);
                    await saveFile(new Blob([bytes], { type: 'application/pdf' }), name, PDF_TYPE, messages);
                }
            } catch (e) {
                console.error(e);
                alert(messages.processError);
            } finally {
                busy = false;
                setBusy(submit, false);
                if (progress) {
                    progress.style.width = '0%';
                }
            }
        }

        bindFileSource(zone, input, addFiles);
        submit.addEventListener('click', (ev) => {
            ev.preventDefault();
            run();
        });

        if (window.jQuery && jQuery.fn.sortable) {
            jQuery(list).sortable({ items: '> .pdft-item' });
        }
        refresh();
    }

    // ---- split --------------------------------------------------------------------------------------------------

    /**
     * Parses a page selection the way Acrobat takes it: "1,2,3-5,15-20". A range may be open on either side
     * ("15-" to the last page, "-3" from the first one), spaces are ignored and an empty text means every page.
     *
     * Returns the selected page numbers in document order without repeats and the same selection written
     * compactly ("1-5,15-20"), or null when the text is not valid for a document with pageCount pages.
     */
    function parsePageRanges(text, pageCount) {
        const parts = String(text).replace(/\s+/g, '').split(/[,;]/).filter((part) => part !== '');
        if (!parts.length) {
            parts.push('1-' + pageCount);
        }

        const selected = new Set();
        for (const part of parts) {
            const match = /^(\d*)(?:(-)(\d*))?$/.exec(part);
            if (!match || (match[1] === '' && (!match[2] || match[3] === ''))) {
                return null;
            }
            const from = match[1] === '' ? 1 : parseInt(match[1], 10);
            const to = !match[2] ? from : (match[3] === '' ? pageCount : parseInt(match[3], 10));
            if (from < 1 || to < from || to > pageCount) {
                return null;
            }
            for (let p = from; p <= to; p++) {
                selected.add(p);
            }
        }

        const pages = Array.from(selected).sort((a, b) => a - b);
        const runs = [];
        pages.forEach((p, i) => {
            if (i > 0 && p === pages[i - 1] + 1) {
                runs[runs.length - 1][1] = p;
            } else {
                runs.push([p, p]);
            }
        });

        return {
            pages,
            canonical: runs.map(([a, b]) => (a === b ? String(a) : a + '-' + b)).join(','),
        };
    }

    function initSplit(root) {
        const messages = JSON.parse(root.dataset.messages || '{}');
        const zone = root.querySelector('.pdft-drop');
        const input = root.querySelector('.pdft-file');
        const info = root.querySelector('.pdft-info');
        const options = root.querySelector('.pdft-options');
        const rangeInput = root.querySelector('.pdft-pages');
        const multi = root.querySelector('.pdft-multi');
        const submit = root.querySelector('.pdft-submit');
        const progress = root.querySelector('.pdft-progress .determinate');

        let current = null;
        let busy = false;

        async function openFile(files) {
            if (files.length !== 1 || !isPdf(files[0])) {
                alert(messages.notSinglePdf);

                return;
            }
            const file = files[0];
            let read;
            try {
                read = await readPdf(new Uint8Array(await file.arrayBuffer()));
            } catch (e) {
                console.error(e);
                alert(messages.loadError + '\n' + file.name);

                return;
            }

            current = { file, doc: read.doc, pages: read.pages, encrypted: read.encrypted };
            info.textContent = file.name + ' — ' + read.pages + ' ' + messages.pagesShort +
                (read.encrypted ? ' — ' + messages.encryptedHint : '');
            info.hidden = false;
            options.hidden = false;
            rangeInput.focus();
        }

        function pad(n) {
            return String(n).padStart(3, '0');
        }

        async function extract(src, indices) {
            const out = await PDFLib.PDFDocument.create();
            const pages = await out.copyPages(src, indices);
            pages.forEach((page) => out.addPage(page));

            return out.save();
        }

        function singleName(selection) {
            const base = baseName(current.file.name);
            if (selection.pages.length === 1) {
                return base + '_' + pad(selection.pages[0]) + '.pdf';
            }
            // a long selection would make an unwieldy file name
            const suffix = selection.canonical.length <= 40 ? selection.canonical : 'izrez';

            return base + '_' + suffix + '.pdf';
        }

        async function splitInBrowser(selection) {
            const base = baseName(current.file.name);
            if (!multi.checked) {
                const bytes = await extract(current.doc, selection.pages.map((p) => p - 1));
                await saveFile(new Blob([bytes], { type: 'application/pdf' }), singleName(selection), PDF_TYPE, messages);

                return;
            }

            const entries = [];
            for (const p of selection.pages) {
                entries.push({ name: base + '_' + pad(p) + '.pdf', data: await extract(current.doc, [p - 1]) });
            }
            await saveFile(makeZip(entries), base + '.zip', ZIP_TYPE, messages);
        }

        async function splitOnServer(selection) {
            const fd = new FormData();
            fd.append('file', current.file);
            fd.append('pages', selection.canonical);
            fd.append('multiPage', multi.checked ? '1' : '0');

            const xhr = await upload(root.dataset.url, fd, 'blob', progress);
            const disposition = xhr.getResponseHeader('Content-Disposition') || '';
            const match = /filename="?([^";]+)"?/i.exec(disposition);
            const isZip = multi.checked;
            const name = match ? match[1] : baseName(current.file.name) + (isZip ? '.zip' : '.pdf');
            await saveFile(xhr.response, name, isZip ? ZIP_TYPE : PDF_TYPE, messages);
        }

        async function run() {
            if (busy) {
                return;
            }
            if (!current) {
                alert(messages.noFiles);

                return;
            }
            const selection = parsePageRanges(rangeInput.value, current.pages);
            if (!selection) {
                alert(messages.badRange.replace('{0}', current.pages));
                rangeInput.focus();

                return;
            }
            if (current.encrypted && !confirm(messages.encryptedSplit)) {
                return;
            }

            busy = true;
            setBusy(submit, true);
            try {
                if (current.encrypted) {
                    await splitOnServer(selection);
                } else {
                    await splitInBrowser(selection);
                }
            } catch (e) {
                console.error(e);
                alert(messages.processError);
            } finally {
                busy = false;
                setBusy(submit, false);
                if (progress) {
                    progress.style.width = '0%';
                }
            }
        }

        bindFileSource(zone, input, openFile);
        submit.addEventListener('click', (ev) => {
            ev.preventDefault();
            run();
        });
        rangeInput.addEventListener('keydown', (ev) => {
            if (ev.key === 'Enter') {
                ev.preventDefault();
                run();
            }
        });
    }

    function init() {
        const merge = document.getElementById('pdf-merge');
        if (merge) {
            initMerge(merge);
        }
        const split = document.getElementById('pdf-split');
        if (split) {
            initSplit(split);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

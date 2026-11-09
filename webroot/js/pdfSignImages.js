/**
 * Places signature images on pages of a pdf. Rendering is done by pdf.js, the images are embedded with pdf-lib,
 * so the pdf never leaves the browser.
 *
 * Signature rectangles are kept in "page units": points of the page as it is displayed (rotation included),
 * origin in the top left corner. The on-screen size is page units multiplied by the current zoom.
 */
function initPdfSignImages() {
    const root = document.getElementById('pdf-sign-images');
    if (!root) {
        return;
    }

    const messages = JSON.parse(root.dataset.messages || '{}');
    const $ = (id) => document.getElementById(id);

    const els = {
        upload: root.querySelector('.psi-upload'),
        editor: root.querySelector('.psi-editor'),
        pdfFile: $('psi-pdf-file'),
        imageFile: $('psi-image-file'),
        viewport: $('psi-viewport'),
        stage: $('psi-stage'),
        canvas: $('psi-canvas'),
        pageInfo: $('psi-page-info'),
        prev: $('psi-prev'),
        next: $('psi-next'),
        add: $('psi-add'),
        remove: $('psi-remove'),
        save: $('psi-save'),
        sign: $('psi-sign'),
    };

    const MIN_SIZE = 12;

    let pdfjsLib = null;
    let pdfDoc = null;
    let pdfBytes = null;
    let filename = 'document.pdf';
    let pageNum = 1;
    let scale = 1;
    let pageSizes = {};
    let signatures = [];
    let selected = null;
    let renderToken = 0;
    let renderTask = null;
    let nextId = 1;

    async function loadPdfjs() {
        if (!pdfjsLib) {
            pdfjsLib = await import(root.dataset.pdfjs);
            pdfjsLib.GlobalWorkerOptions.workerSrc = root.dataset.worker;
        }

        return pdfjsLib;
    }

    async function openPdf(file) {
        try {
            const lib = await loadPdfjs();
            const bytes = new Uint8Array(await file.arrayBuffer());
            // pdf.js takes over the buffer it gets, so it receives a copy
            const doc = await lib.getDocument({ data: bytes.slice() }).promise;

            pdfBytes = bytes;
            pdfDoc = doc;
            filename = file.name;
        } catch (e) {
            console.error(e);
            alert(messages.loadError);
            els.pdfFile.value = '';

            return;
        }

        pageNum = 1;
        pageSizes = {};
        signatures = [];
        selected = null;
        els.upload.hidden = true;
        els.editor.hidden = false;

        await goToPage(1, true);
        updateButtons();
    }

    async function pageSize(num) {
        if (!pageSizes[num]) {
            const page = await pdfDoc.getPage(num);
            const vp = page.getViewport({ scale: 1 });
            pageSizes[num] = { w: vp.width, h: vp.height };
        }

        return pageSizes[num];
    }

    async function goToPage(num, fit) {
        if (num < 1 || num > pdfDoc.numPages) {
            return;
        }
        pageNum = num;
        selected = null;
        await pageSize(num);
        updateButtons();
        if (fit) {
            await setScale(fitScale(false));
        } else {
            await setScale(scale);
        }
        updateButtons();
    }

    function fitScale(widthOnly) {
        const size = pageSizes[pageNum];
        const cs = getComputedStyle(els.viewport);
        const w = els.viewport.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
        const h = els.viewport.clientHeight - parseFloat(cs.paddingTop) - parseFloat(cs.paddingBottom);
        const byWidth = w / size.w;

        return Math.max(0.1, widthOnly ? byWidth : Math.min(byWidth, h / size.h));
    }

    async function setScale(value) {
        scale = Math.min(8, Math.max(0.1, value));
        const size = pageSizes[pageNum];
        els.stage.style.width = size.w * scale + 'px';
        els.stage.style.height = size.h * scale + 'px';
        renderOverlays();
        await renderCanvas();
    }

    async function renderCanvas() {
        const token = ++renderToken;
        if (renderTask) {
            renderTask.cancel();
            try {
                await renderTask.promise;
            } catch (e) {
                // cancelled
            }
        }
        if (token !== renderToken) {
            return;
        }

        const page = await pdfDoc.getPage(pageNum);
        const dpr = window.devicePixelRatio || 1;
        const vp = page.getViewport({ scale: scale * dpr });
        els.canvas.width = Math.floor(vp.width);
        els.canvas.height = Math.floor(vp.height);

        renderTask = page.render({ canvasContext: els.canvas.getContext('2d'), viewport: vp });
        try {
            await renderTask.promise;
        } catch (e) {
            if (e && e.name !== 'RenderingCancelledException') {
                console.error(e);
            }
        }
    }

    function updateButtons() {
        els.pageInfo.textContent = pageNum + ' / ' + (pdfDoc ? pdfDoc.numPages : 1);
        els.prev.disabled = !pdfDoc || pageNum <= 1;
        els.next.disabled = !pdfDoc || pageNum >= pdfDoc.numPages;
        els.remove.disabled = selected === null;
        els.save.disabled = signatures.length === 0;
    }

    // ---- signature overlays -------------------------------------------------------------------------------------

    function placeElement(sig) {
        const st = sig.el.style;
        st.left = sig.x * scale + 'px';
        st.top = sig.y * scale + 'px';
        st.width = sig.w * scale + 'px';
        st.height = sig.h * scale + 'px';
    }

    function renderOverlays() {
        els.stage.querySelectorAll('.psi-sig').forEach((el) => el.remove());

        signatures.filter((s) => s.page === pageNum).forEach((sig) => {
            const el = document.createElement('div');
            el.className = 'psi-sig' + (sig === selected ? ' selected' : '');
            const img = document.createElement('img');
            img.src = sig.url;
            img.draggable = false;
            el.appendChild(img);
            ['nw', 'ne', 'sw', 'se'].forEach((corner) => {
                const handle = document.createElement('div');
                handle.className = 'psi-handle ' + corner;
                handle.dataset.corner = corner;
                el.appendChild(handle);
            });

            sig.el = el;
            placeElement(sig);
            el.addEventListener('pointerdown', (ev) => startInteraction(ev, sig));
            els.stage.appendChild(el);
        });
    }

    function select(sig) {
        selected = sig;
        els.stage.querySelectorAll('.psi-sig').forEach((el) => el.classList.remove('selected'));
        if (sig && sig.el) {
            sig.el.classList.add('selected');
        }
        updateButtons();
    }

    function startInteraction(ev, sig) {
        if (ev.button !== undefined && ev.button !== 0) {
            return;
        }
        ev.preventDefault();
        ev.stopPropagation();
        select(sig);

        const corner = ev.target.dataset ? ev.target.dataset.corner : undefined;
        const size = pageSizes[pageNum];
        const start = { px: ev.clientX, py: ev.clientY, x: sig.x, y: sig.y, w: sig.w, h: sig.h };
        const ratio = sig.h / sig.w;
        // fixed point of a resize is the corner opposite to the dragged one
        const anchorX = corner && corner.includes('w') ? start.x + start.w : start.x;
        const anchorY = corner && corner.includes('n') ? start.y + start.h : start.y;

        sig.el.setPointerCapture(ev.pointerId);

        const move = (e) => {
            const dx = (e.clientX - start.px) / scale;
            const dy = (e.clientY - start.py) / scale;

            if (!corner) {
                sig.x = Math.min(Math.max(0, start.x + dx), size.w - sig.w);
                sig.y = Math.min(Math.max(0, start.y + dy), size.h - sig.h);
            } else {
                const horizontal = corner.includes('w') ? -1 : 1;
                // largest width that still keeps the image on the page
                const room = Math.min(
                    horizontal > 0 ? size.w - anchorX : anchorX,
                    (corner.includes('n') ? anchorY : size.h - anchorY) / ratio,
                );
                const w = Math.min(Math.max(MIN_SIZE, start.w + horizontal * dx), room);
                sig.w = w;
                sig.h = w * ratio;
                sig.x = corner.includes('w') ? anchorX - sig.w : anchorX;
                sig.y = corner.includes('n') ? anchorY - sig.h : anchorY;
            }
            placeElement(sig);
        };
        const end = () => {
            sig.el.removeEventListener('pointermove', move);
            sig.el.removeEventListener('pointerup', end);
            sig.el.removeEventListener('pointercancel', end);
        };
        sig.el.addEventListener('pointermove', move);
        sig.el.addEventListener('pointerup', end);
        sig.el.addEventListener('pointercancel', end);
    }

    // ---- adding signatures --------------------------------------------------------------------------------------

    function isPng(b) {
        return b.length > 4 && b[0] === 0x89 && b[1] === 0x50 && b[2] === 0x4e && b[3] === 0x47;
    }

    function isJpeg(b) {
        return b.length > 3 && b[0] === 0xff && b[1] === 0xd8;
    }

    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = url;
        });
    }

    async function addSignature(file) {
        try {
            let bytes = new Uint8Array(await file.arrayBuffer());
            let type = isPng(bytes) ? 'png' : (isJpeg(bytes) ? 'jpg' : null);
            let url = URL.createObjectURL(new Blob([bytes]));
            let img = await loadImage(url);

            if (!type) {
                // anything else the browser can display (gif, webp, svg...) is converted to png
                const canvas = document.createElement('canvas');
                canvas.width = img.naturalWidth || 300;
                canvas.height = img.naturalHeight || 100;
                canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
                const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
                bytes = new Uint8Array(await blob.arrayBuffer());
                type = 'png';
                url = URL.createObjectURL(blob);
                img = await loadImage(url);
            }

            const size = pageSizes[pageNum];
            const ratio = img.naturalHeight / img.naturalWidth;
            let w = size.w * 0.25;
            if (w * ratio > size.h * 0.5) {
                w = size.h * 0.5 / ratio;
            }
            const sig = {
                id: nextId++,
                page: pageNum,
                bytes,
                type,
                url,
                w,
                h: w * ratio,
                x: 0,
                y: 0,
            };
            sig.x = (size.w - sig.w) / 2;
            sig.y = (size.h - sig.h) / 2;

            signatures.push(sig);
            selected = sig;
            renderOverlays();
            updateButtons();
        } catch (e) {
            console.error(e);
            alert(messages.imageError);
        }
    }

    function removeSelected() {
        if (!selected) {
            return;
        }
        URL.revokeObjectURL(selected.url);
        signatures = signatures.filter((s) => s !== selected);
        selected = null;
        renderOverlays();
        updateButtons();
    }

    // ---- saving -------------------------------------------------------------------------------------------------

    // Draws the signature images on the pages and returns the pdf-lib document.
    async function buildDocument() {
        const { PDFDocument, degrees } = PDFLib;
        const doc = await PDFDocument.load(pdfBytes);
        const pages = doc.getPages();

        for (const sig of signatures) {
            const page = pages[sig.page - 1];
            const image = sig.type === 'png' ? await doc.embedPng(sig.bytes) : await doc.embedJpg(sig.bytes);
            const { width: W, height: H } = page.getSize();
            const rotation = (((page.getRotation().angle % 360) + 360) % 360);

            // the rectangle is in displayed coordinates, pdf-lib wants the unrotated page, bottom-left
            // corner of the image and a counter-clockwise rotation
            const opts = { width: sig.w, height: sig.h };
            if (rotation === 90) {
                Object.assign(opts, { x: sig.y + sig.h, y: sig.x, rotate: degrees(90) });
            } else if (rotation === 180) {
                Object.assign(opts, { x: W - sig.x, y: sig.y + sig.h, rotate: degrees(180) });
            } else if (rotation === 270) {
                Object.assign(opts, { x: W - sig.y - sig.h, y: H - sig.x, rotate: degrees(270) });
            } else {
                Object.assign(opts, { x: sig.x, y: H - sig.y - sig.h });
            }
            page.drawImage(image, opts);
        }

        return doc;
    }

    function download(bytes) {
        const link = document.createElement('a');
        link.href = URL.createObjectURL(new Blob([bytes], { type: 'application/pdf' }));
        link.download = filename.replace(/\.pdf$/i, '') + '_signed.pdf';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(link.href), 10000);
    }

    async function save() {
        els.save.disabled = true;
        try {
            const doc = await buildDocument();
            download(await doc.save());
        } catch (e) {
            console.error(e);
            alert(messages.saveError);
        }
        updateButtons();
    }

    // ---- certificate signature ----------------------------------------------------------------------------------
    //
    // The pdf gets an (invisible) signature field with a placeholder for the signature. The bytes of the file
    // without the placeholder are hashed, the hash of the CMS signed attributes is signed by arhint-signer (the
    // private key never leaves the Windows certificate store) and the finished CMS container replaces the placeholder.

    const SIG_BYTES = 8192;
    const BR_MARKER = '1111111111 2222222222 3333333333';
    const OID = {
        data: '1.2.840.113549.1.7.1',
        signedData: '1.2.840.113549.1.7.2',
        contentType: '1.2.840.113549.1.9.3',
        messageDigest: '1.2.840.113549.1.9.4',
        signingTime: '1.2.840.113549.1.9.5',
        sha256: '2.16.840.1.101.3.4.2.1',
        rsaEncryption: '1.2.840.113549.1.1.1',
        sha256WithRsa: '1.2.840.113549.1.1.11',
    };

    function concat(parts) {
        const out = new Uint8Array(parts.reduce((n, x) => n + x.length, 0));
        let pos = 0;
        parts.forEach((x) => {
            out.set(x, pos);
            pos += x.length;
        });

        return out;
    }

    function derLength(n) {
        if (n < 128) {
            return [n];
        }
        const bytes = [];
        for (let v = n; v > 0; v = Math.floor(v / 256)) {
            bytes.unshift(v % 256);
        }

        return [0x80 | bytes.length, ...bytes];
    }

    function tlv(tag, ...parts) {
        const body = concat(parts);

        return concat([Uint8Array.of(tag, ...derLength(body.length)), body]);
    }

    function oid(text) {
        const n = text.split('.').map(Number);
        const bytes = [40 * n[0] + n[1]];
        n.slice(2).forEach((v) => {
            const chunk = [v & 0x7f];
            for (v = Math.floor(v / 128); v > 0; v = Math.floor(v / 128)) {
                chunk.unshift((v & 0x7f) | 0x80);
            }
            bytes.push(...chunk);
        });

        return tlv(0x06, Uint8Array.from(bytes));
    }

    function algorithm(text) {
        return tlv(0x30, oid(text), Uint8Array.of(0x05, 0x00));
    }

    function readTlv(buf, pos) {
        const first = buf[pos + 1];
        let len = first;
        let hdr = 2;
        if (first >= 0x80) {
            const n = first & 0x7f;
            len = 0;
            for (let i = 0; i < n; i++) {
                len = len * 256 + buf[pos + 2 + i];
            }
            hdr = 2 + n;
        }

        return { tag: buf[pos], pos, content: pos + hdr, end: pos + hdr + len };
    }

    /**
     * Pulls out what the CMS needs from a DER certificate: the issuer and serial number elements and whether the
     * key is RSA (arhint-signer signs with RSA PKCS#1 v1.5 only).
     */
    function parseCertificate(der) {
        const cert = readTlv(der, 0);
        const tbs = readTlv(der, cert.content);
        let t = readTlv(der, tbs.content);
        if (t.tag === 0xa0) {
            t = readTlv(der, t.end);
        }
        const serial = t;
        const sigAlg = readTlv(der, serial.end);
        const issuer = readTlv(der, sigAlg.end);
        const validity = readTlv(der, issuer.end);
        const subject = readTlv(der, validity.end);
        const spki = readTlv(der, subject.end);
        const keyAlg = readTlv(der, readTlv(der, spki.content).content);
        const rsaOid = oid(OID.rsaEncryption);

        return {
            issuerAndSerial: tlv(0x30, der.subarray(issuer.pos, issuer.end), der.subarray(serial.pos, serial.end)),
            isRsa: rsaOid.every((b, i) => der[keyAlg.pos + i] === b),
        };
    }

    function utcTime(date) {
        const two = (n) => String(n).padStart(2, '0');
        const text = two(date.getUTCFullYear() % 100) + two(date.getUTCMonth() + 1) + two(date.getUTCDate()) +
            two(date.getUTCHours()) + two(date.getUTCMinutes()) + two(date.getUTCSeconds()) + 'Z';

        return tlv(0x17, new TextEncoder().encode(text));
    }

    function compareBytes(a, b) {
        for (let i = 0; i < Math.min(a.length, b.length); i++) {
            if (a[i] !== b[i]) {
                return a[i] - b[i];
            }
        }

        return a.length - b.length;
    }

    function fromBase64(text) {
        // arhint-signer leaves out the trailing "=" padding
        return Uint8Array.from(atob(text.padEnd(Math.ceil(text.length / 4) * 4, '=')), (c) => c.charCodeAt(0));
    }

    function toBase64(bytes) {
        let text = '';
        bytes.forEach((b) => {
            text += String.fromCharCode(b);
        });

        return btoa(text);
    }

    function toHex(bytes) {
        return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('').toUpperCase();
    }

    function indexOfText(buf, text) {
        const needle = Array.from(text, (c) => c.charCodeAt(0));
        for (let i = 0; i <= buf.length - needle.length; i++) {
            if (buf[i] === needle[0] && needle.every((b, j) => buf[i + j] === b)) {
                return i;
            }
        }

        return -1;
    }

    async function sha256(bytes) {
        return new Uint8Array(await crypto.subtle.digest('SHA-256', bytes));
    }

    // Adds an empty signature field with a placeholder for ByteRange and Contents to the document.
    function addSignaturePlaceholder(doc, certLabel) {
        const { PDFName, PDFString, PDFHexString, PDFDict, PDFArray, PDFNumber } = PDFLib;
        const ctx = doc.context;

        const sigRef = ctx.register(ctx.obj({
            Type: 'Sig',
            Filter: 'Adobe.PPKLite',
            SubFilter: 'adbe.pkcs7.detached',
            ByteRange: [0, 1111111111, 2222222222, 3333333333],
            Contents: PDFHexString.of('0'.repeat(SIG_BYTES * 2)),
            M: PDFString.fromDate(new Date()),
            Name: PDFHexString.fromText(certLabel),
        }));

        const firstPage = doc.getPages()[0];
        const widgetRef = ctx.register(ctx.obj({
            Type: 'Annot',
            Subtype: 'Widget',
            FT: 'Sig',
            Rect: [0, 0, 0, 0],
            V: sigRef,
            T: PDFString.of('Signature1'),
            F: 132,
            P: firstPage.ref,
        }));
        firstPage.node.addAnnot(widgetRef);

        let acroForm = doc.catalog.lookupMaybe(PDFName.of('AcroForm'), PDFDict);
        if (!acroForm) {
            acroForm = ctx.obj({ Fields: [] });
            doc.catalog.set(PDFName.of('AcroForm'), ctx.register(acroForm));
        }
        let fields = acroForm.lookupMaybe(PDFName.of('Fields'), PDFArray);
        if (!fields) {
            fields = ctx.obj([]);
            acroForm.set(PDFName.of('Fields'), fields);
        }
        fields.push(widgetRef);
        acroForm.set(PDFName.of('SigFlags'), PDFNumber.of(3));
    }

    async function serviceRequest(path, body) {
        const url = (root.dataset.signer || 'http://localhost:8082') + path;
        const response = await fetch(url, body === undefined ? {} : {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });
        const data = await response.json();
        if (data.error) {
            throw new Error(data.error);
        }

        return data.result;
    }

    function chooseCertificate(certs) {
        const dialog = $('psi-cert-dialog');
        const list = $('psi-cert-list');
        const confirmBtn = $('psi-cert-ok');
        let chosen = null;

        list.innerHTML = '';
        confirmBtn.disabled = true;
        certs.forEach((cert) => {
            const li = document.createElement('li');
            li.textContent = cert.label + (cert.notAfter ? ' (' + cert.notAfter + ')' : '');
            li.addEventListener('click', () => {
                list.querySelectorAll('li').forEach((x) => x.classList.remove('selected'));
                li.classList.add('selected');
                chosen = cert;
                confirmBtn.disabled = false;
            });
            list.appendChild(li);
        });

        return new Promise((resolve) => {
            const finish = (value) => {
                confirmBtn.onclick = null;
                $('psi-cert-cancel').onclick = null;
                dialog.onclose = null;
                if (dialog.open) {
                    dialog.close();
                }
                resolve(value);
            };
            confirmBtn.onclick = () => finish(chosen);
            $('psi-cert-cancel').onclick = () => finish(null);
            dialog.onclose = () => finish(null);
            dialog.showModal();
        });
    }

    async function signWithCertificate() {
        els.sign.disabled = true;
        try {
            let certs;
            try {
                certs = (await serviceRequest('/listCerts')) || [];
            } catch (e) {
                console.error(e);
                alert(messages.signerDown);

                return;
            }
            certs = certs.filter((c) => c.cert);
            if (!certs.length) {
                alert(messages.noCertificates);

                return;
            }

            const cert = await chooseCertificate(certs);
            if (!cert) {
                return;
            }
            const certDer = fromBase64(cert.cert);
            // older arhint-signer builds cut the last character of every base64 value
            if (certDer.length < 4 || readTlv(certDer, 0).end !== certDer.length) {
                alert(messages.signerOutdated);

                return;
            }
            const parsed = parseCertificate(certDer);
            if (!parsed.isRsa) {
                alert(messages.rsaOnly);

                return;
            }

            const doc = await buildDocument();
            addSignaturePlaceholder(doc, cert.label);
            const bytes = await doc.save({ useObjectStreams: false });

            // pdf-lib writes the array with its own spacing, so the whole "/ByteRange [...]" text is replaced
            const marker = indexOfText(bytes, BR_MARKER);
            const contentsAt = indexOfText(bytes, '/Contents <' + '0'.repeat(32));
            if (marker < 0 || contentsAt < 0) {
                throw new Error('signature placeholder not found');
            }
            let brAt = marker;
            while (brAt > 0 && indexOfText(bytes.subarray(brAt, brAt + 10), '/ByteRange') !== 0) {
                brAt--;
            }
            const brEnd = marker + bytes.subarray(marker).indexOf(0x5d) + 1;
            const open = contentsAt + '/Contents '.length;
            const close = open + 1 + SIG_BYTES * 2;
            const range = [0, open, close + 1, bytes.length - (close + 1)];

            const brText = ('/ByteRange [' + range.join(' ') + ']').padEnd(brEnd - brAt, ' ');
            new TextEncoder().encodeInto(brText, bytes.subarray(brAt));

            // hash of the whole file except the placeholder
            const digest = await sha256(concat([bytes.subarray(range[0], range[1]), bytes.subarray(range[2])]));

            const attrs = [
                tlv(0x30, oid(OID.contentType), tlv(0x31, oid(OID.data))),
                tlv(0x30, oid(OID.signingTime), tlv(0x31, utcTime(new Date()))),
                tlv(0x30, oid(OID.messageDigest), tlv(0x31, tlv(0x04, digest))),
            ].sort(compareBytes);
            // the signature covers the attributes encoded as a SET; in SignerInfo they are tagged [0]
            const attrsDigest = await sha256(tlv(0x31, ...attrs));
            const signature = fromBase64(await serviceRequest('/sign', {
                hash: toBase64(attrsDigest),
                thumbprint: cert.thumbprint,
            }));
            // RSA signatures are as long as the key: 1024, 1536, 2048, 3072 or 4096 bits
            if (![128, 192, 256, 384, 512].includes(signature.length)) {
                throw new Error(messages.signerOutdated);
            }

            const signerInfo = tlv(
                0x30,
                tlv(0x02, Uint8Array.of(1)),
                parsed.issuerAndSerial,
                algorithm(OID.sha256),
                tlv(0xa0, ...attrs),
                algorithm(OID.sha256WithRsa),
                tlv(0x04, signature),
            );
            const cms = tlv(
                0x30,
                oid(OID.signedData),
                tlv(
                    0xa0,
                    tlv(
                        0x30,
                        tlv(0x02, Uint8Array.of(1)),
                        tlv(0x31, algorithm(OID.sha256)),
                        tlv(0x30, oid(OID.data)),
                        tlv(0xa0, certDer),
                        tlv(0x31, signerInfo),
                    ),
                ),
            );
            if (cms.length > SIG_BYTES) {
                throw new Error('signature does not fit into the placeholder');
            }

            const hex = toHex(cms).padEnd(SIG_BYTES * 2, '0');
            new TextEncoder().encodeInto(hex, bytes.subarray(open + 1));

            download(bytes);
        } catch (e) {
            console.error(e);
            alert(messages.signError + '\n' + (e && e.message ? e.message : ''));
        } finally {
            els.sign.disabled = false;
            updateButtons();
        }
    }

    function reset() {
        if (signatures.length && !confirm(messages.discard)) {
            return;
        }
        signatures.forEach((s) => URL.revokeObjectURL(s.url));
        signatures = [];
        selected = null;
        renderToken++;
        if (renderTask) {
            renderTask.cancel();
        }
        if (pdfDoc) {
            pdfDoc.destroy();
        }
        pdfDoc = null;
        pdfBytes = null;
        els.stage.querySelectorAll('.psi-sig').forEach((el) => el.remove());
        els.pdfFile.value = '';
        els.editor.hidden = true;
        els.upload.hidden = false;
    }

    // ---- events -------------------------------------------------------------------------------------------------

    els.pdfFile.addEventListener('change', () => {
        if (els.pdfFile.files.length) {
            openPdf(els.pdfFile.files[0]);
        }
    });
    els.imageFile.addEventListener('change', () => {
        if (els.imageFile.files.length) {
            addSignature(els.imageFile.files[0]);
        }
        els.imageFile.value = '';
    });
    els.add.addEventListener('click', () => els.imageFile.click());
    els.remove.addEventListener('click', removeSelected);
    els.save.addEventListener('click', save);
    els.sign.addEventListener('click', signWithCertificate);
    $('psi-reset').addEventListener('click', reset);
    $('psi-zoom-in').addEventListener('click', () => setScale(scale * 1.25));
    $('psi-zoom-out').addEventListener('click', () => setScale(scale / 1.25));
    $('psi-zoom-extents').addEventListener('click', () => setScale(fitScale(false)));
    $('psi-zoom-width').addEventListener('click', () => setScale(fitScale(true)));
    els.prev.addEventListener('click', () => goToPage(pageNum - 1, false));
    els.next.addEventListener('click', () => goToPage(pageNum + 1, false));

    // click on the page itself deselects
    els.stage.addEventListener('pointerdown', (ev) => {
        if (ev.target === els.stage || ev.target === els.canvas) {
            select(null);
        }
    });

    document.addEventListener('keydown', (ev) => {
        if (selected && (ev.key === 'Delete' || ev.key === 'Backspace') && els.editor.hidden === false) {
            ev.preventDefault();
            removeSelected();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPdfSignImages);
} else {
    initPdfSignImages();
}

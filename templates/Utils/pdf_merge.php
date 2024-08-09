<?php
    use Cake\Routing\Router;

    $preloader = '<div id="spinner" class="preloader-wrapper small" style="display: none; width: 20px; height: 20px;">' .
        '<div class="spinner-layer spinner-red-only">' .
        '<div class="circle-clipper left">' .
        '<div class="circle"></div>' .
        '</div><div class="gap-patch">' .
        '<div class="circle"></div>' .
        '</div><div class="circle-clipper right">' .
        '<div class="circle"></div>' .
        '</div>' .
        '</div>' .
        '</div>';

    $uploadForm = [
        'title' => 'PDF Merge',
        'menu' => [
            'sign' => [
                'title' => __('PDF Sign'),
                'active' => $this->getRequest()->getParam('action') == 'pdfSign',
                'visible' => true,
                'url' => [
                    'action' => 'pdfSign',
                ],
            ],
            'merge' => [
                'title' => __('PDF Merge'),
                'active' => $this->getRequest()->getParam('action') == 'pdfMerge',
                'visible' => true,
                'url' => [
                    'action' => 'pdfMerge',
                ],
            ],
            'splice' => [
                'title' => __('PDF Splice'),
                'active' => $this->getRequest()->getParam('action') == 'pdfSplice',
                'visible' => true,
                'url' => [
                    'action' => 'pdfSplice',
                ],
            ],
        ],
        'form' => [
            'defaultHelper' => $this->Form,
            'lines' => [
                'file' => [
                    'method' => 'input',
                    'params' => ['file', [
                        'type' => 'file',
                        'id' => 'upload-file',
                        'accept' => 'application/pdf',
                    ]],
                ],
                'area' => sprintf('<div class="upload-area" id="uploadfile"><h1>%s</h1></div>', __('Drag and Drop file here<br/>Or<br/>Click to select file')),
                'progress' => '<div class="progress"><div class="determinate" style="width: 0%"></div></div>',
                'filename' => [
                    'method' => 'control',
                    'params' => ['filename', [
                        'label' => __('Filename') . ':',
                        'id' => 'filename',
                        'default' => 'download.pdf'
                    ]],
                ],
                'compression' => [
                    'method' => 'control',
                    'params' => ['compression', [
                        'type' => 'select',
                        'id' => 'compression',
                        'label' => __('Compression') . ':',
                        'value' => 'default',
                        'options' => [
                            'default' => __('Default'),
                            'printer ' => __('Printer (300 dpi)'),
                            'prepress' => __('Prepress (300 dpi)'),
                            'ebook ' => __('Ebook (150 dpi)'),
                            'screen' => __('Screen (72 dpi)'),
                        ],
                    ]],
                ],
                'pdfa' => [
                    'method' => 'control',
                    'params' => ['pdfa', [
                        'type' => 'checkbox',
                        'label' => __('Encode to PDF/A'),
                        'id' => 'pdfa',
                    ]],
                ],
                /*'submit' => [
                    'method' => 'button',
                    'params' => [__('Submit'), [
                        'type' => 'submit',
                        'id' => 'submit',
                    ]],
                ],*/
                '<a id="submit" class="btn"><span id="submit-caption">' . __('Submit') . '</span>' . $preloader . '</a>',
            ],
        ],
    ];

    echo $this->Lil->form($uploadForm, 'App.Utils.PdfMerge');
    echo $this->Html->meta('csrfToken', $this->getRequest()->getAttribute('csrfToken'));
    echo $this->Html->script('jquery/jquery-ui.min.js');
?>

<script type="text/javascript">
    var dragTargetCaption = "<?= __('Drag Here') ?>";
    var dragDropCaption = "<?= __('Drop') ?>";
    var dragDefaultCaption = "<?= __('Drag and Drop file here<br/>Or<br/>Click to select file') ?>";

    $(function() {
        // preventing page from redirecting
        $("html").on("dragover", function(e) {
            e.preventDefault();
            e.stopPropagation();
            $("h1").text(dragTargetCaption);
        });

        $("html").on("drop", function(e) { e.preventDefault(); e.stopPropagation(); });

        // Drag enter
        $('.upload-area').on('dragenter', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $("h1").text(dragDropCaption);
            $(this).addClass("drag-over");
        });
        $('.upload-area').on('dragout', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $("h1").text(dragTargetCaption);
            $(this).removeClass("drag-over");
        });

        // Drag over
        $('.upload-area').on('dragover', function (e) {
            e.stopPropagation();
            e.preventDefault();
            $("h1").text(dragDropCaption);
        });

        // Drop
        $('.upload-area').on('drop', function (e) {
            e.stopPropagation();
            e.preventDefault();

            $("h1").html(dragDefaultCaption);
            $(this).removeClass("drag-over");

            let files = e.originalEvent.dataTransfer.files;

            for (var i = 0, f; f = files[i]; i++) {
                appendItem(f);
            }
        });

        // Open file selector on div click
        $("#uploadfile").click(function(){
            $("#upload-file").click();
        });

        // file selected
        $("#upload-file").change(function(){
            let files = $('#upload-file')[0].files;

            for (var i = 0, f; f = files[i]; i++) {
                appendItem(f);
            }
        });

        $("#uploadfile").sortable({
            items: '> .upload-thumbnail'
        });

        $("#submit").click(function(e) {
            e.preventDefault();

            if ($("#uploadfile > .upload-thumbnail").length == 0) {
                alert("<?= __('Upload a file first!') ?>");
                return false;
            }

            if ($("#filename").val().trim() == "") {
                alert("<?= __('Please enter target file name!') ?>");
                return false;
            }

            if ($("#filename").val().split(".").pop().toLowerCase() != "pdf") {
                alert("<?= __('Target filename should be PDF!') ?>");
                return false;
            }

            let i = 0;
            let fd = new FormData();

            $("#uploadfile .upload-thumbnail").each(function(index) {
                fd.append("file[" + index + "]", $(this).data("file"));
            });

            fd.append("filename", $("#filename").val());

            fd.append("pdfa", $("#pdfa").prop("checked") ? "1" : "");
            fd.append("compression", $("#compression").val());

            uploadData(fd);

            return false;
        });
    });

    // Sending AJAX request and upload file
    function uploadData(formdata){
        $.ajax({
            url: "<?= Router::url(null, true) ?>",
            type: "post",
            data: formdata,
            contentType: false,
            processData: false,
            dataType: "json",
            headers: {
                "X-CSRF-Token" : $('meta[name="csrfToken"]').attr("content")
            },
            xhr: function() {
                var xhr = new window.XMLHttpRequest();

                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = evt.loaded / evt.total;
                        percentComplete = parseInt(percentComplete * 100);
                        $(".progress .determinate").width(percentComplete + "%");

                    }
                }, false);

                return xhr;
            },
            beforeSend: function() {
                $(".progress .determinate").width("0%");

                $("#spinner").addClass("active");
                $("#spinner").show();
                $("#submit-caption").hide();
            }
        })
        .always(function (response) {
            $("#spinner").removeClass("active");
            $("#spinner").hide();
            $("#submit-caption").show();
        })
        .fail(function(response){
            alert("Error Occured");
        })
        .done(function(response){
            document.location.replace("<?= Router::url(['controller' => 'Pages', 'action' => 'pdf']) ?>/" + response.filename);
        });
    }

    function appendItem(f) {
        if (f.name.split(".").pop().toLowerCase() != "pdf") {
            alert("<?= __('Only pdf files are allowed') ?>");
            return false;
        }
        
        $("#uploadfile h1").remove(); 

        let i = $("#uploadfile div.upload-thumbnail").length + 1;

        let name = f.name;
        let size = convertSize(f.size);
        let src = f.src;

        let d = new Date(f.lastModified );
        let date = d.toLocaleString();

        $("#uploadfile").append('<div id="thumbnail_' + i + '" class="upload-thumbnail row"></div>');
        $("#thumbnail_" + i).append('<span class="name col s7 truncate">' + name + '<span>');
        $("#thumbnail_" + i).append('<span class="size col s1 truncate">' + size + '<span>');
        $("#thumbnail_" + i).append('<span class="size col s3 truncate">' + date + '<span>');
        $("#thumbnail_" + i).append('<span class="remove col s1"><button class="remove-item waves-effect waves-light btn-small"><i class="material-icons">delete</i></button><span>');
        $("#thumbnail_" + i).data("file", f);

        $("#thumbnail_" + i + " button.remove-item").on("click", function(e) {
            e.stopPropagation();
            e.preventDefault();
            $(this).parents("div.upload-thumbnail").remove();
        });
    }


    // Bytes conversion
    function convertSize(size) {
        var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        if (size == 0) return '0 Byte';
        var i = parseInt(Math.floor(Math.log(size) / Math.log(1024)));
        return Math.round(size / Math.pow(1024, i), 2) + ' ' + sizes[i];
    }
</script>
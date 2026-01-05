jQuery.fn.modalPopup = function(p_options) {

    var default_options = {
        title: "",
        url: $(this).prop("href"),
        processSubmit: false,
        onBeforeSubmit: null,
        onJson: null,
        onHtml: null,
        onBeforeRequest: null,
        onOpen: null,
        onClose: null,
        onResize: null
	};
	var $this       = this;
    var options     = [];
    var dialog      = null;
    var oldHeight   = null;

    var template = [
        '<dialog>',
        '   <div class="dialog-header">',
        '   <a href="#" class="btn-small filled modal-close"><i class="material-icons">close</i></a>',
        '   <h3 id="modal-title">Modal Header</h3>',
        '   </div>',
        '   <p class="dialog-content"></p>',
        '</dialog>'
    ];


    this.onClick = function(e) {
        let url = $this.options.url;

        if ($this.options.onBeforeRequest instanceof Function) {
            let result = $this.options.onBeforeRequest(url, this);
            if (result) {
                url = result;
            }
        }
        var jqxhr = $.ajax(url)
            .done(function(html) {
                let json = $this.isJson(html);
                if (json) {
                    $this.dialog.close();
                    if ($this.options.onJson instanceof Function) {
                        $this.options.onJson(html, $this);
                    }
                } else {
                    $("p", $this.dialog).html(html);
                    $("#modal-title", $this.dialog).html($this.options.title);

                    if ($this.options.processSubmit) {
                        $("form", $this.dialog).submit($this.popupFormSubmit);
                        $("form", $this.dialog).attr("method", "dialog");
                    }

                    var selectInputs = $("select", $this.dialog);
                    selectInputs.each(function() {
                        M.FormSelect.init($(this).get(0));
                    });
                    
                    // evaluate javascript blocks if any
                    let script = $("p > script", $this.dialog);
                    if (script) {
                        //script.each(function() { eval($(this).text()) });
                    }
                }

                $this.dialog.showModal();

                if ($this.options.onOpen instanceof Function) {
                    $this.options.onOpen($this.dialog, $this);
                }
            })
            .fail(function() {
                alert("Request Failed");
            });


        e.preventDefault();
        return false;
    };

    // Do an ajax form post
    this.popupFormSubmit = function(e)
    {
        var submitData = $("form", $this.dialog);
        if ($this.options.onBeforeSubmit instanceof Function) {
            submitData = $this.options.onBeforeSubmit($("form", $this.dialog), $this);
        }
        $.ajax({
            type: "POST",
            url: $("form", $this.dialog).prop("action"),
            data: submitData.serialize(),
            success: function(data, status, xhr) {
                let json = $this.isJson(data);
                if (json) {
                    $this.dialog.close();
                    if ($this.options.onJson instanceof Function) {
                        $this.options.onJson(data, $this);
                    }
                } else {
                    // it's html
                    $("p", $this.dialog).html(data);

                    var selectInputs = $("select", $this.dialog);
                    selectInputs.each(function() {
                        M.FormSelect.init($(this).get(0));
                    });

                    // evaluate javascript blocks if any
                    let script = $("p > script", $this.dialog);
                    if (script) {
                        script.each(function() { eval($(this).text()) });
                    }

                    if ($this.options.processSubmit) {
                        $("form", $this.dialog).submit($this.popupFormSubmit);
                    }

                    if ($this.options.onHtml instanceof Function) {
                        $this.options.onHtml(data, $this);
                    }
                }
            }
        });

        e.preventDefault();
        return false;
    }

    this.isJson = function(jsonData) {
        if (typeof jsonData === "object") {
            return jsonData;
        }

        try {
            var o = JSON.parse(jsonData);
            if (o && typeof o === "object") {
                return o;
            }
        }
        catch (e) {  }

        return false;
    };

    this.onClose = function(e) {
        if ($this.options.onClose instanceof Function) {
            $this.options.onClose($this.dialog, $this);
        }

        $("p", $this.dialog).html("");
        $("#modal-title", $this.dialog).html("");
    }


    // initialization
    $this.options = jQuery().extend(true, {}, default_options, p_options);

    $this.dialog = $("dialog").get(0);
    if (!$this.dialog) {
        $this.dialog = $(template.join("\n")).appendTo(document.body).get(0);
    }

    // Close button handler
    $(".modal-close", $this.dialog).on("click", function(e) {
        $this.dialog.close();
        e.preventDefault();
        return false;
    });

    $this.dialog.addEventListener("close", $this.onClose);

    $(this).on("click", $this.onClick);
}

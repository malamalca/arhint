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
    var popup       = null;
    var instance    = null;
    var oldHeight   = null;

    var template = [
        '<div class="modal">',
        '   <div class="modal-content">',
        '   <a href="#" class="btn btn-small modal-close" style="float: right">x</a>',
        '   <h3 id="modal-title">Modal Header</h3>',
        '   <p class="modal-container"></p>',
        '   </div>',
        '</div>'
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
                    $this.instance.close();
                    if ($this.options.onJson instanceof Function) {
                        $this.options.onJson(html, $this);
                    }
                } else {
                    $("p", $this.popup).html(html);
                    $("#modal-title", $this.popup).html($this.options.title);

                    if ($this.options.processSubmit) {
                        $("form", $this.popup).submit($this.popupFormSubmit);
                    }

                    // update text fields for label placement
                    var selectInputs = $this.popup.get(0).querySelectorAll('select');
                    var instances = M.FormSelect.init(selectInputs);
                    //M.updateTextFields();
                    //M.AutoInit();
                }

                $this.instance.open();

                if ($this.options.onOpen instanceof Function) {
                    $this.options.onOpen($this.popup, $this.instance);
                }
            })
            .fail(function() {
                alert("Request Failed");
            });


        e.preventDefault();
        return false;
    };

    this.checkResize = function(e)
    {
        let popupHeight = $($this.popup).height();
        if ($this.oldHeight != popupHeight) {
            if ($this.options.onResize instanceof Function) {
                $this.options.onResize($this.popup, $this.instance);
            }
        }
        $this.oldHeight = popupHeight;
    }

    // Do an ajax form post
    this.popupFormSubmit = function(e)
    {
        var submitData = $("form", $this.popup).serialize()
        if ($this.options.onBeforeSubmit instanceof Function) {
            submitData = $this.options.onBeforeSubmit($("form", $this.popup), $this.instance);
        }
        $.ajax({
            type: "POST",
            url: $("form", $this.popup).prop("action"),
            data: submitData.serialize(),
            success: function(data, status, xhr) {
                let json = $this.isJson(data);
                if (json) {
                    $this.instance.close();
                    if ($this.options.onJson instanceof Function) {
                        $this.options.onJson(data, $this);
                    }
                } else {
                    // it's html
                    $("p", $this.popup).html(data);

                    // evaluate javascript blocks if any
                    let script = $("p > script", $this.popup);
                    if (script) {
                        script.each(function() { eval($(this).text()) });
                    }

                    if ($this.options.processSubmit) {
                        $("form", $this.popup).submit($this.popupFormSubmit);
                    }

                    if ($this.options.onHtml instanceof Function) {
                        $this.options.onHtml(data, $this);
                    }
                    // update text fields for label placement
                    //M.updateTextFields();
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


    // initialization
    $this.options = jQuery().extend(true, {}, default_options, p_options);

    $this.popup = $("div.modal");
    if (!$this.popup.length) {
        $this.popup = $(template.join("\n")).appendTo(document.body);
    }

    $(".modal-close", $this.popup).on("click", function(e) {
        $this.instance.close();
        e.preventDefault();
        return false;
    });

    $(window).on("resize", function(e) {
        $this.checkResize(e);
    });

    $this.instance = M.Modal.init($this.popup.get(0), {
        dismissible: true,
        onCloseEnd: function() {
            $(".modal-container", $this.popup).html("");
            if ($this.options.onClose instanceof Function) {
                $this.options.onClose($this.popup);
            }
        }
    });

    $(this).on("click", $this.onClick);
}

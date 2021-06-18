jQuery.fn.modalPopup = function(p_options) {

    var default_options = {
        title: "",
        url: $(this).prop("href"),
        processSubmit: false,
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
        '   <h4>Modal Header</h4>',
        '   <p></p>',
        '   </div>',
        '</div>'
    ];


    this.onClick = function(e) {
        let url = $this.options.url;

        if ($this.options.onBeforeRequest instanceof Function) {
            let result = $this.options.onBeforeRequest(url);
            if (result) {
                url = result;
            }
        }
        var jqxhr = $.ajax(url)
            .done(function(html) {
                $("p", $this.popup).html(html);
                $("h4", $this.popup).html($this.options.title);

                if ($this.options.processSubmit) {
                    $("form", $this.popup).submit($this.popupFormSubmit);
                }

                // update text fields for label placement
                M.updateTextFields();

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
        $.post(
            $("form", $this.popup).prop("action"),
            $("form", $this.popup).serialize(),
            function(data) {
                let json = $this.isJson(data);
                if (json) {
                    $this.instance.close();
                    if ($this.options.onJson instanceof Function) {
                        $this.options.onJson(data, $this);
                    }
                } else {
                    // it's html
                    if ($this.options.onHtml instanceof Function) {
                        $this.options.onHtml(data, $this);
                    } else {
                        $("p", $this.popup).html(data);


                        if ($this.options.processSubmit) {
                            $("form", $this.popup).submit($this.popupFormSubmit);
                        }

                        // update text fields for label placement
                        M.updateTextFields();
                    }
                }
            }
        );

        e.preventDefault();
        return false;
    }

    this.isJson = function(jsonData) {
        if (typeof jsonData === "object") {
            return jsonData;
        }

        try {
            var o = JSON.parse(jsonData);

            // Handle non-exception-throwing cases:
            // Neither JSON.parse(false) or JSON.parse(1234) throw errors, hence the type-checking,
            // but... JSON.parse(null) returns null, and typeof null === "object",
            // so we must check for that, too. Thankfully, null is falsey, so this suffices:
            if (o && typeof o === "object") {
                return o;
            }
        }
        catch (e) {  }

        return false;
    };


    // initialization
    $this.options = jQuery().extend(true, {}, default_options, p_options);
    $this.popup = $(template.join("\n")).appendTo(document.body);

    $(".modal-close", $this.popup).on("click", function(e) {
        $this.instance.close();
    });

    $(window).on("resize", function(e) {
        $this.checkResize(e);
    });

    $this.instance = M.Modal.init($this.popup.get(0), {
        dismissible: true,
        onCloseEnd: function() {
            if ($this.options.onClose instanceof Function) {
                $this.options.onClose($this.popup);
            }
        }
    });

    $(this).on("click", $this.onClick);
}

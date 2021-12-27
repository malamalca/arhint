jQuery.fn.DocumentEditClient = function (options) {
    var default_options = {
        clientCheckedIconUrl: "/crm/img/ico_contact_check.gif",
        clientAutoCompleteUrl: "",

        cbSameClientId: "#document-client-buyer-toggle",
        mode: "received",

        addContactDialogUrl: "",
        addCompanyDialogTitle: "Add a Company",
        addPersonDialogTitle: "Add a Person",

        messageStartTyping: "Start typing to search for a client",
        messageNoClientsFound: "No clients found."
    };
    var $this = this;

    var modalTemplate = [
        '<div class="modal">',
        '   <div class="modal-content">',
        '   <h4>Modal Header</h4>',
        '   <p></p>',
        '   </div>',
        '</div>'
    ];

    this.fillClientData = function (target, client) {
        var kinds = {"buyer":"BY", "receiver":"IV", "issuer":"II"};
        $("#document-" + target + "-kind", $this).val(kinds[target]);

        $("#document-" + target + "-contact-id", $this).val(client.id);
        $("#document-" + target + "-title", $this).val(client.title);
        $("#document-" + target + "-mat-no", $this).val(client.mat_no);
        $("#document-" + target + "-tax-no", $this).val(client.tax_no);

        if (client.primary_address) {
            $("#document-" + target + "-street", $this).val(client.primary_address.street);
            $("#document-" + target + "-city", $this).val(client.primary_address.city);
            $("#document-" + target + "-zip", $this).val(client.primary_address.zip);
            $("#document-" + target + "-country", $this).val(client.primary_address.country);
            $("#document-" + target + "-country-code", $this).val(client.primary_address.country_code);
        }

        if (client.primary_account) {
            $("#document-" + target + "-iban", $this).val(client.primary_account.iban);
            $("#document-" + target + "-bic", $this).val(client.primary_account.bic);
            $("#document-" + target + "-bank", $this).val(client.primary_account.bank);
        }

        if (client.primary_email) {
            $("#document-" + target + "-email", $this).val(client.primary_email.email);
        }

        if (client.primary_phone) {
            $("#document-" + target + "-phone", $this).val(client.primary_phone.phone);
        }

        if ($("#document-location", $this).val().trim() == "") {
            if (options.mode == "received" && target == "issuer") {
                $("#document-location", $this).val(client.primary_address.city);
            } else {

            }
        }

        M.updateTextFields()
    }

    this.collectClientData = function (target) {
        var client = {
            id: $("#document-" + target + "-contact-id", $this).val(),
            title: $("#document-" + target + "-title", $this).val(),
            mat_no: $("#document-" + target + "-mat-no", $this).val(),
            tax_no: $("#document-" + target + "-tax-no", $this).val(),

            street: $("#document-" + target + "-street", $this).val(),
            city: $("#document-" + target + "-city", $this).val(),
            zip: $("#document-" + target + "-zip", $this).val(),
            country: $("#document-" + target + "-country", $this).val(),
            country_code: $("#document-" + target + "-country-code", $this).val(),

            iban: $("#document-" + target + "-iban", $this).val(),
            bic: $("#document-" + target + "-bic", $this).val(),
            bank: $("#document-" + target + "-bank", $this).val(),

            email: $("#document-" + target + "-email", $this).val(),
            phone: $("#document-" + target + "-phone", $this).val()
        };

        return client;
    }

    this.toggleSameBuyerIssuer = function () {
        $("#buyer-wrapper", $this).toggle();
        if ($("#buyer-wrapper").is(":hidden")) {
            var receiver = $this.collectClientData("receiver");
            $this.fillClientData("buyer", receiver);
        }
    }

    this.selectClient = function (target, item) {
        $this.fillClientData(target, item);
        if (target == "receiver" && !$(options.cbSameClientId, $this).prop("checked")) {
            $this.fillClientData("buyer", item);
        }
        $("#image-" + target + "-checked", $this).show();

        if (options.mode == "received") {
            if ($("#document-location").val().trim() == "") {
                $("#document-location").val(item.city);
                M.updateTextFields()
            }
        }
    }

    this.setAutocompleteTitleField = function (target) {
        var elem = document.querySelector("#document-" + target + "-title");

        if (elem) {
            var instance = M.AutocompleteAjax.init(elem, {
                source: options.clientAutoCompleteUrl,
                minLength: 0,
                onSearch: function () {
                    var title = $("#document-" + target + "-title", $this).val();
                    $("input[id^='document-" + target + "-'").val("");
                    $("#document-" + target + "-title", $this).val(title);
                    $("#image-" + target + "-checked", $this).hide();
                },
                onSelect: function (item) {
                    $this.selectClient(target, item.client);
                },
                onOpenEnd: function (el) {
                    var li = $(
                        "<li style=\"line-height: inherit; min-height: 0;\">" +
                        "<button href=\"#\" class=\"\" style=\"width: 50%; min-height: 30px; float: left; \" id=\"AutocompleteAddPerson\">" +
                            options.addPersonDialogTitle +
                        "</button>" +
                        "<button href=\"#\" class=\"\" style=\"width: 50%; min-height: 30px; float: left;\" id=\"AutocompleteAddCompany\">" +
                            options.addCompanyDialogTitle +
                        "</button>" +
                        "</li>"
                    );

                    $(el).prepend(li);

                    $(el).css({"padding-top": "30px", "min-width": "300px"});
                    $(el).on('scroll', function () {
                        $(li).css({'top': $(el).scrollTop()}); });
                    $(li).css({'position': 'absolute', 'top': 0, 'background-color:': '#ff0000'});

                    $("#AutocompleteAddPerson", el).modalPopup({
                        url: options.addContactDialogUrl.replace("__kind__", "T"),
                        title: options.addPersonDialogTitle,
                        processSubmit: true,
                        onBeforeRequest: function () {
                            instance.close();
                        },
                        onJson: function (item) {
                            $this.selectClient(target, item);
                        }
                    });

                    $("#AutocompleteAddCompany", el).modalPopup({
                        url: options.addContactDialogUrl.replace("__kind__", "C"),
                        title: options.addCompanyDialogTitle,
                        processSubmit: true,
                        onBeforeRequest: function () {
                            instance.close();
                        },
                        onJson: function (item) {
                            $this.selectClient(target, item);
                        }
                    });
                }
            });

            $(elem)
                .on("keyup", function () {
                    if ($(this).val() === "") {
                        $("input[id^='document-" + target + "-'").val("");
                        $("#image-" + target + "-checked", $this).hide();
                    }
                })
                .on("focus", function () {
                    if (!$("#document-" + target + "-contact-id", $this).val()) {
                        instance.open();
                    }
                });
        }
    }

    this.addCheckIconAfterClientTitleField = function (target) {
        var clientCheck = $('<img />', {
            id: 'image-' + target + '-checked',
            src: options.clientCheckedIconUrl,
            style: 'display: none'
        });
        $('#document-' + target + '-title', $this).after(clientCheck);
        if ($('#document-' + target + '-contact-id', $this).val()) {
            $('#image-' + target + '-checked', $this).show();
        }
    }

    // initialization
    options = jQuery().extend(true, {}, default_options, options);

    $this.popup = $(modalTemplate.join("\n")).appendTo(document.body);
    $this.popup.modal();
    $this.popupInstance = M.Modal.getInstance($this.popup);

    if (options.mode == "received") {
        this.addCheckIconAfterClientTitleField("issuer");
        this.setAutocompleteTitleField("issuer");
    } else {
        this.addCheckIconAfterClientTitleField("receiver");
        this.addCheckIconAfterClientTitleField("buyer");
        this.setAutocompleteTitleField("receiver");
        this.setAutocompleteTitleField("buyer");

        if ($("#document-receiver-contact-id", $this).val() == $("#document-receiver-contact-id", $this).val()) {
            $("#buyer-wrapper", $this).hide();
        }
        $(options.cbSameClientId, $this).click($this.toggleSameBuyerIssuer);
    }


}

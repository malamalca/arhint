jQuery.fn.InvoiceEditor = function (options) {
    var default_options = {
        clientCheckedIconUrl: "/lil_crm/img/ico_contact_check.gif",
        clientAutoCompleteUrl: "",

        cbSameClientId: "#invoice-client-buyer-toggle",
        mode: "received",

        addContactDialogUrl: "",
        addCompanyDialogTitle: "Add a Company",
        addPersonDialogTitle: "Add a Person",

        messageStartTyping: "Start typing to search for a client",
        messageNoClientsFound: "No clients found."
    };
    var $this = this;

    this.fillClientData = function (target, client) {
        var kinds = {"buyer":"BY", "receiver":"IV", "issuer":"II"};
        $("#invoice-" + target + "-kind", $this).val(kinds[target]);

        $("#invoice-" + target + "-contact-id", $this).val(client.id);
        $("#invoice-" + target + "-title", $this).val(client.title);
        $("#invoice-" + target + "-mat-no", $this).val(client.mat_no);
        $("#invoice-" + target + "-tax-no", $this).val(client.tax_no);

        $("#invoice-" + target + "-street", $this).val(client.street);
        $("#invoice-" + target + "-city", $this).val(client.city);
        $("#invoice-" + target + "-zip", $this).val(client.zip);
        $("#invoice-" + target + "-country", $this).val(client.country);
        $("#invoice-" + target + "-country-code", $this).val(client.country_code);

        $("#invoice-" + target + "-iban", $this).val(client.iban);
        $("#invoice-" + target + "-bank", $this).val(client.bank);

        $("#invoice-" + target + "-email", $this).val(client.email);
        $("#invoice-" + target + "-phone", $this).val(client.phone);
    }

    this.collectClientData = function (target) {
        var client = {
            id: $("#invoice-" + target + "-contact-id", $this).val(),
            title: $("#invoice-" + target + "-title", $this).val(),
            mat_no: $("#invoice-" + target + "-mat-no", $this).val(),
            tax_no: $("#invoice-" + target + "-tax-no", $this).val(),

            street: $("#invoice-" + target + "-street", $this).val(),
            city: $("#invoice-" + target + "-city", $this).val(),
            zip: $("#invoice-" + target + "-zip", $this).val(),
            country: $("#invoice-" + target + "-country", $this).val(),
            country_code: $("#invoice-" + target + "-country-code", $this).val(),

            iban: $("#invoice-" + target + "-iban", $this).val(),
            bank: $("#invoice-" + target + "-bank", $this).val(),

            email: $("#invoice-" + target + "-email", $this).val(),
            phone: $("#invoice-" + target + "-phone", $this).val()
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

    this.setAutocompleteTitleField = function (target) {
        if ($("#invoice-" + target + "-title").get(0)) {
            $("#invoice-" + target + "-title").autocompleteheader({
                target: target,
                autoFocus: false,
                minLength: 0,
                source: options.clientAutoCompleteUrl,
                search: function () {
                    var title = $("#invoice-" + target + "-title", $this).val();
                    $("input[id^='invoice-" + target + "-'").val("");
                    $("#invoice-" + target + "-title", $this).val(title);
                    $("#image-" + target + "-checked", $this).hide();
                },
                response: function (event, ui) {
                    if (ui.content.length === 0) {
                        var noResultsMessage = $(this).val().length == 0 ? options.messageStartTyping : options.messageNoClientsFound;

                        var noResult = {value: '', label: noResultsMessage, systemMessage: true};
                        ui.content.push(noResult);
                    }
                },
                select: function (event, ui) {
                    if (ui.item) {
                        $this.fillClientData(target, ui.item);
                        if (target == "receiver" && !$(options.cbSameClientId, $this).prop("checked")) {
                            $this.fillClientData("buyer", ui.item);
                        }
                        $("#image-" + target + "-checked", $this).show();
                    }
                },
            })
            .keyup(function () {
                if ($(this).val() === "") {
                    $("input[id^='invoice-" + target + "-'").val("");
                    $("#image-" + target + "-checked", $this).hide();
                }
            })
            .focus(function () {
                if (!$("#invoice-" + target + "-contact-id", $this).val()) {
                    $(this).autocompleteheader("search");
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
        $('#invoice-' + target + '-title', $this).after(clientCheck);
        if ($('#invoice-' + target + '-contact-id', $this).val()) {
            $('#image-' + target + '-checked', $this).show();
        }
    }

    this.addClientDialog = function (contactKind, target) {
        popup({
            title: contactKind == 'C' ? options.addCompanyDialogTitle : options.addPersonDialogTitle,
            url: options.addContactDialogUrl.replace("__kind__", contactKind),
            w: 780,
            h: 'auto',
            onClose: function (e) {
                return false;
            },
            onData: function (client) {
                $this.fillClientData(target, client);
                if (target == "receiver" && !$(options.cbSameClientId, $this).prop("checked")) {
                    $this.fillClientData("buyer", client);
                }
                $("#image-" + target + "-checked", $this).show();
                $("#dialog-form").dialog("close");

                return false;
            }
        });
    }


    // initialization
    options = jQuery().extend(true, {}, default_options, options);

    $.widget("custom.autocompleteheader", $.ui.autocomplete, {
        options: {
            target: "issuer",
        },
        _create: function () {
            this._super();
            this.widget().menu("option", "items", "> :not(.autocomplete-custom-header)");
        },
        _renderItem: function ( ul, item ) {
            var li = $("<li>")
                .attr("data-value", item.value)
                .append(item.label);

            if (typeof item.systemMessage != "undefined") {
                $(li).attr("class", "ui-state-disabled");
            }

            return $(li).appendTo(ul);
        },
        _renderMenu: function (ul, items) {
            var self = this;
            $.each(items, function (index, item) {
                self._renderItemData(ul, item);
                if (index == 0) {
                    var addCompanyLink = $("<a>")
                        .attr("class", "autocomplete-custom-header-button")
                        .click(function () {
                            $this.addClientDialog("C", self.options.target) })
                        .append("Add Company");
                    var addPersonLink = $("<a>")
                        .attr("class", "autocomplete-custom-header-button")
                        .click(function () {
                            $this.addClientDialog("T", self.options.target) })
                        .append("Add Person");

                    var li = $("<li>")
                        .attr("class", "autocomplete-custom-header")
                        .append(addCompanyLink)
                        .append(addPersonLink);

                    ul.prepend(li);
                }
            });
        }
    });

    if (options.mode == "received") {
        this.addCheckIconAfterClientTitleField("issuer");
        this.setAutocompleteTitleField("issuer");
    } else {
        this.addCheckIconAfterClientTitleField("receiver");
        this.addCheckIconAfterClientTitleField("buyer");
        this.setAutocompleteTitleField("receiver");
        this.setAutocompleteTitleField("buyer");

        if ($("#invoice-receiver-contact-id", $this).val() == $("#invoice-receiver-contact-id", $this).val()) {
            $("#buyer-wrapper", $this).hide();
        }
        $(options.cbSameClientId, $this).click($this.toggleSameBuyerIssuer);
    }


}

<?php
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Routing\Router;

$client = $counter->kind == 'issued' ? 'receiver' : 'issuer';

if ($invoice->isNew()) {
    $layoutTitle = __d(
        'lil_invoices',
        'Add an Invoice #{0} <span class="light">({1})</span>',
        $counter->counter + 1,
        h($counter->title)
    );
} else {
    $layoutTitle = __d(
        'lil_invoices',
        'Edit an Invoice #{0} <span class="light">({1})</span>',
        $invoice->counter,
        h($counter->title)
    );
}

$invoiceEdit = [
    'title_for_layout' => $layoutTitle,
    'menu' => [
        'editPreview' => [
            'title' => __d('lil_invoices', 'Preview'),
            'visible' => true,
            'url' => "javascript:void();",
            'params' => ['id' => 'MenuEditPreview'],
        ],
    ],
    'form' => [
        'defaultHelper' => $this->Form,

        'pre' => '<div class="form">',
        'post' => '</div>',

        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [
                    $invoice, [
                        'type' => 'file',
                        'id' => 'invoice-edit-form',
                        'idPrefix' => 'invoice',
                        'url' => [
                            'action' => $invoice->id ? 'edit' : 'add',
                            $invoice->id,
                            '?' => ['counter' => $invoice->counter_id],
                        ],
                    ],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => ['referer', [
                    'type' => 'hidden',
                    'default' => $this->getRequest()->referer()
                ]],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => ['id', ['type' => 'hidden']],
            ],
            'user_id' => [
                'method' => 'control',
                'parameters' => ['user_id', [
                    'type' => 'hidden',
                    'default' => $this->getCurrentUser()->get('id'),
                ]],
            ],
            'counter_id' => [
                'method' => 'control',
                'parameters' => ['counter_id', ['type' => 'hidden']],
            ],
            'doc_type' => [
                'method' => 'control',
                'parameters' => ['doc_type', ['type' => 'hidden']],
            ],
            'counter' => [
                'method' => 'control',
                'parameters' => [
                    'counter', [
                        'type' => 'hidden',
                        'default' => $counter->counter + 1,
                    ],
                ],
            ],
            'duplicate' => [
                'method' => 'control',
                'parameters' => ['duplicate', ['type' => 'hidden']],
            ],
            'tpl_header_id' => [
                'method' => 'control',
                'parameters' => ['tpl_header_id', ['type' => 'hidden', 'default' => $counter->tpl_header_id]],
            ],
            'tpl_footer_id' => [
                'method' => 'control',
                'parameters' => ['tpl_footer_id', ['type' => 'hidden', 'default' => $counter->tpl_footer_id]],
            ],
            'tpl_body_id' => [
                'method' => 'control',
                'parameters' => ['tpl_body_id', ['type' => 'hidden', 'default' => $counter->tpl_body_id]],
            ],

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_basic_start' => '<fieldset>',
            'fs_basic_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Basics')),
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'label' => __d('lil_invoices', 'Title') . ':',
                        'error' => [
                            'empty' => __d('lil_invoices', 'Please enter invoice\'s title.'),
                        ],
                    ],
                ],
            ],
            'client' => [
                'method' => 'control',
                'parameters' => [
                    'field' => $client . '.title',
                    [
                        'label' => ($counter->kind == 'issued' ? __d('lil_invoices', 'Receiver') : __d('lil_invoices', 'Issuer')) . ':',
                        'autocomplete' => 'off'
                    ],
                ],
            ],
            'client_error' => [
                'method' => 'error',
                'parameters' => [$client . '.title', __d('lil_invoices', 'Please choose a client')],
            ],
            'client_kind_error' => [
                'method' => 'error',
                'parameters' => [$client . '.kind', __d('lil_invoices', 'Please choose a client')],
            ],
            'no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'no', [
                        'label' => ($invoice->isInvoice() ? __d('lil_invoices', 'Invoice no') : __d('lil_invoices', 'Document no')) . ':',
                        'disabled' => !empty($counter->mask),
                    ],
                ],
            ],
            'location' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'location', [
                        'label' => __d('lil_invoices', 'Location') . ':',
                        'default' => $invoice->issuer->city,
                    ],
                ],
            ],
            'project' => !Plugin::isLoaded('LilProjects') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'project_id', [
                        'type' => 'select',
                        'label' => [
                            'text' => __d('lil_invoices', 'Project') . ':',
                            'class' => 'active'
                        ],
                        'options' => $projects,
                        'empty' => '-- ' . __d('lil_invoices', 'no project') . ' --',
                        'class' => 'browser-default',
                        'default' => $this->getRequest()->getQuery('project')
                    ],
                ],
            ],
            'fs_basic_end' => '</fieldset>', // basics

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_dates_start' => '<fieldset>',
            'fs_dates_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Dates')),
            'fs_dates_table_start' => '<table id="invoice-dates-table"><tr><td>',
            'dat_issue' => [
                'method' => 'control',
                'parameters' => [
                    'dat_issue',
                    'options' => [
                        'type' => 'date',
                        'label' => __d('lil_invoices', 'Date of issue') . ':',
                        'error' => [
                            'empty' => __d('lil_invoices', 'Blank'),
                        ],
                    ],
                ],
            ],
            'fs_dates_col1_end' => '</td>',

            'fs_dates_table_end' => '</tr></table>',
            'fs_dates_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_descript_start' => '<fieldset>',
            'fs_descript_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Description')),
            'description' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    'options' => [
                        'type' => 'textarea',
                        'label' => false,
                        'default' => $counter->template_descript,
                    ],
                ],
            ],
            'fs_descript_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_attachments_start' => '<fieldset>',
            'fs_attachments_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Archive')),
            'file.name.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'invoices_attachments.0.filename',
                    'options' => [
                        'type' => 'file',
                        'label' => false,
                    ],
                ],
            ],
            'file.invoice_id.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'invoices_attachments.0.invoice_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ],
            'fs_attachments_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    __d('lil_invoices', 'Save'),
                ],
            ],

            'loop' => /*!$invoice->isNew()*/ 1 == 1 ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'loop',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('lil_invoices', 'Add another invoice after saving this one'),
                    ],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

////////////////////////////////////////////////////////////////////////////////////////////////
// hidden client fields
require dirname(dirname(__FILE__)) . DS . 'element' . DS . 'edit_client.php';
$this->Lil->insertIntoArray($invoiceEdit['form']['lines'], clientFields('receiver', $invoice->receiver), ['after' => 'client_error']);
$this->Lil->insertIntoArray($invoiceEdit['form']['lines'], clientFields('issuer', $invoice->receiver), ['after' => 'client_error']);

if ($invoice->isInvoice()) {
    ////////////////////////////////////////////////////////////////////////////////////////////////
    // additional client "buyer" - INVOICES ONLY
    $clientBuyer = clientFields('buyer', $invoice->buyer) +
    [
        'client_buyer_start' => $counter->kind == 'received' ? null : '<div id="buyer-wrapper">',
        'client_buyer' => $counter->kind == 'received' ? null : [
            'method' => 'control',
            'parameters' => [
                'field' => 'buyer.title',
                ['label' => __d('lil_invoices', 'Buyer') . ':'],
            ],
        ],
        'client_buyer_end' => $counter->kind == 'received' ? null : '</div>',

        'client_buyer_toggle' => $counter->kind == 'received' ? null : [
            'method' => 'control',
            'parameters' => [
                'field' => 'client-buyer-toggle', [
                    'type' => 'checkbox',
                    'checked' => $invoice->buyer->contact_id != $invoice->receiver->contact_id,
                    'label' => ' ' . __d('lil_invoices', 'Use different client for buyer.'),
                ],
            ],
        ],
    ];
    $this->Lil->insertIntoArray($invoiceEdit['form']['lines'], $clientBuyer, ['after' => 'client_error']);

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // additional dates - INVOICES ONLY
    $invoiceDates = [
        'fs_dates_col2_start' => '<td>',
        'dat_service' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'dat_service',
                'options' => [
                    'type' => 'date',
                    'label' => __d('lil_invoices', 'Service date') . ':',
                    'error' => ['empty' => __d('lil_invoices', 'Blank')],
                ],
            ],
        ],
        'fs_dates_col2_end' => '</td>',

        'fs_dates_col3_start' => '<td>',
        'dat_expire' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'dat_expire',
                'options' => [
                    'type' => 'date',
                    'label' => __d('lil_invoices', 'Expiration date') . ':',
                    'error' => ['empty' => __d('lil_invoices', 'Blank')],
                ],
            ],
        ],
        'fs_dates_col3_end' => '</td>',
    ];
    $this->Lil->insertIntoArray($invoiceEdit['form']['lines'], $invoiceDates, ['after' => 'fs_dates_col1_end']);

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // analytics and taxes
    $analytics = [];
    if ($invoice->isInvoice() && $counter->kind == 'received') {
        $analytics['fs_analytics_start'] = '<fieldset>';
        $analytics['fs_analytics_legend'] = sprintf('<legend>%s</legend>', __d('lil_invoices', 'Analytics'));
        $analytics['analytics'] = [
            'method' => 'control',
            'parameters' => [
                'total', [
                    'type' => 'number',
                    'step' => 0.01,
                    'label' => __d('lil_invoices', 'Total') . ':',
                ],
            ],
        ];
        $analytics['fs_analytics_end'] = '</fieldset>';
    } elseif ($counter->kind == 'issued') {
        $analytics['fs_analytics_start'] = '<fieldset>';
        $analytics['fs_analytics_legend'] = sprintf('<legend>%s</legend>', __d('lil_invoices', 'Analytics'));
        require dirname(dirname(__FILE__)) . DS . 'element' . DS . 'edit_items.php';
        $analytics['fs_analytics_end'] = '</fieldset>';
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////////////
    if (in_array($counter->kind, ['received'])) {
        $analytics['fs_tax_start'] = '<fieldset>';
        $analytics['fs_tax_legend'] = sprintf('<legend>%s</legend>', __d('lil_invoices', 'Taxes Analytics'));
        require dirname(dirname(__FILE__)) . DS . 'element' . DS . 'edit_tax.php';
        $analytics['fs_tax_end'] = '</fieldset>';
    }
    $this->Lil->insertIntoArray($invoiceEdit['form']['lines'], $analytics, ['after' => 'fs_dates_end']);

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // payment details
    $paymentDetails = [
        'fs_payment_start' => '<fieldset>',
        'fs_payment_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Payment')),
        'pmt_kind' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'pmt_kind', [
                    'type' => 'select',
                    'options' => [
                        0 => __d('lil_invoices', 'Invoice must be payed.'),
                        1 => __d('lil_invoices', 'Auto transaction. No payment needed.'),
                        2 => __d('lil_invoices', 'Invoice payed in full.'),
                        3 => __d('lil_invoices', 'Other. No payment needed.'),
                    ],
                    'default' => 0,
                    'class' => 'browser-default',
                    'label' => [
                        'class' => 'active',
                        'text' => __d('lil_invoices', 'Payment Kind') . ':',
                    ],
                ],
            ],
        ],
        'pmt_sepa_type' => [
            'method' => 'control',
            'parameters' => [
                'pmt_sepa_type',
                'options' => [
                    'type' => 'select',
                    'options' => Configure::read('LilInvoices.sepaTypes'),
                    'default' => 'OTHR',
                    'class' => 'browser-default',
                    'label' => [
                        'class' => 'active',
                        'text' => __d('lil_invoices', 'Sepa Type') . ':',
                    ],
                    'error' => [
                        'format' => __d('lil_invoices', 'Invalid type'),
                    ],
                ],
            ],
        ],
        'fs_payment_table_start' => '<table id="InvoicePayment"><tr><td>',

        'pmt_type' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'pmt_type',
                'options' => [
                    'type' => 'text',
                    'size' => 2,
                    'default' => 'SI',
                    'label' => __d('lil_invoices', 'Type') . ':',
                    'error' => [
                        'format' => __d('lil_invoices', 'Invalid format'),
                    ],
                ],
            ],
        ],
        'fs_payment_col1' => '</td><td>',
        'pmt_module' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'pmt_module',
                'options' => [
                    'type' => 'text', 'size' => 2,
                    'default' => '00',
                    'label' => __d('lil_invoices', 'MOD') . ':',
                    'error' => [
                        'format' => __d('lil_invoices', 'Invalid format'),
                    ],
                ],
            ],
        ],
        'fs_payment_col2' => '</td><td>',
        'pmt_ref' => [
            'method' => 'control',
            'parameters' => [
                'field' => 'pmt_ref',
                'options' => [
                    'type' => 'text',
                    'default' => '0',
                    'label' => __d('lil_invoices', 'Reference') . ':',
                    'error' => [
                        'empty' => __d('lil_invoices', 'Blank'),
                    ],
                ],
            ],
        ],
        'fs_payment_table_end' => '</td></tr></table>',
        'fs_payment_hint' => sprintf(
            '<div class="helper-text">%s</div>',
            __d('lil_invoices', 'Use \'OTHR\' for unknown payment type. Use prefix and two numers for module (eg SI00).')
        ),
        'fs_payment_end' => '</fieldset>',
    ];

    $this->Lil->insertIntoArray($invoiceEdit['form']['lines'], $paymentDetails, ['before' => 'fs_descript_start']);
}

echo $this->Html->script('/LilInvoices/js/invoice_edit_taxes');
echo $this->Html->script('/LilInvoices/js/invoice_edit_items');
echo $this->Html->script('/LilInvoices/js/invoiceEditClient');
echo $this->Html->script('/LilInvoices/js/tinymce/tinymce.min.js');
echo $this->Html->css('/LilCrm/css/lil_crm');

echo $this->Lil->form($invoiceEdit, 'LilInvoices.Invoices.edit');
?>


<script type="text/javascript">
    // constants for scripts
    var itemsAutocompleteUrl = "<?= Router::url(['controller' => 'Items', 'action' => 'autocomplete']) ?>";
    var vatLevels = <?= json_encode($vatLevels ?? []) ?>;

    $(document).ready(function() {
        $("#invoice-tax-table").InvoiceTaxEditor({
            vats: vatLevels
        });
        $("#invoice-items-table").InvoiceItemEditor({
            vats: vatLevels,
            itemsAutocompleteUrl: itemsAutocompleteUrl
        });

        $("#invoice-edit-form").InvoiceEditClient({
            mode: "<?= $counter->kind;?>",
            clientCheckedIconUrl: "<?= Router::url('/lil_crm/img/ico_contact_check.gif'); ?>",

            clientAutoCompleteUrl: "<?= Router::url([
                'plugin' => 'LilCrm',
                'controller' => 'Contacts',
                'action' => 'autocomplete',
                '?' => ['detailed' => true],
            ]); ?>",
            addContactDialogUrl: "<?= Router::url([
                'plugin' => 'LilCrm',
                'controller' => 'Contacts',
                'action' => 'add',
                '?' => ['kind' => '__kind__'],
            ]); ?>",

            addCompanyDialogTitle: "<?= __d('lil_invoices', 'Add a Company'); ?>",
            addPersonDialogTitle: "<?= __d('lil_invoices', 'Add a Person'); ?>",
        });

        $("#invoice-dat-issue").change(function(dateText, inst) {
            let dateVal = $(this).val();
            if ($('#invoice-dat-service').val() == "") $('#invoice-dat-service').val(dateVal);
            if ($('#invoice-dat-expire').val() == "") $('#invoice-dat-expire').val(dateVal);
        });

        // EditPreview Javascript Code
        var iframe = $('<iframe frameborder="0" marginwidth="0" marginheight="0" style="width:100%; height: 99%" name="IframeEditPreview" id="IframeEditPreview"></iframe>');

        var dialog = $("<div class=\"modal\" id=\"editPreviewWindow\"></div>")
            .append(iframe)
            .appendTo("body")
            .modal();

        $("#MenuEditPreview").click(function(e) {
            e.preventDefault();

            var defaultAction = $("#invoice-edit-form").prop("action");
            var defaultTarger = $("#invoice-edit-form").prop("target");

            $("#invoice-edit-form")
                .prop("action", "<?= Router::url(['action' => 'editPreview']); ?>")
                .prop("target", "IframeEditPreview")
                .submit();

            $("#invoice-edit-form")
                .prop("action", defaultAction)
                .prop("target", defaultTarger);

            dialog.modal("open");


            $("#editPreviewWindow").height($(window.top).height())
        });


        // HTML Wysiwyg Javascript Code
        //$('textarea#invoice-descript').textareaAutoSize();
        tinymce.init({
            selector:'textarea#invoice-descript',
            menubar:false,
            statusbar: false,
            toolbar: 'undo redo | styleselect | bold italic underline subscript superscript | bullist numlist | indent outdent | pagebreak | pasteword table image',
            plugins: "autoresize table paste pagebreak image",
            table_toolbar: "tablecellprops | tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
            paste_auto_cleanup_on_paste : true,
            autoresize_max_height: 500,
            width: "750px"
        });
    });


</script>

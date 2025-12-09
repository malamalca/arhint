<?php
use Cake\Core\Plugin;
use Cake\Routing\Router;

$client = $document->documents_counter->direction == 'issued' ? 'receiver' : 'issuer';
$counter = $document->documents_counter;
$this->set('currentCounter', $counter->id);

if ($document->isNew()) {
    $layoutTitle = __d(
        'documents',
        'Add an Document #{0} <span class="light">({1})</span>',
        $counter->counter + 1,
        h($counter->title)
    );
} else {
    $layoutTitle = __d(
        'documents',
        'Edit an Document #{0} <span class="light">({1})</span>',
        $document->counter,
        h($counter->title)
    );
}

$documentEdit = [
    'title_for_layout' => $layoutTitle,
    'menu' => [
        'editPreview' => [
            'title' => __d('documents', 'Preview'),
            'visible' => true,
            'url' => 'javascript:void();',
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
                    $document, [
                        'type' => 'file',
                        'id' => 'invoice-edit-form',
                        'idPrefix' => 'invoice',
                        'url' => array_filter([
                            'action' => 'edit',
                            $document->id,
                            '?' => ['counter' => $document->counter_id],
                        ]),
                    ],
                ],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => ['referer', [
                    'type' => 'hidden',
                    'default' => $this->getRequest()->getQuery('redirect'),
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
            'fs_basic_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Basics')),
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'title',
                    [
                        'label' => __d('documents', 'Title') . ':',
                        'error' => [
                            'empty' => __d('documents', 'Please enter document\'s title.'),
                        ],
                    ],
                ],
            ],
            'client' => [
                'method' => 'control',
                'parameters' => [
                    'field' => $client . '.title',
                    [
                        'label' => ($counter->direction == 'issued' ? __d('documents', 'Receiver') : __d('documents', 'Issuer')) . ':',
                        'autocomplete' => 'off',
                    ],
                ],
            ],
            'client_hint' => sprintf(
                '<div class="helper-text">%s</div>',
                $this->Lil->link(
                    __d('documents', 'Start typing to select client or add a [$1new company] or [$2new contact].'),
                    [
                        1 => [
                            [
                                'plugin' => 'Crm',
                                'controller' => 'Contacts',
                                'action' => 'edit',
                                '?' => ['kind' => 'C'],
                            ],
                            [
                                'id' => 'AddCompanyLink',
                                'tabIndex' => -1,
                            ],
                        ],
                        2 => [
                            [
                                'plugin' => 'Crm',
                                'controller' => 'Contacts',
                                'action' => 'edit',
                                '?' => ['kind' => 'T'],
                            ],
                            [
                                'id' => 'AddContactLink',
                                'tabIndex' => -1,
                            ],
                        ],
                    ]
                )
            ),
            'client_error' => [
                'method' => 'error',
                'parameters' => [$client . '.title', __d('documents', 'Please choose a client')],
            ],
            'client_kind_error' => [
                'method' => 'error',
                'parameters' => [$client . '.kind', __d('documents', 'Please choose a client')],
            ],
            'no' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'no', [
                        'label' => __d('documents', 'Document no') . ':',
                        'disabled' => !empty($counter->mask),
                    ],
                ],
            ],
            'location' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'location', [
                        'label' => __d('documents', 'Location') . ':',
                        'default' => $document->issuer->city,
                    ],
                ],
            ],
            'project' => !Plugin::isLoaded('Projects') ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'project_id', [
                        'type' => 'select',
                        'label' => __d('documents', 'Project') . ':',
                        'options' => $projects,
                        'empty' => '-- ' . __d('documents', 'no project') . ' --',
                        'default' => $this->getRequest()->getQuery('project'),
                    ],
                ],
            ],
            'fs_basic_end' => '</fieldset>', // basics

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_dates_start' => '<fieldset>',
            'fs_dates_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Dates')),
            'dat_issue' => [
                'method' => 'control',
                'parameters' => [
                    'dat_issue',
                    'options' => [
                        'type' => 'date',
                        'label' => __d('documents', 'Date of issue') . ':',
                        'error' => [
                            'empty' => __d('documents', 'Blank'),
                        ],
                    ],
                ],
            ],
            'fs_dates_end' => '</fieldset>',

            ////////////////////////////////////////////////////////////////////////////////////
            'fs_descript_start' => '<fieldset>',
            'fs_descript_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Description')),
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
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('documents', 'Save'),
                    [
                        'type' => 'submit',
                    ],
                ],
            ],

                        ////////////////////////////////////////////////////////////////////////////////////
            'fs_attachments_start' => '<fieldset>',
            'fs_attachments_legend' => sprintf('<legend>%s</legend>', __d('documents', 'Archive')),
            'file.name.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.filename',
                    'options' => [
                        'type' => 'file',
                        'label' => false,
                    ],
                ],
            ],
            'file.document_id.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.document_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ],
            'file.model.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.model',
                    'options' => [
                        'type' => 'hidden',
                        'value' => 'Document',
                    ],
                ],
            ],
            'fs_attachments_end' => '</fieldset>',

            'loop' => /*!$document->isNew()*/ 1 == 1 ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'loop',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('documents', 'Add another document after saving this one'),
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
$this->Lil->insertIntoArray($documentEdit['form']['lines'], clientFields('receiver', 'Document'), ['after' => 'client_error']);
$this->Lil->insertIntoArray($documentEdit['form']['lines'], clientFields('issuer', 'Document'), ['after' => 'client_error']);

echo $this->Html->script('/Documents/js/invoiceEditClient');
echo $this->Html->script('/Documents/js/tinymce/tinymce.min.js');
echo $this->Html->css('/Crm/css/crm');

echo $this->Lil->form($documentEdit, 'Documents.Invoices.edit');
?>


<script type="text/javascript">
    // constants for scripts
    var itemsAutocompleteUrl = "<?= Router::url(['controller' => 'Items', 'action' => 'autocomplete']) ?>";

    $(document).ready(function() {
        $("#invoice-edit-form").InvoiceEditClient({
            mode: "<?= $counter->direction;?>",
            clientCheckedIconUrl: "<?= Router::url('/crm/img/ico_contact_check.gif'); ?>",

            clientAutoCompleteUrl: "<?= Router::url([
                'plugin' => 'Crm',
                'controller' => 'Contacts',
                'action' => 'autocomplete',
                '?' => ['detailed' => true],
            ]); ?>",
            addContactDialogUrl: "<?= Router::url([
                'plugin' => 'Crm',
                'controller' => 'Contacts',
                'action' => 'edit',
                '?' => ['kind' => '__kind__'],
            ]); ?>",

            addCompanyDialogTitle: "<?= __d('documents', 'Add a Company'); ?>",
            addPersonDialogTitle: "<?= __d('documents', 'Add a Person'); ?>",
        });

        // EditPreview Javascript Code
        var iframe = $('<iframe frameborder="0" marginwidth="0" marginheight="0" style="width:100%; height: 99%" name="IframeEditPreview" id="IframeEditPreview"></iframe>');

        //var dialog = $("<div class=\"modal\" id=\"editPreviewWindow\"></div>")
        //    .append(iframe)
        //    .appendTo("body")
        //    .modal();

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
        //$('textarea#document-descript').textareaAutoSize();
        tinymce.init({
            selector:'textarea#invoice-descript',
            menubar:false,
            statusbar: false,
            convert_urls: false,
            toolbar: 'undo redo | styleselect | bold italic underline subscript superscript | bullist numlist | indent outdent | pagebreak | pasteword table image | code',
            plugins: "autoresize table paste pagebreak image",
            table_toolbar: "tablecellprops | tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
            paste_auto_cleanup_on_paste : true,
            autoresize_max_height: 500,
            width: "750px"
        });

        $("#invoice-title").focus();
    });


</script>

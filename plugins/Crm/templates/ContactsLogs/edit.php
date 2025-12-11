<?php
use Cake\Routing\Router;

/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($contact->title) . ' :: ' .
        ($contactsLog->id ? __d('crm', 'Edit Log') : __d('crm', 'Add Log')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $contactsLog, ['idPrefix' => 'contacts-logs', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'contact_id' => [
                'method' => 'hidden',
                'parameters' => ['contact_id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],

            'descript' => [
                'method' => 'textarea',
                'parameters' => [
                    'descript',
                    [
                        'id' => 'contacts-logs-descript',
                    ],
                ],
            ],
            'spacer' => '<div>&nbsp;</div>',
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('crm', 'Save'),
                    ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
echo $this->Lil->form($editForm, 'Crm.ContactsLogs.edit');
echo $this->Html->script('/Documents/js/tinymce/tinymce.min.js');
?>
<script type="text/javascript">
    $(document).ready(function() {
        tinymce.init({
            selector:'#contacts-logs-descript',
            menubar:false,
            statusbar: false,
            toolbar: "undo redo | styleselect | bold italic underline subscript superscript | bullist numlist | indent outdent | pagebreak | pasteword table image",
            plugins: "autoresize table paste pagebreak image",
            table_toolbar: "tablecellprops | tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
            paste_auto_cleanup_on_paste : true,
            autoresize_max_height: 350,
            width: "700px"
        });
    })
</script>
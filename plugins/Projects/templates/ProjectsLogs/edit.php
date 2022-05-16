<?php
/**
 * This is admin_edit template file.
 */

$editForm = [
    'title_for_layout' =>
        h($project->getName()) . ' :: ' .
        ($projectsLog->id ? __d('projects', 'Edit Log') : __d('projects', 'Add Log')),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => ['model' => $projectsLog, ['idPrefix' => 'projects-logs', 'type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['id'],
            ],
            'redirect' => [
                'method' => 'hidden',
                'parameters' => ['redirect', ['default' => base64_encode($this->getRequest()->referer() ?? '')]],
            ],
            'project_id' => [
                'method' => 'hidden',
                'parameters' => ['project_id'],
            ],
            'user_id' => [
                'method' => 'hidden',
                'parameters' => ['user_id'],
            ],

            'descript' => [
                'method' => 'control',
                'parameters' => [
                    'descript',
                    [
                        'type' => 'textarea',
                        'label' => false,
                    ],
                ],
            ],

            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('projects', 'Save'),
                    ['type' => 'submit'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
            ],
        ],
    ],
];
$this->Lil->jsReady('$("#descript").focus();');
echo $this->Lil->form($editForm, 'Projects.ProjectsLogs.edit');
echo $this->Html->script('/Documents/js/tinymce/tinymce.min.js');
?>
<script type="text/javascript">
    $(document).ready(function() {
        // HTML Wysiwyg Javascript Code
        //$('textarea#document-descript').textareaAutoSize();
        tinymce.init({
            selector:'textarea#projects-logs-descript',
            menubar:false,
            statusbar: false,
            toolbar: "undo redo | styleselect | bold italic underline subscript superscript | bullist numlist | indent outdent | pagebreak | pasteword table image",
            plugins: "autoresize table paste pagebreak image",
            table_toolbar: "tablecellprops | tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol",
            paste_auto_cleanup_on_paste : true,
            autoresize_max_height: 350,
            width: "700px"
        });
        tinymce.execCommand('mceFocus',false,'projects-logs-descript');

    })
</script>
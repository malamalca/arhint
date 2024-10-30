<?php

$attachedImage = false;
//data:image/png;base64,

if (!empty($template->body) && substr($template->body, 0, 5) == 'data:') {
    $attachedImage = true;
}

$templateEdit = [
    'title_for_layout' =>
        $template->id ? __d('documents', 'Edit Template') : __d('documents', 'Add Template'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form" id="edit-template">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$template, ['type' => 'file']],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'label' => __d('documents', 'Title') . ':',
                        'error' => __d('documents', 'Title is required.'),
                    ],
                ],
            ],
            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
                    'options' => [
                        'type' => 'radio',
                        'label' => false,
                        'options' => [
                            'body' => __d('documents', 'Body'),
                            'header' => __d('documents', 'Header'),
                            'footer' => __d('documents', 'Footer'),
                            'email' => __d('documents', 'Email'),
                        ],
                        'default' => 'body',
                    ],
                ],
            ],

            ////////////////////////////////////////////////////////////////////////////////////////
            'fs_layout_body_div_start' => sprintf('<div class="input-field%s">', $attachedImage ? ' invisible' : ''),
            'fs_layout_body_label' => sprintf('<label for="template-body" class="active">%s:</label><br />', __d('documents', 'Body')),

            'body' => [
                'method' => 'textarea',
                'parameters' => [
                    'field' => 'body', [
                        'id' => 'template-body',
                        'value' => $attachedImage ? '' : $template->body,
                    ],
                ],
            ],
            'fs_body_img' => !$attachedImage ? '' : sprintf(
                '<div id="body-image-div"><img src="%s" style="width: 400px" /></div>',
                $template->body
            ),
            'fs_body_remove' => !$attachedImage ? '' : sprintf(
                '<div id="body-remove-div"><a href="javascript:void(0);" id="body-remove">%s</a></div>',
                __d('documents', 'Remove image')
            ),
            'fs_layout_body_file_div_start' => '<div>',
            'body_file' => [
                'method' => 'file',
                'parameters' => [
                    'field' => 'body_file', ['id' => 'body-picker'],
                ],
            ],
            'fs_layout_body_file_div_end' => '</div>',
            'fs_layout_body_div_end' => '</div>',
            'fs_layout_end' => '</fieldset>',

            'main' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'main',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('documents', 'This is default template'),
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('documents', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($templateEdit, 'Documents.DocumentsTemplates.edit');
?>
<script type="text/javascript">
    $(".invisible textarea").hide();

    $(document).ready(function() {

        $("a#body-remove").click(function() {
            $("#body-image-div").remove();
            $("#body-remove-div").remove();
            $("#template-body").show();
        });


        $("#body-picker").change(function() {
            if ($(this).val()) {
                $('textarea#template-body').hide();
            } else {
                $('textarea#template-body').show();
            }
        });

        $("textarea").keydown(function(e) {
            if(e.keyCode === 9) { // tab was pressed
                // get caret position/selection
                var start = this.selectionStart;
                var end = this.selectionEnd;

                var $this = $(this);
                var value = $this.val();

                // set textarea value to: text before caret + tab + text after caret
                $this.val(value.substring(0, start)
                            + "\t"
                            + value.substring(end));

                // put caret at right position again (add one for the tab)
                this.selectionStart = this.selectionEnd = start + 1;

                // prevent the focus lose
                e.preventDefault();
            }
        });
    });
</script>

<?php

$attachedImage = false;
//data:image/png;base64,

if (!empty($template->body) && substr($template->body, 0, 5) == 'data:') {
    $attachedImage = true;
}

$templateEdit = [
    'title_for_layout' =>
        $template->id ? __d('lil_invoices', 'Edit Template') : __d('lil_invoices', 'Add Template'),
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
                        'label' => __d('lil_invoices', 'Title') . ':',
                        'error' => __d('lil_invoices', 'Title is required.'),
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
                            'body' => __d('lil_invoices', 'Body'),
                            'header' => __d('lil_invoices', 'Header'),
                            'footer' => __d('lil_invoices', 'Footer'),
                        ],
                    ],
                ],
            ],

            ////////////////////////////////////////////////////////////////////////////////////////
            'fs_layout_body_div_start' => sprintf('<div class="input-field%s">', $attachedImage ? ' invisible' : ''),
            'fs_layout_body_label' => sprintf('<label for="template-body" class="active">%s:</label>', __d('lil_invoices', 'Body')),
            'body' => [
                'method' => 'textarea',
                'parameters' => [
                    'field' => 'body', [
                        'id' => 'template-body',
                        'disabled' => (bool)$attachedImage,
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
                __d('lil_invoices', 'Remove image')
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
                        'label' => __d('lil_invoices', 'This is default template'),
                    ],
                ],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($templateEdit, 'LilInvoices.InvoicesTemplates.edit');
?>
<script type="text/javascript">
    $(".invisible textarea").hide();

    $(document).ready(function() {

        $("a#body-remove").click(function() {
            $("#body-image-div").remove();
            $("#body-remove-div").remove();
            $("#template-body").show().prop("disabled", "");
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

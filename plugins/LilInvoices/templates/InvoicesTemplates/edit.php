<?php

$attachedFooter = false;
if (!empty($template->footer) && substr($template->footer, 0, 2) == '{"') {
    $attachedFooter = json_decode($template->footer, true);
}

$templateEdit = [
    'title_for_layout' =>
        $template->id ? __d('lil_invoices', 'Edit Template') : __d('lil_invoices', 'Add Template'),
    'form' => [
        'pre' => '<div class="form" id="edit-template">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'class' => $this->Form,
                'method' => 'create',
                'parameters' => ['model' => $template, ['type' => 'file']],
            ],
            'id' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'class' => $this->Form,
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'title' => [
                'class' => $this->Form,
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
                'class' => $this->Form,
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
            'fs_layout_body_div_start' => '<div class="input ui-widget ui-textarea textarea">',
            'fs_layout_body_label' => sprintf('<label for="template-body">%s:</label>', __d('lil_invoices', 'Body')),
            'body' => [
                'class' => $this->Form,
                'method' => 'textarea',
                'parameters' => [
                    'field' => 'body', [
                        'id' => 'template-body',
                        'class' => $attachedFooter ? 'invisible' : '',
                        'disabled' => (bool)$attachedFooter,
                        'value' => $attachedFooter ? '' : $template->body,
                    ],
                ],
            ],
            'fs_body_img' => !$attachedFooter ? '' : sprintf(
                '<div id="body-image-div"><img src="data:%1$s;base64,%2$s" style="width: 400px" /></div>',
                $attachedFooter['type'],
                $attachedFooter['image']
            ),
            'fs_body_remove' => !$attachedFooter ? '' : sprintf(
                '<div id="body-remove-div"><a href="javascript:void(0);" id="body-remove">%s</a></div>',
                __d('lil_invoices', 'Remove image')
            ),
            'fs_layout_body_file_div_start' => '<div>',
            'body_file' => [
                'class' => $this->Form,
                'method' => 'file',
                'parameters' => [
                    'field' => 'body_file', ['id' => 'body-picker'],
                ],
            ],
            'fs_layout_body_file_div_end' => '</div>',
            'fs_layout_body_div_end' => '</div>',
            'fs_layout_end' => '</fieldset>',

            'main' => [
                'class' => $this->Form,
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
                'class' => $this->Form,
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Save'),
                ],
            ],
            'form_end' => [
                'class' => $this->Form,
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($templateEdit, 'LilInvoices.InvoicesTemplates.edit');
?>
<script type="text/javascript">
    $(".invisible").hide();

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

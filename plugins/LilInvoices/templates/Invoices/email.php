<?php

use Cake\Routing\Router;

$send_invoice = [
    'title_for_layout' => __d('lil_invoices', 'Email Invoice'),
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$email],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'to' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'to',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('lil_invoices', 'To') . ':',
                        'default' => $this->getRequest()->getQuery('to'),
                    ],
                ],
            ],
            'cc' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'cc',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('lil_invoices', 'CC') . ':',
                        'default' => $this->getRequest()->getQuery('cc'),
                    ],
                ],
            ],
            'cc_me' => empty($currentUser['email']) ? null : [
                'method' => 'control',
                'parameters' => [
                    'field' => 'cc_me',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('lil_invoices', 'Send CC to me ({0})', $currentUser['email']),
                    ],
                ],
            ],
            'subject' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'subject',
                    'options' => [
                        'type' => 'text',
                        'label' => __d('lil_invoices', 'Subject') . ':',
                        'default' => $this->getRequest()->getQuery('subject'),
                    ],
                ],
            ],
            'body' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'body',
                    'options' => [
                        'type' => 'textarea',
                        'label' => __d('lil_invoices', 'Body') . ':',
                        'default' => $this->getRequest()->getQuery('body'),
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('lil_invoices', 'Send'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

// add invoices to be send
$invoices_display = [];
$invoices_display[] = '<div class="ui-widget ui-text">';
$invoices_display[] = sprintf('<label>%1$s:</label>', __d('lil_invoices', 'Attachments'));
foreach ($attachments as $invoice_id => $invoice_title) {
    $invoices_display[] = sprintf(
        '<div class="email-attachment" id="email-attachment-%5$s">%2$s %1$s %3$s %4$s</div>',
        $invoice_title,
        $this->Html->image('/lil_invoices/img/attachment.png'),
        $this->Html->image('/lil_invoices/img/remove.gif', ['class' => 'remove-attachment']),
        $this->Form->hidden('invoices[]', [
            'value' => $invoice_id,
            'id' => 'attachment-' . $invoice_id,
        ]),
        $invoice_id
    );
}
$invoices_display[] = '</div>';

$this->Lil->insertIntoArray($send_invoice['form']['lines'], $invoices_display, ['after' => 'subject']);

echo $this->Lil->form($send_invoice, 'Invoices.email');

?>
<script type="text/javascript">
    $(document).ready(function() {
        $('.remove-attachment').click(function() {
            $('#email-attachment-' + $(this).siblings('input').val()).remove();
        });
    });
</script>

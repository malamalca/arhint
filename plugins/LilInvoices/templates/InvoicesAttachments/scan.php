<?php

use Cake\Routing\Router;

$attachmentScan = [
    'title_for_layout' => __d('lil_invoices', 'Scan an Attachment'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form"><div id="spinner" style="text-align: center;">' .
            $this->Html->image('LilInvoices.loading.gif') . '</div>',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$attachment, ['id' => 'ScanForm']],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referer', ['id' => 'referer', 'default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'invoice_id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'invoice_id', ['default' => $attachment->id]],
            ],
            'scanned' => [
                'method' => 'hidden',
                'parameters' => ['scanned', ['id' => 'scanned']],
            ],
            'scanned_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['scanned'],
            ],
            'submit' => [
                'method' => 'button',
                'parameters' => [
                    __d('lil_invoices', 'Scan a Document'),
                    ['id' => 'DoScan'],
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($attachmentScan, 'LilInvoices.InvoicesAttachments.scan');
?>

<script type="text/javascript">
    $(document).ready(function() {
        $("#DoScan").hide();

        var wsImpl = window.WebSocket || window.MozWebSocket;
        window.ws = new wsImpl("ws://localhost:8080/");

        ws.onmessage = function(e) {
            if (e.data == "cancel") {
                window.location = $("#referer").val();
                return;
            }
            $("#scanned").val(e.data);
            $("#ScanForm").submit();
            ws.send("done");
        };
        ws.onopen = function() {
            $("#DoScan").click(function(e) {
                $("#spinner").show();
                $("#DoScan").hide();
                ws.send("EE");

                e.preventDefault();
                return false;
            });

            $("#spinner").hide();
            $("#DoScan").show();
        };
        ws.onerror = function(e) {
        }
        ws.onclose = function() {
            window.location = $("#referer").val();
        };

    });
</script>

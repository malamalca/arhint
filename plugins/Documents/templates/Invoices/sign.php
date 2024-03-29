<?php
use Cake\I18n\DateTime;
use Cake\Routing\Router;

$this->set('head_for_layout', false);

if (empty($id)) {
    $action = ['action' => 'export.pdf', 'download' => 0, 'filter' => $this->getRequest()->getQuery('filter')];
    $name = __d('documents', 'Invoices Preview');

    $action = Router::url(['action' => 'export', 'invoices.pdf', '?' => $filter], true);
} else {
    if (empty($name)) {
        $name = $id;
    }
    $name = __d('documents', 'Sign Invoice #{0}', h($name));
    $action = Router::url(['action' => 'export', $id, $name . '.pdf'], true);
}

$document_preview = [
    'title_for_layout' => $name,
    'menu' => [
        'edit' => empty($id) ? null : [
            'title' => __d('documents', 'Back'),
            'visible' => true,
            'url' => [
                'action' => 'view',
                $id,
            ],
        ],
    ],
    'panels' => [
        'confirm' => [
            'params' => ['id' => 'SignPanel'],
            'form' => [
                'defaultHelper' => $this->Form,
                'lines' => [
                    'form_start' => [
                        'method' => 'create',
                        'parameters' => [$invoice, ['id' => 'DocumentSign']],
                    ],
                    'id' => [
                        'method' => 'hidden',
                        'parameters' => ['field' => 'id'],
                    ],
                    'sign_digest' => [
                        'method' => 'hidden',
                        'parameters' => ['field' => 'sign_cert', ['id' => 'digest', 'value' => $digest ?? '']],
                    ],
                    'sign_cert' => [
                        'method' => 'hidden',
                        'parameters' => ['field' => 'sign_cert', ['id' => 'cert']],
                    ],
                    'sign_cert_unlock' => [
                        'method' => 'unlockField',
                        'parameters' => ['sign_cert'],
                    ],
                    'sign_signature' => [
                        'method' => 'hidden',
                        'parameters' => ['field' => 'sign_signature', ['id' => 'signature']],
                    ],
                    'sign_signature_unlock' => [
                        'method' => 'unlockField',
                        'parameters' => ['sign_signature'],
                    ],
                    'sign_date' => [
                        'method' => 'control',
                        'parameters' => [
                            'field' => 'dat_sign',
                            'options' => [
                                'type' => 'datetime',
                                'label' => [
                                    'text' => __d('documents', 'Datetime') . ':',
                                    'class' => 'active',
                                ],
                                'default' => (new DateTime())->toDatetimeString(),
                                'readonly' => !$this->getCurrentUser()->hasRole('root'),
                            ],
                        ],
                    ],
                    'form_end' => [
                        'method' => 'button',
                        'parameters' => [
                            'Sign Document',
                        ],
                    ],
                ],
            ],
        ],
        'preview' => $this->getRequest()->is('ajax') ? null : sprintf('<br /><iframe id="invoice-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($document_preview, 'Documents.Invoices.sign');

$this->Html->script('/Documents/js/hwcrypto-legacy.js', ['block' => 'script']);
$this->Html->script('/Documents/js/hwcrypto.js', ['block' => 'script']);
$this->Html->script('/Documents/js/hex2base.js', ['block' => 'script']);

if (!$this->getRequest()->is('ajax')) {
    ?>
<script type="text/javascript">

    $(document).ready(function() {
        $("#invoice-view").height(window.innerHeight - $("#invoice-view").offset().top - 30);

        var signature = $("#signature").val();
        if (signature == "") {
            $("form#DocumentSign").on("submit", function(e) {
                e.preventDefault();

                if (!window.hwcrypto.use("auto")) {
                    alert("Selecting backend failed.");
                }

                var formElement = this;

                window.hwcrypto.getCertificate({lang: "en"}).then(
                    function(response) {
                        var cert = response;
                        $("#cert", formElement).val(hexToPem(response.hex));

                        $.post(
                            $(formElement).prop("action"),
                            $(formElement).serialize(),
                            function(html) {
                                $("div#SignPanel").replaceWith(html);

                                formElement = $("form#DocumentSign");
                                var digest = $("#digest", formElement).val();

                                window.hwcrypto.sign(cert, {type: "SHA-1", hex: digest}, {lang: "en"}).then(
                                    function(response) {
                                        $("#signature", formElement).val(hexToBase64(response.hex));
                                        $(formElement).submit();
                                    },
                                    function(err) {
                                        alert("sign() failed: " + err);
                                    }
                                );
                            }
                        );
                    },
                    function(err) {
                        alert("getCertificate() failed: " + err);
                    }
                );

                return false;
            });
        }
    });
</script>
    <?php
}
?>

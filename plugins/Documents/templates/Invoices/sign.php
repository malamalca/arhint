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
                    'sign_cert_select_label' => [
                        'method' => 'label',
                        'parameters' => [
                            'field' => 'sign_select_cert',
                            'text' => __d('documents', 'Select Certificate') . ':',
                        ],
                    ],
                    'sign_select_cert' => [
                        'method' => 'control',
                        'parameters' => [
                            'field' => 'sign_select_cert',
                            'options' => [
                                'type' => 'select',
                                'label' => false,
                                'class' => 'browser-default',
                            ],
                        ],
                    ],
                    'sign_select_cert_unlock' => [
                        'method' => 'unlockField',
                        'parameters' => ['sign_select_cert'],
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
                            __d('documents', 'Sign Document'),
                            ['type' => 'submit'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

echo $this->Lil->panels($document_preview, 'Documents.Invoices.sign');

?>
<script type="text/javascript">
    var SERVICE_URL = 'http://localhost:8082';
    var certificates = [];

    async function fetchCertificates() {
        // Fetch certificates from web service
        const response = await fetch(`${SERVICE_URL}/listCerts`, {
            method: 'GET',
            mode: 'cors',
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.error) {
            showOutput('Error: ' + data.error, 'error');
            return;
        }

        if (data.result) {
            certificates = data.result;

            // Populate the select dropdown
            certificates.forEach((cert, index) => {
                $("#sign-select-cert").append($("<option>", {
                    value: index,
                    text: cert.label
                }));
            });

            // Set the first certificate as default
            $("#cert").val(certificates[0].cert);

            // Handle certificate selection change
            $("#sign-select-cert").on("change", function() {
                const selectedIndex = $(this).val();
                $("#cert").val(certificates[selectedIndex].cert);
            });
        }
    }

    function showOutput(message, type) {
        // Simple output function, can be enhanced to show messages in the UI
        alert(`${type.toUpperCase()}: ${message}`);
    }

    async function sign(hash) {
        try {
            // Send sign request to web service
            const response = await fetch(`${SERVICE_URL}/sign`, {
                method: 'POST',
                mode: 'cors',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    hash: hash,
                    thumbprint: certificates[$("#sign-select-cert").val()].thumbprint
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.error) {
                showOutput('Signing Error: ' + data.error, 'error');
                return;
            }

            if (data.result) {
                let formElement = $("#DocumentSign");
                $("#signature", formElement).val(data.result);
                formElement.off("submit");
                formElement.submit();
            }
        } catch (error) {
            showOutput('Error: ' + error.message, 'error');
        }
    }

    fetchCertificates();

    $(document).ready(function() {
        var signature = $("#signature").val();
        if (signature == "") {
            $("form#DocumentSign").on("submit", function(e) {
                if (!confirm('<?= __d('documents', 'Are you sure you want to sign this document?'); ?>'))  {
                    return false;
                }

                let formElement = $("#DocumentSign");
                e.preventDefault();

                // First, get the digest from the server using public certificate
                $.post(
                    $(formElement).prop("action"),
                    $(formElement).serialize(),
                    function(data) {
                        if (data.error) {
                            showOutput('Error: ' + data.error, 'error');
                            return;
                        }
                        sign(data.digest);
                    }
                );

                return false;
            });
        }
    });
</script>

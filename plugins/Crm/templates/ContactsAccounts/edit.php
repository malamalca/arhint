<?php
use Cake\Core\Configure;

if ($account->id) {
    $title = __d('crm', 'Edit Account');
} else {
    $title = __d('crm', 'Add an Account');
}

$editForm = [
    'title_for_layout' => $title,
    'form' => [
        'pre' => '<div class="form">',
        'post' => '</div>',
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [$account, [
                    'id' => 'contacts-account-form',
                    'idPrefix' => 'contact-account',
                ]],
            ],
            'id' => [
                'method' => 'control',
                'parameters' => ['id', 'options' => ['type' => 'hidden']],
            ],
            'contact_id' => [
                'method' => 'control',
                'parameters' => ['contact_id', 'options' => ['type' => 'hidden']],
            ],
            'referer' => [
                'method' => 'control',
                'parameters' => ['referer', 'options' => ['type' => 'hidden']],
            ],

            'kind' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'kind',
                    'options' => [
                        'type' => 'select',
                        'label' => __d('crm', 'Kind') . ':',
                        'options' => Configure::read('Crm.accountTypes'),
                        'error' => [
                            'kindOccupied' => __d('crm', 'Entry of this type already exists.'),
                        ],
                    ],
                ],
            ],

            //'fs_main_start' => sprintf('<fieldset><legend>%s</legend>', __d('crm', 'Account')),
            'iban' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'iban',
                    'options' => ['label' => __d('crm', 'IBAN') . ':'],
                ],
            ],
            'bic' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'bic',
                    'options' => ['label' => __d('crm', 'BIC') . ':'],
                ],
            ],
            'bank' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'bank',
                    'options' => ['label' => __d('crm', 'Bank') . ':'],
                ],
            ],
            //'fs_main_end' => '</fieldset>',
            'primary' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'primary',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __d('crm', 'This is a primary account'),
                        'default' => false,
                    ],
                ],
            ],
            'submit' => [
                'method' => 'submit',
                'parameters' => ['label' => __d('crm', 'Save')],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($editForm, 'Crm.ContactsAccounts.edit');
?>
<script type="text/javascript">
    var banks = {
    <?php
        $bd = [];
    foreach ($banks = Configure::read('Crm.banks') as $bankId => $bank) {
        $bd[] = 'bd' . $bankId . ':"' . $bank['bic'] . '"';
        $bd[] = $bank['bic'] . ':"' . $bank['name'] . '"';
    }
        echo implode(', ', $bd);
    ?>
    };

    $(document).ready(function() {
        $('#contact-account-iban').blur(function() {
            var iban = $('#contact-account-iban').val().split(' ').join('');
            var bic = $('#contact-account-bic').val().trim();
            var bank = $('#contact-account-bank').val().trim();
            if ((iban.substr(0, 4) == 'SI56') && (bic == '') && typeof banks['bd'+iban.substr(4, 2)] != 'undefined') {
                bic = banks['bd'+iban.substr(4, 2)];
                $('#contact-account-bic').val(bic);
                if (bank == '' && typeof banks[bic] != 'undefined') {
                    $('#contact-account-bank').val(banks[bic]);
                }
            }
        });

        $("#contact-account-iban").focus();
    });
</script>

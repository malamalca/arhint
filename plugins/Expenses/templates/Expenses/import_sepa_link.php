<?php
use Cake\Routing\Router;

$payment_edit = [
    'title_for_layout' => __d('expenses', 'Link with Sepa Payment'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
            ],
            'payment_id' => [
                'method' => 'hidden',
                'parameters' => ['payment_id', ['id' => 'payment_id']],
            ],
            'payment_id_unlock' => [
                'method' => 'unlockField',
                'parameters' => ['payment_id'],
            ],
            'sepa_id' => [
                'method' => 'hidden',
                'parameters' => ['sepa_id', ['value' => $this->getRequest()->getQuery('id')]],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['referrer'],
            ],

            'title' => [
                'method' => 'control',
                'parameters' => ['title', [
                    'type' => 'text',
                    'label' => __d('expenses', 'Description') . ':',
                ]],
            ],

            'submit' => [
                'method' => 'submit',
                'parameters' => [
                    'label' => __d('expenses', 'Save'),
                ],
            ],
            'form_end' => [
                'method' => 'end',
                'parameters' => [],
            ],
        ],
    ],
];

echo $this->Lil->form($payment_edit, 'Expenses.Expenses.import_sepa_link');
?>
<script type="text/javascript">
    $(document).ready(function() {
        var elems = document.querySelectorAll('#title');

        var instances = M.AutocompleteAjax.init(elems, {
            source: "<?= Router::url(['controller' => 'Payments', 'action' => 'autocomplete'], true); ?>",
            onSelect: function(item) {
                $("#payment_id").val(item.id);
                $("button[type=submit").prop("disabled", false);
            },
            onSearch: function() {
                $("button[type=submit").prop("disabled", true);
            }
        });

        $("button[type=submit").prop("disabled", true);

    });

</script>

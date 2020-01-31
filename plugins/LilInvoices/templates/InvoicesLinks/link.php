<?php
use Cake\Core\Configure;
use Cake\Routing\Router;

$data = [
    'title_for_layout' =>
        __d('lil_invoices', 'Link Invoices'),
    'form' => [
        'defaultHelper' => $this->Form,
        'pre' => '<div class="form">',
        'post' => '</div>',
        'lines' => [
            'form_start' => [
                'method' => 'create',
                'parameters' => [null],
            ],
            'id' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'id'],
            ],
            'referer' => [
                'method' => 'hidden',
                'parameters' => ['field' => 'referer'],
            ],
            'invoice_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'invoice_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ],
            'title' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'title',
                    'options' => [
                        'autofocus',
                        'label' => __d('lil_invoices', 'Invoice') . ':',
                        'error' => __d('lil_invoices', 'Invoice is required.'),
                    ],
                ],
            ],
            'img-link' => $this->Html->image('/lil_invoices/img/link.gif', [
                'id' => 'image-check',
                'style' => 'display:none',
            ]),
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

echo $this->Lil->form($data, 'LilInvoices.InvoicesLinks.link');
?>
<script type="text/javascript">
    $(document).ready(function() {
        // move link to position after field
        var element = $('#image-check').detach();
        $('#title').parent('div').append(element);

        let el = $('#title').get(0);
        M.AutocompleteAjax.init(el, {
            source: '<?php echo Router::url([
                'plugin' => 'LilInvoices',
                'controller' => 'invoices',
                'action' => 'autocomplete',
            ], true); ?>',
            onSearch: function() {
                $('#invoice-id').val('');
                $('#image-check').hide();
            },
            onSelect: function(item) {
                $('#title').val(item.label);
                $('#invoice-id').val(item.value);
                $('#image-check').show();
            },
            onRenderItem: function(li, item) {
                let li2 = $( "<li>" )
                    .append( "<a><span class=\"invoice-autocomplete-no\">" + item.no + "</span> - " + item.label + " <span class=\"invoice-autocomplete-counter\">(" + item.counter + ")</span></a>" )
                    .get(0);

                return li2;
            }
        });
    });
</script>

<?php
use Cake\Routing\Router;

$data = [
    'title_for_layout' =>
        __d('documents', 'Link Documents'),
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
                'parameters' => ['referer', ['default' => Router::url($this->getRequest()->referer(), true)]],
            ],
            'document_id' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'document_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ],
            'model' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'model',
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
                        'label' => __d('documents', 'Document') . ':',
                        'error' => __d('documents', 'Document is required.'),
                    ],
                ],
            ],
            'img-link' => $this->Html->image('/documents/img/link.gif', [
                'id' => 'image-check',
                'style' => 'display:none',
            ]),
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

echo $this->Lil->form($data, 'Documents.DocumentsLinks.link');
?>
<script type="text/javascript">
    $(document).ready(function() {
        // move link to position after field
        var element = $('#image-check').detach();
        $('#title').parent('div').append(element);

        let el = $('#title').get(0);
        M.AutocompleteAjax.init(el, {
            source: '<?php echo Router::url([
                'plugin' => 'Documents',
                'controller' => 'Documents',
                'action' => 'autocomplete',
            ], true); ?>',
            onSearch: function() {
                $('#document-id').val('');
                $('#model').val('');
                $('#image-check').hide();
            },
            onSelect: function(item) {
                $('#title').val(item.label);
                $('#document-id').val(item.value);
                $('#model').val(item.model);
                $('#image-check').show();
            },
            onRenderItem: function(li, item) {
                let li2 = $( "<li>" )
                    .append( "<a><span class=\"document-autocomplete-no\">" + item.no + "</span> - " + item.label + " <span class=\"document-autocomplete-counter\">(" + item.counter + ")</span></a>" )
                    .get(0);

                return li2;
            }
        });
    });
</script>

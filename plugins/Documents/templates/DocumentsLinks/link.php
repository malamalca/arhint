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
        var DocumentsAutocompleteUrl = "<?php echo Router::url([
            'plugin' => 'Documents',
            'controller' => 'Documents',
            'action' => 'autocomplete',
        ], true); ?>";

        let el = $("#title").get(0);
        M.Autocomplete.init(el, {
            allowUnsafeHTML: true,
            onSearch: (text, autocomplete) => {
                $.get(DocumentsAutocompleteUrl + "?term=" + text).done(function(data) {
                    if (data.length > 1 || (data.length == 1 && text != data[0].title)) {
                        autocomplete.setMenuItems(data);
                        $("#document-id").val("");
                        $("#model").val("");
                        $("#title").parent("div").children("div.suffix").remove();
                    }
                });
            },
            onAutocomplete: (entries) => {
                if (entries.length == 1) {
                    let item = entries[0];
                    $("#title").val(item.title);
                    $("#document-id").val(item.id);
                    $("#model").val(item.model);
                    $("#title").parent("div").append("<div class='suffix'><i class='material-icons'>link</i></div>");
                }
            }
        });
    });
</script>

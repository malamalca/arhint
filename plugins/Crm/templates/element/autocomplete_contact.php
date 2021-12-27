<?php
    use Cake\Routing\Router;

if (empty($autocomplete_client)) {
    $autocomplete_client = [];
}
    $autocomplete_client = array_merge([
        'title' => '#DocumentClient',
        'id' => '#DocumentContactId',
        'image' => '#ImageContactCheck',
        'url' => ['plugin' => 'Crm', 'controller' => 'Contacts', 'action' => 'autocomplete'],
        'kind' => null,
    ], $autocomplete_client);
    ?>
$(document).ready(function() {
    $('<?php echo $autocomplete_client['title']; ?>').autocompleteajax({
        autoFocus: true,
        source: '<?php echo Router::url($autocomplete_client['url'] + ['kind' => $autocomplete_client['kind']]); ?>',
        onSearch: function() {
            $('<?php echo $autocomplete_client['id']; ?>').val('');
            $('<?php echo $autocomplete_client['image']; ?>').hide();
        },
        onSelect: function(item) {
            autocompleteClientSelect(item.id);
        }
    });
});

function autocompleteClientSelect(id, title) {
    $('<?php echo $autocomplete_client['id']; ?>').val(id);
    if (typeof title != 'undefined') {
        $('<?php echo $autocomplete_client['title']; ?>').val(title);
    }
    $('<?php echo $autocomplete_client['image']; ?>').show();
}

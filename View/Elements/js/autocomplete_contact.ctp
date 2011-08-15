<?php
	if (empty($autocomplete_client)) $autocomplete_client = array();
	$autocomplete_client = array_merge(
		array(
			'title' => '#InvoiceClient',
			'id' => '#InvoiceContactId',
			'image' => '#ImageContactCheck',
			'url' => array('admin' => true, 'plugin' => 'lil_crm', 'controller' => 'contacts', 'action' => 'autocomplete'),
			'kind' => null
		),
		$autocomplete_client
	);
?>
$('<?php echo $autocomplete_client['title']; ?>').autocomplete({
	source: '<?php echo $this->Html->url($autocomplete_client['url']+array('kind' => $autocomplete_client['kind'])); ?>', 
	search: function() {
		$('<?php echo $autocomplete_client['id']; ?>').val('');
		$('<?php echo $autocomplete_client['image']; ?>').hide();
	},
	select: function(event, ui) {
		if (ui.item) {
			autocompleteClientSelect(ui.item.id);
		}
	}
}).keyup(function() {
	if ($(this).val() === "") {
		$('<?php echo $autocomplete_client['id']; ?>').val('');
		$('<?php echo $autocomplete_client['image']; ?>').hide();
	}
});

function autocompleteClientSelect(id, title) {
	$('<?php echo $autocomplete_client['id']; ?>').val(id);
	if (typeof title != 'undefined') {
		$('<?php echo $autocomplete_client['title']; ?>').val(title);
	}
	$('<?php echo $autocomplete_client['image']; ?>').show();
}
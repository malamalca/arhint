<?php __d('lil_crm', 'Contact has been successfuly added.'); ?>
<script type="text/javascript">
	$(function() {
		autocompleteClientSelect(
			<?php echo json_encode($this->data['Contact']['id']) ?>,
			<?php echo json_encode($this->data['Contact']['title']) ?>
		);
		$("#dialog-form").dialog("close");
	});
</script>
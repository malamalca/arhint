<?php echo __d('lil_crm', 'Company has been successfuly added.'); ?>
<script type="text/javascript">
	$(function() {
		autocompleteClientSelect(<?php echo json_encode($this->data['Contact']['id']) ?>, <?php echo json_encode($this->data['Contact']['title']) ?>);
	});
</script>
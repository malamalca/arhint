<?php
	$this->set('title_for_layout', $this->Html->clean($counter['Counter']['title']));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_invoices', 'Add', true),
			'visible' => $counter['Counter']['active'],
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_invoices',
				'controller' => 'invoices',
				'action'     => 'add',
				'?'          => array('filter' => array('counter' => $counter['Counter']['id']))
			)
		)
	));
?>
<div class="invoices index">
<?php
if (empty($data)) {
	echo __d('lil_invoices', 'No invoices found.');
} else {
?>
<table width="100%" cellpadding="0" cellspacing="0" class="index" id="table-index-invoices">
	<thead>
		<tr>
			<th class="center"><?php echo __d('lil_invoices', 'Cnt'); ?></th>
			<th class="left"><?php echo __d('lil_invoices', 'No'); ?></th>
			<th class="center"><?php echo __d('lil_invoices', 'Issued'); ?></th>
			<th class="left"><?php echo __d('lil_invoices', 'Title'); ?></th>
			<th class="left"><?php echo __d('lil_invoices', 'Client'); ?></th>
			<th class="right"><?php echo __d('lil_invoices', 'Total'); ?></th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($data as $invoice) {
?>
		<tr>
			<td class="center nowrap small"><?php echo $this->Html->clean($invoice['Invoice']['counter']); ?></td>
			<td class="left nowrap strong"><?php echo $this->Html->link($invoice['Invoice']['no'], array('action' => 'view', $invoice['Invoice']['id'])); ?></td>
			<td class="center nowrap"><?php echo $this->LilDate->format($invoice['Invoice']['dat_issue']); ?></td>
			<td class="left small"><?php echo $this->Html->clean($invoice['Invoice']['title']); ?></td>
	        <td class="left small"><?php echo $this->Text->truncate($this->Html->clean($invoice['Client']['title']), 30); ?></td>
			<td class="right strong nowrap"><?php echo $this->LilFloat->money($invoice['Invoice']['total'], 2); ?></td>
		</tr>
<?php
	}
?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="5" class="right strong"><?php echo __d('lil_invoices', 'Total Sum'); ?>:</td>
			<td class="right strong"><?php echo $this->LilFloat->money($total_sum); ?></td>
		</tr>
	</tfoot>
</table>
<?php
	}
?>
<script type="text/javascript">
	$(document).ready(function() {
		$('#table-index-invoices').data(
			"settings",	{
				"aaSorting" : [[0, 'desc']],
				"aoColumnDefs": [
					//{ "bSortable": false, "aTargets": [ -1, -2, -3 ] }
					{ "sType": "lil_date", "aTargets": [ 2 ] },
					{ "sType": "lil_float", "aTargets": [ 5 ] },
				]
			}
		);
	});
</script>
</div>
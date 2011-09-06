<?php
/**
 * This is invoices_edit template file. 
 *
 * @copyright     Copyright 2008-2010, LilCake.net
 * @link          http://lilcake.net LilCake.net
 * @package       lil
 * @subpackage    lil.views.areas
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
	$invoice_edit = array(
		'title_for_layout' => sprintf(
			($this->Form->value('Invoice.id')) ? __d('lil_invoices', 'Edit an Invoice #%s') : __d('lil_invoices', 'Add an Invoice #%s'),
			($this->Form->value('Invoice.id')) ? $this->Form->value('Invoice.counter') : $counter['InvoicesCounter']['counter'] + 1
		),
		'form' => array(
			'pre' => '<div class="form">',
			'post' => '</div>',
			'lines' => array(
				'form_start' => array(
					'class'      => $this->LilForm,
					'method'     => 'create',
					'parameters' => array(
						'Invoice',
						'options' => array(
							'type' => 'file', 
							'url' => array(
								'action' => $this->Form->value('Invoice.id') ? 'edit' : 'add',
								'?' => array('filter' => array('counter' => $counter['InvoicesCounter']['id']))
						)))
				),
				'referer' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array('referer', 'options' => array('type' => 'hidden'))
				),
				'id' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array('id', 'options' => array('type' => 'hidden'))
				),
				'counter_id' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array('counter_id', 'options' => array('type' => 'hidden'))
				),
				'kind' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array('kind', 'options' => array('type' => 'hidden'))
				),
				'counter' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'counter',
						'options' => array(
							'type' => 'hidden',
							'default'  => $counter['InvoicesCounter']['counter'] + 1,
						)
					)
				),
				////////////////////////////////////////////////////////////////////////////////////
				'fs_basic_start' => '<fieldset>',
				'fs_basic_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Basics')),
				'contact_id' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'Invoice.contact_id',
						'options' => array('type' => 'hidden')
					)
				),
				'title' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'title',
						'options' => array(
							'label' => __d('lil_invoices', 'Title') . ':',
						)
					)
				),
				'client' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'client',
						'options' => array(
							'label' => __d('lil_invoices', 'Client') . ':',
							'div'   => array('class' => (!empty($this->validationErrors['Invoice']['contact_id']) ? 'error' : '')),
							'after' => ' '.$this->Html->image('/lil_crm/img/ico_contact_check.gif', array(
								'style' => ($this->Form->value('Invoice.contact_id')) ? '' : 'display: none;',
								'id' => 'ImageInvoiceContactCheck'
							))
						)
					)
				),
				'client_error' => empty($this->validationErrors['Invoice']['contact_id']) ? '' : sprintf(
					'<div class="error-message">%s</div>',
					__d('lil_invoices', 'Please choose a client.')
				),
				'client_hint' => sprintf('<div class="hint">%s</div>',
					$this->Lil->link(
						__d('lil_invoices', 'Start typing to select client. You can also add a [$1new company] or [$2new person].'),
						array(
							array(
								'admin'      => true,
								'plugin'     => 'lil_crm',
								'controller' => 'contacts',
								'action'     => 'add',
								'kind'       => 'C'
							),
							array(
								'onclick' => 'addCompany(); return false;'
							)
						),
						array(
							array(
								'admin'      => true,
								'plugin'     => 'lil_crm',
								'controller' => 'contacts',
								'action'     => 'add',
								'kind'       => 'T'
							),
							array(
								'onclick' => 'addPerson(); return false;'
							)
						)
					)
				),
				'no' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'no',
						'options' => array(
							'label' => __d('lil_invoices', 'Invoice no') . ':',
							'default'  => isset($counter['invoice_no']) ? $counter['invoice_no'] : '',
							'disabled' => !$this->Lil->currentUser->role('admin') && !empty($counter['InvoicesCounter']['mask']),
						)
					)
				),
				'fs_basic_end' => '</fieldset>', // basics
				
				////////////////////////////////////////////////////////////////////////////////////
				'fs_dates_start' => '<fieldset>',
				'fs_dates_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Dates')),
				'fs_dates_table_start' => '<table id="InvoiceDates"><tr><td>',
				'dat_issue' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'dat_issue',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_invoices', 'Date of issue') . ':',
							'onSelect' => 'applyDates'
						),
					)
				),
				'fs_dates_col1' => '</td><td>',
				'dat_service' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'dat_service',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_invoices', 'Service date') . ':',
						)
					)
				),
				'fs_dates_col2' => '</td><td>',
				'dat_expire' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'dat_expire',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_invoices', 'Expiration date') . ':',
						)
					)
				),
				'fs_dates_table_end' => '</td></tr></table>',
				'fs_dates_end' => '</fieldset>',
				
				////////////////////////////////////////////////////////////////////////////////////
				'fs_analytics_start' => '<fieldset>',
				'fs_analytics_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Analytics')),
				'analytics' => 
					(($counter['InvoicesCounter']['kind'] == 'received') ?
					array(
						'class'      => $this->LilForm,
						'method'     => 'input',
						'parameters' => array(
							'total',
							'options' => array(
								'type'  => 'float',
								'label' => __d('lil_invoices', 'Total') . ':',
							)
						)
					)
					:
					$this->element('edit_items')),
				'fs_analytics_end' => '</fieldset>',
				
				////////////////////////////////////////////////////////////////////////////////////
				'fs_attachments_start' => (($counter['InvoicesCounter']['kind'] == 'received') ? '<fieldset>' : ''),
				'fs_attachments_legend' => ($counter['InvoicesCounter']['kind'] == 'received') ? sprintf('<legend>%s</legend>', __d('lil_invoices', 'Archive')) : '',
				'file.name.0' => ($counter['InvoicesCounter']['kind'] != 'received') ? null : array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Attachment.0.filename',
						'options' => array(
							'type'  => 'file',
							'label'	=> false,
						)
					)
				),
				'file.model.0' => ($counter['InvoicesCounter']['kind'] != 'received') ? null : array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'Attachment.0.model',
						'options' => array(
							'type'  => 'hidden',
							'value'	=> 'Invoice',
						)
					)
				),
				'fs_attachments_end' => (($counter['InvoicesCounter']['kind'] == 'received') ? '</fieldset>' : ''),
				
				////////////////////////////////////////////////////////////////////////////////////
				'fs_descript_start' => '<fieldset>',
				'fs_descript_legend' => sprintf('<legend>%s</legend>', __d('lil_invoices', 'Description')),
				'description' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'descript',
						'options' => array(
							'type'    => 'textarea',
							'label'   => false,
							'default' => $counter['InvoicesCounter']['template_descript']
						)
					)
				),
				'fs_descript_end' => '</fieldset>',
				
				
				'submit' => array(
					'class'      => $this->LilForm,
					'method'     => 'submit',
					'parameters' => array(
						'label' => __d('lil_invoices', 'Save')
					)
				),
				'form_end' => array(
					'class'      => $this->LilForm,
					'method'     => 'end',
					'parameters' => array()
				),
			)
		)
	);
	
	App::uses('LilPluginRegistry', 'Lil.Lil'); $registry = LilPluginRegistry::getInstance();
	$invoice_edit = $registry->callPluginHandlers($this, 'form_edit_invoice', $invoice_edit);
	$this->set('title_for_layout', $invoice_edit['title_for_layout']);
	
	// form display begins
	echo $invoice_edit['form']['pre']; 
	foreach ($invoice_edit['form']['lines'] as $name => $line) {
		if (is_string($line)) {
			echo $line;
		} else if (!empty($line['class'])) {
			$parameters = array();
			if (!empty($line['parameters'])) {
				$parameters = (array)$line['parameters'];
			}
			if (is_object($line['class'])) {
				$use_object =& $line['class'];
			} else {
				$use_object =& ${$line['class']};
			}
			echo call_user_func_array(array($use_object, $line['method']), $parameters);
		}
	}
	echo $invoice_edit['form']['post']; 
?>

<div id="dialog-form"></div>



<script type="text/javascript">
	// constants for scripts
	<?php App::uses('Sanitize', 'Utility'); ?>
	
	var itemAutocompleteUrl = '<?php echo $this->Html->url(array('controller' => 'items', 'action' => 'autocomplete')); ?>';
	var toggleUnlinkItemConfirmation = '<?php echo Sanitize::escape(__d('lil_invoices', 'Are you sure you want to unlink item?')); ?>';
	var isUserAdmin = <?php echo $this->Lil->currentUser->role('admin') ? 'true' : 'false'; ?>;
	
	var popupCompanyTitle = '<?php echo __d('lil_invoices', 'Add a Company'); ?>';
	var popupCompanyUrl = '<?php echo $this->Html->url(array('plugin' => 'lil_crm', 'controller' => 'contacts', 'action' => 'add', 'kind' => 'C')); ?>';
	var popupPersonTitle = '<?php echo __d('lil_invoices', 'Add a Person'); ?>';
	var popupPersonUrl = '<?php echo $this->Html->url(array('plugin' => 'lil_crm', 'controller' => 'contacts', 'action' => 'add', 'kind' => 'T')); ?>';

	function addCompany() { openAddContactForm('C'); }
	function addPerson() { openAddContactForm('T'); }
	
	<?php
		$this->Html->script('/LilInvoices/js/invoice_edit', array('inline' => false));
		
		echo $this->element('js' . DS . 'autocomplete_contact', array(
			'autocomplete_client' => array(
				'image' => '#ImageInvoiceContactCheck'
			)
		));
	?>
</script>
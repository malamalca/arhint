<?php
/**
 * This is admin_edit template file. 
 *
 * @copyright     Copyright 2008-2010, LilCake.net
 * @link          http://lilcake.net LilCake.net
 * @package       lil
 * @subpackage    lil.views.areas
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
	$travel_edit = array(
		'title_for_layout' => sprintf(
			($this->Form->value('TravelOrder.id')) ? __d('lil_travel_orders', 'Edit Travel Order  #%s') : __d('lil_travel_orders', 'Add a Travel Order #%s'),
			($this->Form->value('TravelOrder.id')) ? $this->Form->value('TravelOrder.counter') : $counter['TravelOrdersCounter']['counter'] + 1
		),
		'form' => array(
			'pre' => '<div class="form">',
			'post' => '</div>',
			'lines' => array(
				'form_start' => array(
					'class'      => $this->LilForm,
					'method'     => 'create',
					'parameters' => array(
						'TravelOrder',
						'options' => array(
							'type' => 'file', 
							'url' => array(
								'action' => $this->Form->value('TravelOrder.id') ? 'edit' : 'add',
								'?' => array('filter' => array('counter' => $counter['TravelOrdersCounter']['id']))
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
				'counter' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'counter',
						'options' => array(
							'type' => 'hidden',
							'default'  => $counter['TravelOrdersCounter']['counter'] + 1,
						)
					)
				),
				////////////////////////////////////////////////////////////////////////////////////
				'tror_basic_start' => '<fieldset>',
				'tror_basic_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Basics')),
				'no' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'no',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Travel Order no') . ':',
							'default'  => isset($counter['order_no']) ? $counter['order_no'] : '',
							'disabled' => !$this->Lil->currentUser->role('admin') && !empty($counter['TavelOrdersCounter']['mask']),
						)
					)
				),
				'location' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'location',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Travel Order Signature Location') . ':',
							'div'   => array('id' => 'edit-travel-order-location')
						)
					)
				),
				'dat_order' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'dat_order',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_travel_orders', 'Travel Order Signature Date') . ':',
							'div'   => array('id' => 'edit-travel-order-dat_order')
						)
					)
				),
				'tror_basic_end' => '</fieldset>', // basics
				
				////////////////////////////////////////////////////////////////////////////////////
				'tror_travel_start' => '<fieldset>',
				'tror_travel_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Travel Details')),
				
				'employee_id' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'employee_id',
						'options' => $this->Lil->currentUser->role('admin') ?
							array(
								'type'    => 'select',
								'label'   => __d('lil_travel_orders', 'Traveller') . ':',
								'default' => $this->Lil->currentUser->get('id'),
								'options' => $users,
								'div'   => array('id' => 'edit-travel-order-employee_id')
							)
							:
							array(
								'type'    => 'hidden',
								'default' => $this->Lil->currentUser->get('id'),
							)
					)
				),
				'taskee' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'taskee',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Travel ordered by') . ':',
							'div'   => array('id' => 'edit-travel-order-taskee')
						)
					)
				),
				'descript' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'descript',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Travel Description') . ':',
							'default' => $counter['TravelOrdersCounter']['template_descript'],
							'div'   => array('id' => 'edit-travel-order-descript')
						)
					)
				),
				'description_hint' =>
					sprintf('<div class="hint">%s</div>', __d('lil_travel_orders', 'Enter travel route (eg. New York - Jersey - New York)')),
				'task' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'task',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Travel Task') . ':',
							'div'   => array('id' => 'edit-travel-order-task')
						)
					)
				),
				'dat_task' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'dat_task',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_travel_orders', 'Travel Task Date') . ':',
							'div'   => array('id' => 'edit-travel-order-dat_task')
						)
					)
				),
				'departure' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'departure',
						'options' => array(
							'type'     => 'datetime',
							'interval' => 5,
							'label'    => __d('lil_travel_orders', 'Travel Departure') . ':',
							'div'   => array('id' => 'edit-travel-order-departure')
						)
					)
				),
				'arrival' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'arrival',
						'options' => array(
							'type'     => 'datetime',
							'interval' => 5,
							'label'    => __d('lil_travel_orders', 'Travel Arrival') . ':',
							'div'   => array('id' => 'edit-travel-order-arrival')
						)
					)
				),
				'tror_travel_end' => '</fieldset>',
				
				////////////////////////////////////////////////////////////////////////////////////
				'tror_vehicle_start' => '<fieldset>',
				'tror_vehicle_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Vehicle')),
				'vehicle_title' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'vehicle_title',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Vehicle\'s name') . ':',
						)
					)
				),
				'vehicle_registration' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'vehicle_registration',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Vehicle\'s Registration') . ':',
						)
					)
				),
				'vehicle_owner' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'vehicle_owner',
						'options' => array(
							'label' => __d('lil_travel_orders', 'Vehicle\'s Owner') . ':',
						)
					)
				),
				'tror_vehicle_end' => '</fieldset>',
				
				////////////////////////////////////////////////////////////////////////////////////
				'tror_expenses_start' => '<fieldset>',
				'tror_expenses_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Travel Advance')),
				'payer_id' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'payer_id',
						'options' => array('type' => 'hidden')
					)
				),
				'payer' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'payer',
						'options' => array(
							'type'  => 'text',
							'label' => __d('lil_travel_orders', 'Travel\'s Payer') . ':',
							'div'   => array(
								'class' => (!empty($this->validationErrors['TravelOrder']['payer_id']) ? 'error' : ''),
								'id'    => 'edit-travel-order-payer'
							),
							'after' => ' '.$this->Html->image('/lil_crm/img/ico_contact_check.gif', array(
								'style' => ($this->Form->value('TravelOrder.payer_id')) ? '' : 'display: none;',
								'id'    => 'ImageTravelOrderContactCheck'
							))
						)
					)
				),
				'payer_error' => empty($this->validationErrors['TravelOrder']['payer_id']) ? '' : sprintf(
					'<div class="error-message">%s</div>',
					__d('lil_travel_orders', 'Please choose a payer.')
				),
				'payer_hint' => sprintf('<div class="hint">%s</div>',
					$this->Lil->link(
						__d('lil_travel_orders', 'Start typing to select payer. You can also add a [$1new company] or [$2new person].'),
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
				
				'advance' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'advance',
						'options' => array(
							'type'  => 'money',
							'label' => __d('lil_travel_orders', 'Travel Advance') . ':',
							'div'   => array('id' => 'edit-travel-order-advance')
						)
					)
				),
				'dat_advance' => array(
					'class'      => $this->LilForm,
					'method'     => 'input',
					'parameters' => array(
						'field' => 'dat_advance',
						'options' => array(
							'type'  => 'date',
							'label' => __d('lil_travel_orders', 'Date of Advance Payment') . ':',
							'div'   => array('id' => 'edit-travel-order-dat_advance')
						)
					)
				),
				'tror_expenses_end' => '</fieldset>',
				
				////////////////////////////////////////////////////////////////////////////////////
				'tror_analytics_start' => '<fieldset>',
				'tror_analytics_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Analytics')),
				'tror_analytics_data' => $this->element('edit_items'),
				'tror_analytics_end' => '</fieldset>',
				
				'tror_expenses2_start' => '<fieldset>',
				'tror_expenses2_legend' => sprintf('<legend>%s</legend>', __d('lil_travel_orders', 'Travel Expenses')),
				'tror_expenses2_data' => $this->element('edit_expenses'),
				'tror_expenses2_end' => '</fieldset>',
				
				
				'submit' => array(
					'class'      => $this->LilForm,
					'method'     => 'submit',
					'parameters' => array(
						'label' => __d('lil_travel_orders', 'Save')
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
	
	$travel_edit = $this->callPluginHandlers('form_edit_travel_order', $travel_edit);
	$this->Lil->form($travel_edit);
?>

<div id="dialog-form"></div>



<script type="text/javascript">
	// constants for scripts
	var isUserAdmin = <?php echo $this->Lil->currentUser->role('admin') ? 'true' : 'false'; ?>;
	
	var popupCompanyTitle = '<?php echo __d('lil_travel_orders', 'Add a Company'); ?>';
	var popupCompanyUrl = '<?php echo $this->Html->url(array('plugin' => 'lil_crm', 'controller' => 'contacts', 'action' => 'add', 'kind' => 'C')); ?>';
	var popupPersonTitle = '<?php echo __d('lil_travel_orders', 'Add a Person'); ?>';
	var popupPersonUrl = '<?php echo $this->Html->url(array('plugin' => 'lil_crm', 'controller' => 'contacts', 'action' => 'add', 'kind' => 'T')); ?>';

	function addCompany() { openAddContactForm('C'); }
	function addPerson() { openAddContactForm('T'); }
	
	<?php
		$this->Html->script('/LilTravelOrders/js/travel_order_edit', array('inline' => false));
		
		echo $this->element('js' . DS . 'autocomplete_contact', array(
			'autocomplete_client' => array(
				'image' => '#ImageTravelOrderContactCheck'
			)
		));
	?>
</script>
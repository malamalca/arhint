<?php
	$title = $this->Html->clean($data['Contact']['title']);
	
	$job = '';
	if ( !empty($data['Contact']['job'])) {
		$job .= ', '.$this->Html->clean($data['Contact']['job']);
	} else {
		if (!empty($data['Company']['title'])) {
			$job .= ', ' . __d('lil_crm', 'employed', true);
		}
	}
	if (!empty($data['Company']['title'])) {
		$job .= 
			' ' . __d('lil_crm', 'at', true) . ' ' .
			$this->Html->link($data['Company']['title'], array(
				'controller'=>'contacts', 
				'action'=>'view', 
				$data['Company']['id']
			));
	}
	if (!empty($job)) $title .= sprintf('<span class="light">%s</span>', $job);
	
	
	$contact_view = array(
		'title_for_layout' => $title,
		'menu' => array(
			'edit' => array(
				'title' => __d('lil_crm', 'Edit', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'edit',
					$data['Contact']['id']
				)
			),
			'delete' => array(
				'title' => __d('lil_crm', 'Delete', true),
				'visible' => true,
				'url'   => array(
					'admin'      => true,
					'plugin'     => 'lil_crm',
					'controller' => 'contacts',
					'action'     => 'delete',
					$data['Contact']['id']
				),
				'params' => array(
					'confirm' => __d('lil_crm', 'Are you sure you want to delete this contact?')
				)
			),
		),
		'panels' => array(
			'descript' => empty($data['Contact']['descript']) ? null : array(
				'params' => array('id' => 'contact-view-descript'),
				'text' => $this->Html->clean($data['Contact']['descript'])
			),
		)
	);
	
	$contact_view['panels']['addresses_h2'] = sprintf('<h2>%s</h2>', __d('lil_crm', 'Addresses'));
	foreach ($data['ContactsAddress'] as $address) {
		$contact_view['panels']['addresses']['lines'][] = array(
			'label' => ucfirst($address['kind'] ? $GLOBALS['address_types'][$address['kind']] : __d('lil_crm', 'other')) . ':',
			'text' => implode(', ', Set::filter(array(
					$address['street'],
					trim(implode(' ', array($address['zip'], $address['city']))),
					$address['country']
				))) . ' ' .
				$this->Lil->editLink(
					array(
						'controller' => 'contacts_addresses',
						'action'     => 'edit',
						$address['id']
					),
					array(
						'class' => 'edit-element edit-address'
					)
				) . ' ' .
				$this->Lil->deleteLink(
					array(
						'controller' => 'contacts_addresses',
						'action'     => 'delete',
						$address['id']
					),
					array(
						'class' => 'delete-element'
					)
				)
		);
	}
	$contact_view['panels']['addresses']['lines'][] = $this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_addresses',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-address')
		);
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	$contact_view['panels']['emails_h2'] = sprintf('<h2>%s</h2>', __d('lil_crm', 'Emails'));
	foreach ($data['ContactsEmail'] as $email) {
		$contact_view['panels']['emails']['lines'][] = array(
			'label' => ucfirst($GLOBALS['email_types'][$email['kind']]) . ':',
			'text' => $email['email'] . ' ' .
				$this->Lil->editLink(
					array(
						'controller' => 'contacts_emails',
						'action'     => 'edit',
						$email['id']
					),
					array(
						'class' => 'edit-element edit-email'
					)
				) . ' ' .
				$this->Lil->deleteLink(
					array(
						'controller' => 'contacts_emails',
						'action'     => 'delete',
						$email['id']
					),
					array(
						'class' => 'delete-element'
					)
				)
		);
	}
	$contact_view['panels']['emails']['lines'][] = $this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_emails',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-email')
		);
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	$contact_view['panels']['phones_h2'] = sprintf('<h2>%s</h2>', __d('lil_crm', 'Phones'));
	foreach ($data['ContactsPhone'] as $phone) {
		$contact_view['panels']['phones']['lines'][] = array(
			'label' => ucfirst($GLOBALS['phone_types'][$phone['kind']]) . ':',
			'text' => $phone['no'] . ' ' .
				$this->Lil->editLink(
					array(
						'controller' => 'contacts_phones',
						'action'     => 'edit',
						$phone['id']
					),
					array(
						'class' => 'edit-element edit-phone'
					)
				) . ' ' .
				$this->Lil->deleteLink(
					array(
						'controller' => 'contacts_phones',
						'action'     => 'delete',
						$phone['id']
					),
					array(
						'class' => 'delete-element'
					)
				)
		);
	}
	$contact_view['panels']['phones']['lines'][] = $this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_phones',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-phone')
		);
	
	////////////////////////////////////////////////////////////////////////////////////////////////
	$contact_view = $this->callPluginHandlers('lil_crm_view_contact', array('data' => $data, 'contents' => $contact_view));
	$this->Lil->panels($contact_view['contents']);

	////////////////////////////////////////////////////////////////////////////////////////////////
	$js_c = '$("%1$s").click(function(){popup(\'%2$s\', $(this).attr("href"), %3$s); return false;});';
	$this->Lil->jsReady(sprintf($js_c, '#add-address', __d('lil_crm', 'Add a new Address'), 450));
	$this->Lil->jsReady(sprintf($js_c, '#add-email', __d('lil_crm', 'Add a new Email'), 270));
	$this->Lil->jsReady(sprintf($js_c, '#add-phone', __d('lil_crm', 'Add a new Phone'), 270));
	
	$this->Lil->jsReady(sprintf($js_c, '.edit-address', __d('lil_crm', 'Edit Address'), 450));
	$this->Lil->jsReady(sprintf($js_c, '.edit-email', __d('lil_crm', 'Edit Email'), 270));
	$this->Lil->jsReady(sprintf($js_c, '.edit-phone', __d('lil_crm', 'Edit Phone'), 270));
	
	$this->Lil->jsReady('$(".view-panel").mouseover(function(){' .
		'$(".edit-element, .delete-element", this).show();}).mouseout(function(){$(".edit-element, .delete-element", this).hide();});');
	$this->Lil->jsReady('$(".edit-element").hide()');
	$this->Lil->jsReady('$(".delete-element").hide()');
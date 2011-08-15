<?php
	echo $this->element('head', array('contact' => $data));
	$this->set('main_menu', array(
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
	));
?>
<div class="index">
<?php
	if (!empty($data['Contact']['descript'])) {
		printf('<div id="contact-view-descript">%s</div>', $this->Html->clean($data['Contact']['descript']));
	}
	
	printf('<h2>%s</h2>', __d('lil_crm', 'Addresses'));
	foreach ($data['ContactsAddress'] as $address) {
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			ucfirst($address['kind'] ? $GLOBALS['address_types'][$address['kind']] : __d('lil_crm', 'other')) . ':',
			implode(', ', Set::filter(array(
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
	printf('<div class="view-panel">%1$s</div>',
		$this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_addresses',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-address')
		)
	);
	printf('<br />');
	
	printf('<h2>%s</h2>', __d('lil_crm', 'Emails'));
	foreach ($data['ContactsEmail'] as $email) {
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			ucfirst($GLOBALS['email_types'][$email['kind']]) . ':',
			$email['email'] . ' ' .
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
	printf('<div class="view-panel">%1$s</div>',
		$this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_emails',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-email')
		)
	);
	printf('<br />');
	
	printf('<h2>%s</h2>', __d('lil_crm', 'Phones'));
	foreach ($data['ContactsPhone'] as $phone) {
		printf('<div class="view-panel"><span class="label">%1$s</span>%2$s</div>',
			ucfirst($GLOBALS['phone_types'][$phone['kind']]) . ':',
			$phone['no'] . ' ' .
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
	printf('<div class="view-panel">%1$s</div>',
		$this->Html->link(
			__d('lil_crm', 'add'),
			array(
				'controller' => 'contacts_phones',
				'action'     => 'add',
				$data['Contact']['id']
			),
			array('id' => 'add-phone')
		)
	);
	printf('<br />');
	
	echo $this->element('js' . DS . 'popup_dialog');
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
?>
</div>
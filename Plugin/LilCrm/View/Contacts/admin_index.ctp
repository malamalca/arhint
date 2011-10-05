<?php
/**
 * LilPlan: The Project Management System
 *
 * @copyright     Copyright 2010, MalaMalca (http://malamalca.com)
 * @license       http://opensource.org/licenses/mit-license.php The MIT License
 */
	$this->set('title_for_layout', __d('lil_crm', 'Contact List'));
	$this->set('main_menu', array(
		'add' => array(
			'title' => __d('lil_crm', 'Add', true),
			'visible' => true,
			'url'   => array(
				'admin'      => true,
				'plugin'     => 'lil_crm',
				'controller' => 'contacts',
				'action'     => 'add',
				'kind'       => $filter['kind']
			)
		)
	));
?>

<div class="index">
<?php
	if (sizeof($contacts) == 0) {
?>
	<p><?php __d('lil_crm', 'Ups. No contacts found.'); ?></p>
	<br />
<?php
	} else {
?>
	<table class="index" width="100%" cellpadding="0" cellspacing="0" id="ContactsIndexTable">
		<thead>
			<tr>
				<th class="left"><?php echo ($filter['kind'] == 'T') ? __d('lil_crm', 'Name') : __d('lil_crm', 'Title');?></th>
				<th class="left"><?php echo __d('lil_crm', 'Email'); ?></th>
				<th class="left"><?php echo __d('lil_crm', 'Phone'); ?></th>
			</tr>
		</thead>
		<?php 
			foreach ($contacts as $contact) {
		?>
		<tr>
			<td>
			<?php
				echo $this->Html->link(
					$contact['Contact']['title'], 
					array(
						'action' => 'view',
						$contact['Contact']['id'],
						'kind' => $contact['Contact']['kind'],						
						strtotime($contact['Contact']['modified'])
					),
					array('class' => 'big')
				);
				
				if (!empty($contact['Contact']['company_id'])) {
					echo '<div>';
					if ( !empty($contact['Contact']['job'])) {
						echo $this->Html->clean($contact['Contact']['job']);
					} else {
						echo '<span class="light">' . __d('lil_crm', 'employed') . '</span>';
					}
					echo ' <span class="light">' . __d('lil_crm', 'at') . '</span> ';
					echo $this->Html->link(
						$contact['Company']['title'], 
						array(
							'action' => 'view',
							$contact['Company']['id'],
							strtotime($contact['Company']['modified'])
						)
					);
					echo '</div>';
				} else if (!empty($contact['Contact']['job'])) {
					printf('<div>%s</div>', $this->Html->clean($contact['Contact']['job']));
				}
				
				if (!empty($contact['PrimaryAddress'])) {
					printf('<div class="light small">%s</div>', implode(', ', Set::filter(array(
						$contact['PrimaryAddress']['street'],
						implode(' ', Set::filter(array(
							$contact['PrimaryAddress']['zip'],
							$contact['PrimaryAddress']['city'],
						))),
						$contact['PrimaryAddress']['country'],
					))));
				}
			?>
			</td>
			<td>
			<?php
				if (!empty($contact['ContactsEmail']) && is_array($contact['ContactsEmail']))
					foreach ($contact['ContactsEmail'] as $email) {
						echo $this->Html->link(
							$email['email'],
							'mailto:' . $this->Html->clean($email['email'])
						);
						echo ' <span class="small">';
						echo $GLOBALS['email_types'][$email['kind']];
						echo '</span><br />';
					}
				else echo "&nbsp;";
			?>
			</td>
			<td>
			<?php
				if (!empty($contact['ContactsPhone']) && is_array($contact['ContactsPhone']))
					foreach ($contact['ContactsPhone'] as $phone) {
						echo '<span class="strong">';
						echo $this->Html->clean($phone['no']);
						echo '</span>';
						echo ' <span class="small">';
						echo $GLOBALS['phone_types'][$phone['kind']];
						echo '</span><br />';
					}
				else echo "&nbsp;";
			?>
			</td>
		</tr>
		<?php } ?>
	</table>
	<?php
		} // sizeof($contacts)==0
	?>
</div>
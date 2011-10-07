<?php
class PaymentsExpense extends AppModel {

	var $name = 'PaymentsExpense';
	var $useTable = 'payments_expenses';
	var $belongsTo = array(
		'Payment' => array(
			'className' => 'LilExpenses.Payment'
		)
	);

}
<?php
$cakeDescription = __d('cake_dev', 'ARHIM d.o.o. :: seznam računov');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo $cakeDescription ?>:
		<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('cake.generic');

		printf($this->Html->css('/lil/css/Aristo/jquery-ui-1.8.7.custom') . PHP_EOL);
		printf($this->Html->css('/arhim/css/arhim') . PHP_EOL);
		
		printf($this->Html->script('/lil/js/jquery-1.7.1.min') . PHP_EOL);
		printf($this->Html->script('/lil/js/jquery-ui-1.8.16.custom.min') . PHP_EOL);
		printf($this->Html->script('/lil/js/lil_popups') . PHP_EOL);
	
		echo $scripts_for_layout;
		
		
	?>
</head>
<body>
	<div id="container">
		<div id="header">
			<h1><?php echo $this->Html->link($cakeDescription, 'http://www.arhim.si'); ?></h1>
		</div>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $content_for_layout; ?>

		</div>
		<div id="footer">
			<?php echo $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt'=> $cakeDescription, 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false)
				);
			?>
		</div>
	</div>
	<?php
		$this->Lil->outputPopups();
		echo $this->element('sql_dump');
	?>
	<script type="text/javascript">
	<?php
		echo $this->Lil->jsReadyOut();
	?>
	</script>
</body>
</html>
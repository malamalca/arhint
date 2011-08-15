<!DOCTYPE HTML>
<html>
<head>
<?php
printf($this->Html->charset() . PHP_EOL);
	printf('<title>%s</title>'  . PHP_EOL,
		strip_tags(
			implode(' :: ', array_merge(
				array(__d('lil', 'LilTimeTrack')),
				Set::filter(array($title_for_layout))
			))
		)
	);
	printf($this->Html->css('/lil_time_track/css/lil_time_track_registration') . PHP_EOL);
	printf($this->Html->script('/lil/js/jquery-1.5.1.min') . PHP_EOL);
?>
</head>
<body>
<div id="content"><div id="main"><?php print ($content_for_layout); ?></div></div>
</body>
</html>
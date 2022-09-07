<html>
<head>
<?= $this->Html->charset() ?>
</head>
<body>
    <div id="print-area">
<?php
	echo $this->fetch('content');
?>
    </div>
</body>
</html>
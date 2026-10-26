<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
<head>
    <title><?= $this->fetch('title') ?></title>
    <style>
<?= file_get_contents(WWW_ROOT . 'css' . DS . 'email.css') ?>
    </style>
</head>
<body>
    <?= $this->fetch('content') ?>
</body>
</html>

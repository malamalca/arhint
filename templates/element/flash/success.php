<?php
$class = 'message';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}


$this->Lil->jsReady(sprintf('new M.Toast({text: "%s", classes: "green"});', $message));
?>


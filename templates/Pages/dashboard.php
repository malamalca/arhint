<?php
    use Cake\Core\Configure;

    $this->set('pageTitle', Configure::read('App.title'));
    echo '<div class="row g-1">' . $this->Lil->panels($panels) . '</div>';
?>
<?php $this->Html->script('aiChat.js', ['block' => 'script']) ?>

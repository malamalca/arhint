<?php
    use Cake\Core\Configure;

    $this->set('pageTitle', Configure::read('App.title'));
    echo '<div class="row">' . $this->Lil->panels($panels) . '</div>';
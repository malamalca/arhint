<?php
    use Cake\Core\Configure;

    $this->set('pageTitle', Configure::read('App.title'));
    echo $this->Lil->panels($panels);
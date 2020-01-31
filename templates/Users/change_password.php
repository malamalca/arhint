<?php
    use Cake\Core\Configure;

    $this->set('title', __('Set new password for {0}', h($user->name)));
?>
<?php
    echo $this->Form->create($user);
    echo $this->Form->hidden('id');
    echo $this->Form->hidden('reset_key1', ['value' => '']);
    echo $this->Form->control('passwd', ['type' => 'password', 'label' => __('New Password') . ':', 'error' => __('Password is required, format must be valid.'), 'value' => '']);
    echo $this->Form->control('repeat_passwd', ['type' => 'password', 'label' => __('Repeat Password') . ':', 'error' => __('Passwords do not match.'), 'value' => '']);

    echo '<br />';
    echo $this->Form->submit(__('Change'));
    echo $this->Form->end();

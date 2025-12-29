<table class="index">
    <thead>
        <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('Action'); ?></th>
            <th><?php echo __('Details'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $log) : ?>
        <tr>
            <td><?php echo h($log->created->i18nFormat()); ?></td>
            <td>
                <?php
                if ($log->has('user')) {
                    echo $this->Html->link(
                        h($log->user->name),
                        ['controller' => 'Users', 'action' => 'view', $log->user->id],
                    );
                } else {
                    echo __('System');
                }
                ?>
            </td>
            <td><?php echo h($log->action); ?></td>
            <td><?php echo h($log->details); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
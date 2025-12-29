<table class="index LogsTable">
    <thead>
        <tr>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('Details'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $log) : ?>
        <tr>
            <td><?php echo h($log->created->i18nFormat()); ?></td>
            <td>
            <?php
            switch ($log->action) {
                case 'DocumentEmail':
                    echo '<div class="small" style="font-size: smaller;">' . __(
                        '{0} emailed this document to {1} with subject {2}.',
                        '<b>' . ($log->has('user') ? h($log->user->name) : __('System')) . '</b>',
                        '<b>' . h($log->data['to'] ?? '') . '</b>',
                        '<b>"' . h($log->data['subject'] ?? '') . '"</b>',
                    ) . '</div>';
                    break;
                default:
                    echo __('Action: {0}', h($log->action));
            }
            ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
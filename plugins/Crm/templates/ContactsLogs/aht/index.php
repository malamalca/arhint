<?php
if ($contactsLogs->count() > 0) {
    $this->Paginator->options([
        'url' => $sourceRequest ?? null
    ]);

    $logsTable = [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'ContactsLogsList', 'class' => 'striped',
        ],
        /*'head' => ['rows' => [['columns' => [
            'no' => [
                'parameters' => ['class' => 'left-align'],
                'html' => __d('documents', 'Documents'),
            ],
            'date' => [
                'parameters' => ['class' => 'center-align'],
                'html' => $this->Paginator->sort(
                    'dat_issue',
                    __d('documents', 'Date'),
                ),
            ],
        ]]]],*/
        'foot' => ['rows' => [['columns' => [
            'actions' => [
                'parameters' => ['class' => 'left-align', 'colspan' => 2],
                'html' => '<ul class="paginator">' .
                    $this->Paginator->numbers([
                        'first' => 1,
                        'last' => 1,
                        'modulus' => 3,
                    ]) .
                    '</ul>',
            ],
         ]]]],
    ];

    $total = 0;

    foreach ($contactsLogs as $log) {
        
        switch (strtoupper($log->kind)) {
            case 'L':
                $logIcon = 'person_outline';
                break;
            case 'E':
                $logIcon = 'mail_outline';
                break;
            default:
                $logIcon = 'chat_bubble_outline';
        }

        $logsTable['body']['rows'][]['columns'] = [
            'descript' =>
                sprintf(
                    '<div class="logs-header"><span class="log-kind" style="display: block; float: left;">%3$s</span>%2$s %1$s</div>',
                    $this->Time->i18nFormat($log->created),
                    $this->getCurrentUser()->hasRole('admin') || ($this->getCurrentUser()->id == $log->user_id) ?
                        $this->Html->link(
                            __d('crm', 'delete'),
                            ['controller' => 'ContactsLogs', 'action' => 'delete', $log->id]
                        ) . ' ' .
                        $this->Html->link(
                            __d('crm', 'edit'),
                            ['controller' => 'ContactsLogs', 'action' => 'edit', $log->id]
                        )
                        :
                        '',
                    '<i class="material-icons" style="height: 12px; font-size: 12px;">' . $logIcon . '</i>'
                ) .
                strip_tags($log->descript, ['a', 'strong', 'em', 'span', 'sub', 'sup', 'table', 'tr', 'td', 'p', 'pre', 'blockquote', 'img']),
        ];
    }

    echo $this->Lil->table($logsTable, 'Crm.ContactsLogs.Aht.index');
} else {
    echo '<div class="hint">' . __d('crm', 'No logs found.') . '</div>';
}

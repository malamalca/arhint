<?php
use Cake\I18n\Date;
use Projects\Lib\AuthorityWorkflow;

/**
 * @var \App\View\AppView $this
 * @var array<\Cake\Datasource\EntityInterface> $cases
 * @var array<string, mixed> $summary
 * @var array<\Cake\Datasource\EntityInterface> $reviews
 */
$controller = 'ProjectsAuthorities';
$canReview = $this->getCurrentUser()->hasRole('admin');
$today = Date::today()->format('Y-m-d');
$states = AuthorityWorkflow::states();
$rows = [];
foreach ($cases as $case) {
    $phases = $summary['rows'][$case->id];
    $columns = ['authority' => $this->Html->link($case->title, [
        'controller' => $controller, 'action' => 'view', $case->id,
    ])];
    $lastEvent = $phases['DGD']['last'];
    if ($lastEvent && $lastEvent->date_due && $lastEvent->kind !== 'request') {
        $overdue = $lastEvent->date_due->format('Y-m-d') < $today;
        $columns['authority'] .= '<div class="authority-deadline' . ($overdue ? ' overdue' : '') . '">' .
            '<strong>' . h(AuthorityWorkflow::kinds()[$lastEvent->kind]) . '</strong><br>' .
            h(__d('projects', 'Due')) . ': <strong>' . h($this->Time->i18nFormat(
                $lastEvent->date_due,
                'd. M. yyyy',
            )) . '</strong>' . ($overdue ? ' · ' . h(__d('projects', 'Overdue')) : '') . '</div>';
    }
    $status = $phases['status'];
    $columns['status'] = '<span class="authority-status authority-status-' . h($status) . '"' .
        ($status === 'review' ?
            ' title="' . h(__d('projects', 'Documents changed since confirmation. Check the response again.')) . '"' :
            '') . '>' . h($states[$status]) . '</span>';
    // The receipt is the response itself, not the latest thing that happened to the case.
    foreach (['DPP' => 'conditions', 'DGD' => 'opinion'] as $phase => $kind) {
        $received = $phases[$phase]['received'];
        $html = '';
        if ($received && $received->kind === $kind) {
            $firstLink = ($received->event_documents ?? [])[0] ?? null;
            if ($firstLink && $firstLink->document) {
                $date = $firstLink->document->dat_issue ? $this->Time->i18nFormat(
                    $firstLink->document->dat_issue,
                    'dd.MM.yyyy',
                ) : '—';
                $html = '<div class="authority-receipt"><strong>' . $this->Html->link($date, [
                    'plugin' => 'Documents', 'controller' => 'Documents', 'action' => 'view', $firstLink->document_id,
                ], ['title' => AuthorityWorkflow::kinds()[$kind]]) . '</strong></div>';
            }
        }
        $columns[$kind] = ['html' => $html, 'params' => ['class' => 'center-align']];
    }
    // Every open supplement request stays visible until it is answered.
    $requestHtml = '';
    foreach ($phases['DGD']['requests'] as $request) {
        $firstLink = ($request->event_documents ?? [])[0] ?? null;
        $overdue = $request->date_due && $request->date_due->format('Y-m-d') < $today;
        $caption = __d('projects', 'Due') . ': ' . ($request->date_due ?
            $this->Time->i18nFormat($request->date_due, 'dd.MM.yyyy') : '—');
        $link = $firstLink && $firstLink->document ? $this->Html->link($caption, [
            'plugin' => 'Documents', 'controller' => 'Documents', 'action' => 'view', $firstLink->document_id,
        ]) : h($caption);
        $requestHtml .= '<div class="authority-deadline' . ($overdue ? ' overdue' : '') . '"' .
            ($overdue ? ' title="' . h(__d('projects', 'Overdue')) . '"' : '') . '><strong>' . $link .
            '</strong></div>';
    }
    $columns['request'] = $requestHtml;
    $rows[] = ['columns' => $columns];
}

$notes = [];
if ($cases) {
    $line = __d('projects', 'Opinions confirmed: {0} of {1}', $summary['complete'], $summary['required']);
    if ($summary['exempt']) {
        $line .= ' · ' . __d('projects', 'Not required: {0}', $summary['exempt']);
    }
    $notes[] = '<strong>' . h($line) . '</strong>';
    if ($summary['missing']) {
        $notes[] = h(__d('projects', 'Missing: {0}', implode(', ', $summary['missing'])));
    }
    if ($summary['blocked']) {
        $notes[] = h(__d('projects', 'Responses or supplements are still outstanding.'));
    }
    if (!$summary['determined']) {
        $notes[] = h(__d('projects', 'An authority contact is not available.'));
    }
    if ($summary['ready']) {
        $notes[] = '<strong>' . h(__d('projects', 'Ready for submission to UE')) . '</strong>';
    } elseif ($summary['collected']) {
        $notes[] = h(__d('projects', 'All opinions are confirmed. Awaiting confirmation of readiness.'));
    }
}
?>
<div class="projects-authorities">
    <?php if ($notes) : ?>
        <div class="authority-summary">
            <?php foreach ($notes as $note) : ?>
                <p><?= $note ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?= $this->Lil->table([
        'params' => ['class' => 'striped', 'id' => 'projects-authorities'],
        'head' => ['rows' => [['columns' => [
            'authority' => __d('projects', 'Authority'),
            'status' => __d('projects', 'Status'),
            'conditions' => ['html' => __d('projects', 'Project conditions'), 'params' => ['class' => 'center-align']],
            'opinion' => ['html' => __d('projects', 'Opinion'), 'params' => ['class' => 'center-align']],
            'request' => __d('projects', 'Supplement requested'),
        ]]]],
        'body' => ['rows' => $rows],
    ]) ?>
    <?php if (!$cases) : ?>
        <p><?= h(__d('projects', 'No authority cases recorded.')) ?></p>
    <?php endif; ?>
    <?php if ($canReview) : ?>
        <?php
        $reviewActions = [
            'ready' => __d('projects', 'Confirm readiness for UE'),
            'revoke' => __d('projects', 'Revoke readiness'),
        ];
        $reviewUrl = ['controller' => $controller, 'action' => 'review', $project->id];
        $reviewAllowed = [
            'ready' => $summary['collected'] && !$summary['ready'],
            'revoke' => $summary['ready'],
        ];
        ?>
        <?php foreach ($reviewActions as $kind => $label) : ?>
            <?php if ($reviewAllowed[$kind]) : ?>
                <?= $this->Form->create(null, ['url' => $reviewUrl]) ?>
                <?= $this->Form->hidden('snapshot_hash', ['value' => $summary['snapshotHash']]) ?>
                <?= $this->Form->hidden('kind', ['value' => $kind]) ?>
                <?= $this->Form->button($label, ['type' => 'submit', 'class' => 'btn-small filled']) ?>
                <?= $this->Form->end() ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($reviews) : ?>
        <p><strong><?= h(__d('projects', 'Review history')) ?></strong></p>
        <?php
        $labels = [
            'ready' => __d('projects', 'Ready for submission to UE'),
            'revoke' => __d('projects', 'Submission readiness revoked'),
        ];
        ?>
        <?php foreach ($reviews as $review) : ?>
            <?php if (isset($labels[$review->kind])) : ?>
                <?php $author = $review->user->name ?? ''; ?>
                <p><?= h((string)$review->created . ' · ' . $author . ' · ' . $labels[$review->kind]) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

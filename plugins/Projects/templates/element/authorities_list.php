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
$canEdit = $this->getCurrentUser()->hasRole('editor');
$canSort = $canEdit && count($cases) > 1;
$addEventLabel = __d('projects', 'Record procedure event');
$rows = [];
foreach ($cases as $case) {
    $phases = $summary['rows'][$case->id];
    $handle = $canSort ?
        '<span class="authority-handle" title="' . h(__d('projects', 'Drag to change the order')) . '">&#8942;</span>' :
        '';
    $columns = ['authority' => $handle . $this->Html->link($case->title, [
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
    $columns['status'] = '<span class="authority-status authority-status-' . h($status) . '">' .
        h($states[$status]) . '</span>';
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
    if ($canEdit) {
        $columns['actions'] = ['html' => $this->Html->link('+', [
            'controller' => $controller, 'action' => 'event', $case->id,
        ], [
            'class' => 'btn-small tonal btn-add-authority-event',
            'title' => $addEventLabel,
            'data-title' => $case->title . ' · ' . $addEventLabel,
        ]), 'params' => ['class' => 'right-align']];
    }
    $rowParams = $canSort ? ['params' => ['data-case-id' => $case->id, 'draggable' => 'true']] : [];
    $rows[] = ['columns' => $columns] + $rowParams;
}

$notes = [];
if ($cases) {
    if ($summary['blocked']) {
        $notes[] = h(__d('projects', 'Responses or supplements are still outstanding.'));
    }
    if (!$summary['determined']) {
        $notes[] = h(__d('projects', 'An authority contact is not available.'));
    }
    if ($summary['ready']) {
        $notes[] = '<strong>' . h(__d('projects', 'Ready for submission to UE')) . '</strong>';
    } elseif ($summary['collected']) {
        $notes[] = h(__d('projects', 'All opinions have been received. Awaiting confirmation of readiness.'));
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
        'params' => ['class' => 'striped', 'id' => 'projects-authorities'] + ($canSort ? [
            'data-sort-url' => $this->Url->build([
                'controller' => $controller, 'action' => 'sortCases', $project->id,
            ]),
        ] : []),
        'head' => ['rows' => [['columns' => [
            'authority' => __d('projects', 'Authority'),
            'status' => __d('projects', 'Status'),
            'conditions' => ['html' => __d('projects', 'Project conditions'), 'params' => ['class' => 'center-align']],
            'opinion' => ['html' => __d('projects', 'Opinion'), 'params' => ['class' => 'center-align']],
            'request' => __d('projects', 'Supplement requested'),
        ] + ($canEdit ? ['actions' => ''] : [])]]],
        'body' => ['rows' => $rows],
    ]) ?>
    <?php if (!$cases) : ?>
        <p><?= h(__d('projects', 'No authority cases recorded.')) ?></p>
    <?php endif; ?>
    <?php if ($canEdit && $cases) : ?>
        <script>
        $(document).ready(function() {
            $('#projects-authorities .btn-add-authority-event').each(function() {
                $(this).modalPopup({
                    title: $(this).data('title'),
                    processSubmit: true,
                    onJson: function() { window.location.reload(); },
                    onOpen: function(popup) { $('#authority-event-kind', popup).focus(); }
                });
            });
        });
        </script>
    <?php endif; ?>
    <?php if ($canSort) : ?>
        <?php
        $sortError = json_encode(
            __d('projects', 'The order could not be saved.'),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
        ?>
        <script>
        (function() {
            var table = document.getElementById('projects-authorities');
            if (!table || !table.dataset.sortUrl) {
                return;
            }
            var body = table.tBodies[0];
            var errorText = <?= $sortError ?>;
            var dragged = null;
            var before = [];
            function rows() {
                return Array.prototype.slice.call(body.rows);
            }
            function restore() {
                before.forEach(function(row) {
                    body.appendChild(row);
                });
            }
            function save() {
                var token = document.querySelector('meta[name="csrfToken"]');
                var data = rows().map(function(row) {
                    return 'order[]=' + encodeURIComponent(row.dataset.caseId);
                }).join('&');
                fetch(table.dataset.sortUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': token ? token.getAttribute('content') : ''
                    },
                    body: data
                }).then(function(response) {
                    if (!response.ok) {
                        throw new Error(response.status);
                    }
                }).catch(function() {
                    restore();
                    if (window.M && M.toast) {
                        M.toast({html: errorText});
                    } else {
                        alert(errorText);
                    }
                });
            }
            body.addEventListener('dragstart', function(event) {
                dragged = event.target.closest ? event.target.closest('tr') : null;
                if (!dragged) {
                    return;
                }
                before = rows();
                dragged.classList.add('authority-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', dragged.dataset.caseId);
            });
            body.addEventListener('dragover', function(event) {
                if (!dragged) {
                    return;
                }
                event.preventDefault();
                var row = event.target.closest('tr');
                if (!row || row === dragged) {
                    return;
                }
                var box = row.getBoundingClientRect();
                body.insertBefore(dragged, event.clientY > box.top + box.height / 2 ? row.nextSibling : row);
            });
            body.addEventListener('drop', function(event) {
                event.preventDefault();
            });
            body.addEventListener('dragend', function() {
                if (!dragged) {
                    return;
                }
                dragged.classList.remove('authority-dragging');
                dragged = null;
                var changed = rows().some(function(row, index) {
                    return row !== before[index];
                });
                if (changed) {
                    save();
                }
            });
        })();
        </script>
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

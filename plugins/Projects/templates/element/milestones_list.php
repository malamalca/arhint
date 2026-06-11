<?php
declare(strict_types=1);

use App\Filter\Filter;
use Cake\Routing\Router;

// Open milestones are shown by default; toggling the active counter falls back
// to "all".
$currentMs = $this->getRequest()->getQuery('ms', 'open');
?>
<?= $this->Form->create(null, [
    'type' => 'post',
    'url' => ['controller' => 'ProjectsMilestones', 'action' => 'bulk'],
]) ?>
<?= $this->Form->hidden('redirect', ['value' => Router::url(null, true)]) ?>
<div id="panel-filter">
    <div class="checkbox"><input type="checkbox" id="select-all-milestones" /></div>
    <div id="panel-counters">
        <?= $this->Html->link(
            h(__d('projects', 'Open')) . sprintf('<span class="badge">%d</span>', $project->milestones_open),
            [$project->id, '?' => ['tab' => 'milestones', 'ms' => $currentMs === 'open' ? 'all' : 'open']],
            ['class' => ($currentMs === 'open' ? 'active' : ''), 'escape' => false]
        ) ?>
        <?= $this->Html->link(
            h(__d('projects', 'Closed')) . sprintf('<span class="badge">%d</span>', $project->milestones_done),
            [$project->id, '?' => ['tab' => 'milestones', 'ms' => $currentMs === 'closed' ? 'all' : 'closed']],
            ['class' => ($currentMs === 'closed' ? 'active' : ''), 'escape' => false]
        ) ?>
    </div>
    <div id="panel-actions" style="display:none;">
        <?= $this->Form->button(__d('projects', 'Mark as done'), [
            'type' => 'submit',
            'name' => 'action',
            'value' => 'done',
            'confirm' => __d('projects', 'Are you sure you want to mark the selected milestones as done?'),
        ]) ?>
        <?= $this->Form->button(__d('projects', 'Delete'), [
            'type' => 'submit',
            'name' => 'action',
            'value' => 'delete',
            'confirm' => __d('projects', 'Are you sure you want to delete the selected milestones? This action cannot be undone.'),
        ]) ?>
    </div>
</div>
<div id="panel-list">
<?php foreach ($milestones as $milestone) :
    $completedPercent = $milestone->tasks_done + $milestone->tasks_open > 0 ?
        round($milestone->tasks_done / ($milestone->tasks_done + $milestone->tasks_open), 2) * 100 : 0;
    ?>
    <div class="panel-row<?= $milestone->date_complete ? ' done' : '' ?>">
        <div class="checkbox"><?= $this->Form->checkbox('ids[]', ['value' => $milestone->id, 'hiddenField' => false]) ?></div>
        <div class="title">
            <?= $this->Html->link(h($milestone->title), [
                'controller' => 'ProjectsTasks',
                'action' => 'index',
                $milestone->project_id,
                '?' => ['q' => 'milestone:' . Filter::escapeQueryArgument($milestone->title)]]) ?>
            <div class="details">
                <span><?= $this->Html->image('/projects/img/calendar-16.svg') ?><?= __d('projects', 'Due') ?>: <?= $milestone->date_due ? h($milestone->date_due->nice()) : __d('projects', 'not set') ?></span>
                <span><?= $this->Html->image('/projects/img/clock-16.svg') ?><?= __d('projects', 'Last updated') ?> <?= h($milestone->modified->timeAgoInWords()) ?></span>
                <?php if ($milestone->date_complete) : ?>
                    <span><?= $this->Html->image('/projects/img/issue-closed-16.svg') ?><?= __d('projects', 'Completed') ?> <?= h($milestone->date_complete->nice()) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="milestone-progress">
            <div class="bar" style="width: 100%;">
                <div class="completed" style="width: <?= $completedPercent ?>%;"></div>
            </div>
            <div class="labels">
                <span><strong><?= $completedPercent ?> %</strong> <?= __d('projects', 'complete') ?></span>
                <span><a href="<?= $this->Url->build([
                    'controller' => 'ProjectsTasks',
                    'action' => 'index',
                    $milestone->project_id,
                    '?' => [
                        'q' => 'milestone:' . Filter::escapeQueryArgument($milestone->title) . ' status:open',
                    ],
                ]) ?>"><strong><?= $milestone->tasks_open ?></strong> <?= __d('projects', 'open') ?></a></span>
                <span><a href="<?= $this->Url->build([
                    'controller' => 'ProjectsTasks',
                    'action' => 'index',
                    $milestone->project_id,
                    '?' => [
                        'q' => 'milestone:' . Filter::escapeQueryArgument($milestone->title) . ' status:closed',
                    ],
                ]) ?>"><strong><?= $milestone->tasks_done ?></strong> <?= __d('projects', 'closed') ?></a></span>
            </div>
            <div class="actions">
                <?php
                if ($this->getCurrentUser()->can('edit', $milestone)) {
                    if (!$milestone->date_complete) {
                        echo $this->Html->link(
                            __d('projects', 'Add Task'),
                            [
                                'controller' => 'ProjectsTasks',
                                'action' => 'edit',
                                '?' => [
                                    'project' => $milestone->project_id,
                                    'milestone' => $milestone->id,
                                    'redirect' => Router::url(),
                                ],
                            ],
                            ['class' => 'btn-small text btn-add-task', 'onclick' => 'popup(); return false;'],
                        );
                        echo $this->Html->link(
                            __d('projects', 'Edit'),
                            [
                                'controller' => 'ProjectsMilestones',
                                'action' => 'edit',
                                $milestone->id,
                                '?' => ['redirect' => Router::url()],
                            ],
                            ['class' => 'btn-small text btn-edit-milestone'],
                        );
                        echo $this->Html->link(
                            __d('projects', 'Close'),
                            [
                                'controller' => 'ProjectsMilestones',
                                'action' => 'close',
                                $milestone->id,
                            ],
                            ['class' => 'btn-small text', 'confirm' => __d('projects', 'Are you sure?')],
                        );
                    } else {
                        echo $this->Html->link(
                            __d('projects', 'Reopen'),
                            [
                                'controller' => 'ProjectsMilestones',
                                'action' => 'reopen',
                                $milestone->id,
                            ],
                            ['class' => 'btn-small text'],
                        );
                    }
                }
                ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?= $this->Form->end() ?>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        function updateMilestonesActions() {
            var $boxes = $("#panel-list div.panel-row div.checkbox input");
            var anyChecked = false;
            var allChecked = $boxes.length > 0;
            $boxes.each(function() {
                anyChecked = anyChecked || $(this).prop("checked");
                allChecked = allChecked && $(this).prop("checked");
            });

            $("#select-all-milestones").prop("checked", allChecked);
            if (!allChecked && anyChecked) {
                $("#select-all-milestones").addClass("somechecked");
            } else {
                $("#select-all-milestones").removeClass("somechecked");
            }

            if (anyChecked) {
                $("#panel-actions").show();
                $("#panel-counters").hide();
            } else {
                $("#panel-actions").hide();
                $("#panel-counters").show();
            }
        }

        $("#select-all-milestones").on("change", function() {
            $("#panel-list div.panel-row div.checkbox input").prop("checked", $(this).prop("checked"));
            updateMilestonesActions();
        });
        $("#panel-list div.panel-row div.checkbox input").on("change", updateMilestonesActions);
    });
</script>

<?php

use Cake\Routing\Router;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Projects\Filter\ProjectsTasksFilter;

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

$filter = new ProjectsTasksFilter('');

$dropdownEditTaskPopup = ['items' => [
    !$this->getCurrentUser()->can('edit', $task) ? null : [
        'title' => '<i class="material-icons tiny">edit</i>' . h(__d('projects', 'Edit')),
        'url' => ['action' => 'edit', '__id__', '?' => ['redirect' => Router::url(null, true)]],
        'params' => ['class' => 'nowrap', 'id' => 'dropdown-item-edit'],
    ],
]];
$dropdownEditTaskPopup = $this->Lil->popup('edittask', $dropdownEditTaskPopup, true);

$dropdownEditCommentPopup = ['items' => [
    !$this->getCurrentUser()->can('edit', $comment) ? null : [
        'title' => '<i class="material-icons tiny">edit</i>' . h(__d('projects', 'Edit')),
        'url' => ['controller' => 'ProjectsTasksComments', 'action' => 'edit', '__id__', '?' => ['redirect' => Router::url(null, true)]],
        'params' => ['class' => 'nowrap', 'id' => 'dropdown-item-edit'],
    ],
    !$this->getCurrentUser()->can('delete', $comment) ? null : [
        'title' => '<i class="material-icons tiny">delete</i>' . h(__d('projects', 'Delete')),
        'url' => ['controller' => 'ProjectsTasksComments', 'action' => 'delete', '__id__', '?' => ['redirect' => Router::url(null, true)]],
        'params' => [
            'class' => 'nowrap',
            'id' => 'dropdown-item-delete',
            'data-confirm' => __d('projects', 'Are you sure you want to delete this comment?'),
        ],
    ],
]];
$dropdownEditCommentPopup = $this->Lil->popup('editcomment', $dropdownEditCommentPopup, true);

$addCommentForm = [
    'form' => [
        'defaultHelper' => $this->Form,
        'lines' => [
            'form_start' => ['method' => 'create', 'parameters' => [
                $comment,
                ['url' => ['controller' => 'ProjectsTasksComments', 'action' => 'edit']],
            ]],
            'kind' => ['method' => 'hidden', 'parameters' => ['kind']],
            'task_id' => ['method' => 'hidden', 'parameters' => ['task_id']],
            'user_id' => ['method' => 'hidden', 'parameters' => ['user_id']],
            'redirect' => ['method' => 'hidden', 'parameters' => ['redirect', ['value' => Router::url(null, true)]],
            ],
            'descript' => [
                'method' => 'control',
                'parameters' => ['descript', ['type' => 'textarea', 'label' => false]],
            ],
            'submit' => ['method' => 'submit', 'parameters' => ['label' => __d('projects', 'Save')]],
            'form_end' => ['method' => 'end', 'parameters' => []],
        ],
    ],
];

$taskView = [
    'title_for_layout' => $task->title . ' <span class="task-no">#' . $task->no . '</span>',
    'entity' => $task,
    'pre' => '<div id="task-view">',
    'post' => '</div>' . $dropdownEditTaskPopup . $dropdownEditCommentPopup,
    'panels' => [
        'sidebar' => '<div id="task-view-sidebar">' .
            '<p><span class="label">' . __d('projects', 'Status') . ':</span><span class="value">' . h($task->status) . '</span></p>' .
            '<p><span class="label">' . __d('projects', 'Project') . ':</span>' .
                $this->Html->link(
                    (string)$task->project,
                    [ 'action' => 'index', $task->project->id],
                    ['class' => 'value'],
                ) .
            '</p>' .
            '<p><span class="label">' . __d('projects', 'Milestone') . ':</span>' .
                $this->Html->link(
                    (string)$task->milestone,
                    ['action' => 'index', $task->project->id, '?' => ['q' => $filter->buildQuery('milestone', $task->milestone->title)]],
                    ['class' => 'value'],
                ) .
            '</p>' .
            '<p><span class="label">' . __d('projects', 'Owner') . ':</span><span class="value">' . h($users[$task->user_id]) . '</span></p>' .
            '<p><span class="label">' . __d('projects', 'Assigned To') . ':</span><span class="value">' . h($users[$task->assigned_user_id] ?? __d('projects', 'N/A')) . '</span></p>' .
            '<p><span class="label">' . __d('projects', 'Due Date') . ':</span><span class="value">' . ($task->due_date ? h($task->due_date->nice()) : __d('projects', 'N/A')) . '</span></p>' .
        '</div>',
        'first' => sprintf('<div class="task-view-details" id="task-%s">', $task->id) .
            /*sprintf(
                '<div class="avatar"><img src="data:image/jpeg;base64,%s" /></div>',
                $task->user->getAvatar(),
            ) .*/
            sprintf(
                '<div class="avatar"><img src="https://gravatar.com/avatar/%s?d=mp" /></div>',
                hash('sha256',strtolower(trim($task->user->email)))
            ) .
            '<div class="task-view-description">' .
                sprintf(
                    '<div class="section-title"><b>%1$s</b> %2$s' .
                        '<div class="actions"><a href="#!" class="dropdown-task" data-target="dropdown-edittask"><i class="material-icons">more_horiz</i></a></div>' .
                    '</div>',
                    h($task->user->name),
                    __d('projects', 'opened on {0}', $task->created->nice()),
                ) .
                '<div class="section-body">' . (string)$converter->convert($task->descript) . '</div>' .
            '</div>' .
            '</div>',
        'add' =>
            '<div class="task-view-details task-add">' .
                sprintf(
                    '<div class="avatar"><img src="data:image/jpeg;base64,%s" /></div>',
                    $this->getCurrentUser()->getAvatar(),
                ) .
                '<div class="task-view-description">' .
                    sprintf('<div class="task-view-add">%s</div>', __d('projects', 'Add a new Comment')) .
                    '<div class="section-body task-add-comment">' .
                    $this->Lil->form($addCommentForm) .
                    '</div>' .
                '</div>' .
            '</div>',
    ],
];

$commentsLines = [];
foreach ($task->comments as $comment) {
    if (isset($comment->kind) && $comment->kind == 2) {
        // Render as a compact change item (no avatar).
        // `descript` is expected to be JSON like:
        // {"fieldName1": {"old":"oldvalue","new":"newValue"}, "fieldName2": {...}}
        $descript = trim((string)$comment->descript);
        $decoded = json_decode($descript, true);

        $changeHtml = '<div class="task-change-item" id="comment-' . $comment->id . '">';
        $changeHtml .= '<div class="who">' .
            '<b>' . h($comment->user->name) . '</b> ' .
            __d('projects', 'changed on {0}', $comment->created->nice()) . ':' .
            '</div>';
        $changeHtml .= '<ul>';

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && count($decoded)) {
            foreach ($decoded as $field => $vals) {
                $old = $vals['old'] ?? null;
                $new = $vals['new'] ?? null;

                switch ($field) {
                    case 'user_id':
                        $field = __d('projects', 'Owner');
                        $oldText = isset($users[$old]) ? h((string)$users[$old]) : __d('projects', 'Unassigned');
                        $newText = isset($users[$new]) ? h((string)$users[$new]) : __d('projects', 'Unassigned');
                        $changeHtml .= '<li><strong>' . h($field) . '</strong>: ' . __d('projects', 'from {0} to {1}', $oldText, $newText) . '</li>';
                        break;
                    case 'assigned_user_id':
                        $field = __d('projects', 'Assigned User');
                        $oldText = isset($users[$old]) ? h((string)$users[$old]) : __d('projects', 'Unassigned');
                        $newText = isset($users[$new]) ? h((string)$users[$new]) : __d('projects', 'Unassigned');
                        $changeHtml .= '<li><strong>' . h($field) . '</strong>: ' . __d('projects', 'from {0} to {1}', $oldText, $newText) . '</li>';
                        break;
                    case 'milestone_id':
                        $field = __d('projects', 'Milestone');
                        $oldText = $old ? h((string)$task->milestone) : __d('projects', 'No Milestone');
                        $newText = $new ? h((string)$task->milestone) : __d('projects', 'No Milestone');
                        $changeHtml .= '<li><strong>' . h($field) . '</strong>: ' . __d('projects', 'from {0} to {1}', $oldText, $newText) . '</li>';
                        break;
                    case 'descript':
                        $field = __d('projects', 'Description');
                        $changeHtml .= '<li><strong>' . h($field) . '</strong></li>';
                        break;
                    default:
                        $oldText = is_scalar($old) || $old === null ? h((string)$old) : h(json_encode($old));
                        $newText = is_scalar($new) || $new === null ? h((string)$new) : h(json_encode($new));
                        $changeHtml .= '<li><strong>' . h($field) . '</strong>: ' . __d('projects', 'from {0} to {1}', $oldText, $newText) . '</li>';
                        break;
                }
            }
        } else {
            // Fallback: show raw descript
            $changeHtml .= '<li>' . h($descript) . '</li>';
        }

        $changeHtml .= '</ul>';
        $changeHtml .= '</div>';

        $commentsLines[] = $changeHtml;
        continue;
    }

    $commentsLines[] = '<div class="task-view-details" id="comment-' . $comment->id . '">' .
        /*sprintf(
            '<div class="avatar"><img src="data:image/jpeg;base64,%s" /></div>',
            $comment->user->getAvatar(),
        ) .*/
        sprintf(
            '<div class="avatar"><img src="https://gravatar.com/avatar/%s?d=robohash" /></div>',
            hash('sha256',strtolower(trim($comment->user->email))),
        ) .
        '<div class="task-view-description">' .
            sprintf(
                '<div class="section-title"><b>%1$s</b> %2$s' .
                '<div class="actions"><a href="#!" class="dropdown-comment" data-target="dropdown-editcomment"><i class="material-icons">more_horiz</i></a></div>' .
                '</div>',
                h($comment->user->name),
                __d('projects', 'commented on {0}', $comment->created->nice()),
            ) .
            '<div class="section-body">' . (string)$converter->convert($comment->descript) . '</div>' .
        '</div>' .
    '</div>';
}

$this->Lil->insertIntoArray($taskView['panels'], $commentsLines, ['after' => 'first']);

echo $this->Lil->panels($taskView, 'Projects.ProjectsTasks.view');
?>
<script type="text/javascript">
    $(document).ready(function() {
        var dropdownTaskEdit = document.querySelectorAll(".dropdown-task")[0];
        var dropdownTaskEditInstance = M.Dropdown.init(dropdownTaskEdit, {
            constrainWidth: false,
            coverTrigger: false
        });

        var dropdownCommentEditElements = document.querySelectorAll(".dropdown-comment");
        var dropdownCommentEditInstances = M.Dropdown.init(dropdownCommentEditElements, {
            constrainWidth: false,
            coverTrigger: false
        });

        var dropdownTriggerElement = null;

        $(".dropdown-task").on("click", function(e) {
            dropdownTriggerElement = $(this);
        });

        $("ul#dropdown-edittask li a#dropdown-item-edit").modalPopup({
            title: "<?= __d('projects', 'Edit Task') ?>",
            onOpen: function(popup) {
                dropdownTaskEditInstance.close();
                $("#title", popup).focus();
            },
            onBeforeRequest: function(url, popup) {
                let taskId = dropdownTriggerElement.closest("div.task-view-details").attr("id").replace("task-", "");
                return url.replace("__id__", taskId);
            }
        });

        $("li a#dropdown-item-delete").on("click", function(e) {
            e.preventDefault();
            if (confirm($(this).data("confirm"))) {
                let commentId = $(this).closest("div.task-view-details").attr("id").replace("comment-", "");
                let url = $(this).attr("href").replace("__id__", commentId);
                window.location.href = url;
            }
        });

        $(".dropdown-comment").on("click", function(e) {
            dropdownTriggerElement = $(this);
        });
        $("ul#dropdown-editcomment li a#dropdown-item-edit").modalPopup({
            title: "<?= __d('projects', 'Edit Comment') ?>",
            onOpen: function(popup) {
                dropdownCommentEditInstances.forEach(function(instance) {
                    instance.close();
                });
                M.Forms.InitTextarea($("#descript", popup).get(0));
                $("#descript", popup).focus();
            },
            onBeforeRequest: function(url, popup) {
                let commentId = dropdownTriggerElement
                    .closest("div.task-view-details")
                    .attr("id")
                    .replace("comment-", "");
                return url.replace("__id__", commentId);
            }
        });
    });
</script>

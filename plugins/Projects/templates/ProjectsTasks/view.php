<?php

use Cake\Routing\Router;
use League\CommonMark\GithubFlavoredMarkdownConverter;

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

$dropdownEditTask = '<ul id="edittask" class="dropdown-content">' .
    sprintf(
        '<li id="dropdown-item-edit"><a href="%2$s"><i class="material-icons tiny">edit</i>%1$s</a></li>',
        __d('projects', 'Edit'),
        $this->Url->build(['controller' => 'ProjectsTasks', 'action' => 'edit', '__id__', '?' => ['redirect' => Router::url(null, true)]]),
    ) .
'</ul>';

$dropdownEditComment = '<ul id="editcomment" class="dropdown-content">' .
    sprintf(
        '<li id="dropdown-item-edit"><a href="%2$s"><i class="material-icons tiny">edit</i>%1$s</a></li>',
        __d('projects', 'Edit'),
        $this->Url->build(['controller' => 'ProjectsTasksComments', 'action' => 'edit', '__id__', '?' => ['redirect' => Router::url(null, true)]]),
    ) .
    sprintf(
        '<li id="dropdown-item-delete"><a href="%2$s" data-confirm="%3$s"><i class="material-icons tiny">delete</i>%1$s</a></li>',
        __d('projects', 'Delete'),
        $this->Url->build(['controller' => 'ProjectsTasksComments', 'action' => 'delete', '__id__', '?' => ['redirect' => Router::url(null, true)]]),
        __d('projects', 'Are you sure you want to delete this comment?'),
    ) .
'</ul>';

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
    'post' => '</div>' . $dropdownEditTask . $dropdownEditComment,
    'panels' => [
        'first' => sprintf('<div class="task-view-details" id="task-%s">', $task->id) .
            sprintf(
                '<div class="avatar"><img src="data:image/jpeg;base64,%s" /></div>',
                $task->user->getAvatar(),
            ) .
            '<div class="task-view-description">' .
                sprintf(
                    '<div class="section-title"><b>%1$s</b> %2$s' .
                        '<div class="actions"><a href="#!" class="dropdown-task" data-target="edittask"><i class="material-icons">more_horiz</i></a></div>' .
                    '</div>',
                    h($task->user->name),
                    __d('projects', 'opened on {0}', $task->created->nice()),
                ) .
                '<div class="section-body">' . (string)$converter->convert($task->descript) . '</div>' .
            '</div>' .
            '</div>',
        'add' =>
            sprintf('<div class="task-view-details" id="task-%s">', $task->id) .
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
    $commentsLines[] = '<div class="task-view-details" id="comment-' . $comment->id . '">' .
        sprintf(
            '<div class="avatar"><img src="data:image/jpeg;base64,%s" /></div>',
            $comment->user->getAvatar(),
        ) .
        '<div class="task-view-description">' .
            sprintf(
                '<div class="section-title"><b>%1$s</b> %2$s' .
                '<div class="actions"><a href="#!" class="dropdown-comment" data-target="editcomment"><i class="material-icons">more_horiz</i></a></div>' .
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

        $("ul#edittask li#dropdown-item-edit a").modalPopup({
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

        $("li#dropdown-item-delete a").on("click", function(e) {
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
        $("ul#editcomment li#dropdown-item-edit a").modalPopup({
            title: "<?= __d('projects', 'Edit Comment') ?>",
            onOpen: function(popup) {
                dropdownCommentEditInstances.forEach(function(instance) {
                    instance.close();
                });
                M.Forms.InitTextarea($("#descript", popup).get(0));
                $("#descript", popup).focus();
            },
            onBeforeRequest: function(url, popup) {
                let commentId = dropdownTriggerElement.closest("div.task-view-details").attr("id").replace("comment-", "");
                return url.replace("__id__", commentId);
            }
        });
    });
</script>

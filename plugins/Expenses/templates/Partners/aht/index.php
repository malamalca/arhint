<?php
use Cake\Routing\Router;

/**
 * @var \App\View\AppView $this
 * @var iterable<\Expenses\Model\Entity\Partner> $partners
 * @var string|null $contactId
 * @var array<string, string> $roleList
 */

$reloadUrl = h(Router::url([
    'plugin'     => 'Expenses',
    'controller' => 'Partners',
    'action'     => 'index',
    '_ext'       => 'aht',
    '?'          => ['contact_id' => $contactId],
]));

$addUrl = h(Router::url([
    'plugin'     => 'Expenses',
    'controller' => 'Partners',
    'action'     => 'edit',
    '?'          => ['contact_id' => $contactId],
]));

$partnersList = $partners instanceof \Cake\Datasource\ResultSetInterface ? $partners->toList() : iterator_to_array($partners);
?>

<div class="partners-tab">

<?php if (!empty($partnersList)): ?>
    <table class="lil-table striped">
        <thead>
            <tr>
                <th><?= __d('expenses', 'Role') ?></th>
                <th><?= __d('expenses', 'Date from') ?></th>
                <th><?= __d('expenses', 'Date to') ?></th>
                <th class="actions"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partnersList as $partner): ?>
            <tr>
                <td><?= h($roleList[$partner->role] ?? $partner->role) ?></td>
                <td><?= $partner->date_start ? h($partner->date_start->toDateString()) : '—' ?></td>
                <td><?= $partner->date_end   ? h($partner->date_end->toDateString())   : '—' ?></td>
                <td class="actions">
                    <a href="<?= h(Router::url(['plugin' => 'Expenses', 'controller' => 'Partners', 'action' => 'edit', $partner->id])) ?>"
                       class="edit-partner-link btn-small waves-effect waves-light">
                        <i class="material-icons tiny">edit</i>
                    </a>
                    <a href="<?= h(Router::url(['plugin' => 'Expenses', 'controller' => 'Partners', 'action' => 'delete', $partner->id])) ?>"
                       onclick="return confirm('<?= __d('expenses', 'Are you sure you want to delete this partner record?') ?>');"
                       class="btn-small waves-effect waves-light red lighten-2">
                        <i class="material-icons tiny">delete</i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="hint"><?= __d('expenses', 'No partner records for this contact.') ?></p>
<?php endif; ?>

    <a href="<?= $addUrl ?>" id="add-partner-link" class="btn-small waves-effect waves-light">
        <i class="material-icons tiny">add</i> <?= __d('expenses', 'Add Partner') ?>
    </a>

</div>

<script>
(function() {
    var reloadTab = function() {
        $.get("<?= $reloadUrl ?>", function(data) {
            $("#tab-content-partners").html(data);
        });
    };

    $("#add-partner-link").modalPopup({
        title: "<?= __d('expenses', 'Add Partner') ?>",
        processSubmit: true,
        onJson: function() { reloadTab(); },
    });

    $(".edit-partner-link").each(function() {
        $(this).modalPopup({
            title: "<?= __d('expenses', 'Edit Partner') ?>",
            processSubmit: true,
            onJson: function() { reloadTab(); },
        });
    });
}());
</script>

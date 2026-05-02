<?php
/**
 * @var \App\View\AppView $this
 * @var array<\Crm\Model\Entity\Contact> $contacts
 * @var array<string, string> $roleList
 */
use Cake\Routing\Router;

$pickContactUrl = h(Router::url([
    'plugin' => 'Expenses',
    'controller' => 'Partners',
    'action' => 'pickContact',
], true));
?>
<div>
    <div style="margin-bottom:8px;display:flex;gap:8px;align-items:center">
        <input type="text" id="contact-pick-filter"
               placeholder="<?= h(__d('expenses', 'Filter…')) ?>"
               autocomplete="off" class="browser-default"
               style="flex:1;padding:6px 8px;border:1px solid #9e9e9e;box-sizing:border-box" />
        <select id="contact-pick-role" class="browser-default"
                style="width:auto;padding:6px 8px;border:1px solid #9e9e9e">
            <?php foreach ($roleList as $value => $label) : ?>
            <option value="<?= h($value) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div style="max-height:350px;overflow-y:auto">
        <table class="striped" style="width:100%">
            <thead>
                <tr>
                    <th><?= h(__d('expenses', 'Name')) ?></th>
                    <th><?= h(__d('expenses', 'Title')) ?></th>
                </tr>
            </thead>
            <tbody id="contact-pick-list">
                <?php foreach ($contacts as $contact) : ?>
                <tr class="contact-pick-row" style="cursor:pointer"
                    data-id="<?= h($contact->id) ?>"
                    data-title="<?= h($contact->title ?? $contact->name ?? '') ?>">
                    <td><?= h($contact->name ?? '') ?></td>
                    <td><?= h($contact->title ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <form id="contact-pick-form" method="post" action="<?= $pickContactUrl ?>" style="display:none">
        <?= $this->Form->hidden('_csrfToken', ['value' => $this->request->getAttribute('csrfToken')]) ?>
        <input type="hidden" name="contact_id" id="contact-pick-id" value="" />
        <input type="hidden" name="role" id="contact-pick-role-val" value="buyer" />
    </form>
    <script>
    (function() {
        var $filter  = document.getElementById("contact-pick-filter");
        var $rows    = document.querySelectorAll(".contact-pick-row");
        var $role    = document.getElementById("contact-pick-role");
        var $form    = document.getElementById("contact-pick-form");

        $filter.addEventListener("input", function() {
            var q = this.value.toLowerCase();
            $rows.forEach(function(r) {
                var visible = q === ""
                    || r.dataset.title.toLowerCase().indexOf(q) !== -1
                    || r.cells[0].textContent.toLowerCase().indexOf(q) !== -1;
                r.style.display = visible ? "" : "none";
            });
        });
        $filter.focus();

        $rows.forEach(function(r) {
            r.addEventListener("click", function() {
                document.getElementById("contact-pick-id").value    = r.dataset.id;
                document.getElementById("contact-pick-role-val").value = $role.value;
                $($form).trigger("submit");
            });
        });
    })();
    </script>
</div>

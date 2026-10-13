<?php

echo $this->element('Projects.authority_form', [
    'entity' => $case,
    'summary' => $summary,
    'title' => $case->isNew() ? __d('projects', 'Add authority case') : __d('projects', 'Edit authority case'),
    'formEvent' => 'Projects.ProjectsAuthorities.editCase',
    'fields' => [
        'contact_id' => ['type' => 'hidden', 'id' => 'authority-contact-id'],
        'contact_title' => [
            'label' => __d('projects', 'Authority'), 'type' => 'text', 'id' => 'authority-contact-title',
            'value' => $contactTitle, 'autocomplete' => 'off', 'required' => true,
        ],
        'title' => ['label' => __d('projects', 'Case title / area'), 'type' => 'text'],
        'user_id' => [
            'label' => __d('projects', 'Responsible person'), 'options' => $responsibleUsers, 'empty' => true,
        ],
        'reference' => ['label' => __d('projects', 'Case reference'), 'type' => 'text'],
        'exempt_reason' => [
            'label' => __d('projects', 'Not required: reason (the case is then excluded from the review)'),
            'type' => 'textarea',
        ],
        'descript' => ['label' => __d('projects', 'Notes'), 'type' => 'textarea'],
    ],
]);
?>
<script>
$(document).ready(function() {
    var contactInput = document.getElementById('authority-contact-title');
    var contactId = document.getElementById('authority-contact-id');
    var contactUrl = <?= json_encode($this->Url->build([
        'plugin' => 'Crm', 'controller' => 'Contacts', 'action' => 'autocomplete', '?' => ['kind' => 'C'],
    ]), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    contactInput.addEventListener('input', function() {
        contactId.value = '';
    });
    M.Autocomplete.init(contactInput, {
        onSearch: function(text, autocomplete) {
            $.get(contactUrl, {term: text}).done(function(data) {
                if (contactInput.value !== text) {
                    return;
                }
                autocomplete.setMenuItems(data.map(function(item) {
                    return {id: item.id, text: item.value};
                }));
            });
        },
        onAutocomplete: function(entries) {
            if (entries.length === 1) {
                contactId.value = entries[0].id;
                var titleInput = document.getElementById('title');
                if (titleInput && titleInput.value.trim() === '') {
                    titleInput.value = contactInput.value;
                    M.updateTextFields();
                }
            }
        }
    });
});
</script>

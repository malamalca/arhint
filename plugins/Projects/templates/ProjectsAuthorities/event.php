<?php
use Projects\Lib\AuthorityWorkflow;

echo '<p>' . h(__d(
    'projects',
    'Record an actual submission or response. This form does not send an application.',
)) . '</p>';
$formTitle = $case->title . ' · ' .
    ($event->isNew() ? __d('projects', 'Record procedure event') : __d('projects', 'Edit event'));
echo $this->element('Projects.authority_form', [
    'entity' => $event,
    'summary' => $summary,
    'title' => $formTitle,
    'formEvent' => 'Projects.ProjectsAuthorities.event',
    // The form can open in a popup above a page that already has fields with the same names.
    'formOptions' => ['idPrefix' => 'authority-event'],
    'fields' => [
        'kind' => ['label' => __d('projects', 'Event type'), 'options' => AuthorityWorkflow::kinds()],
        'event_date' => ['label' => __d('projects', 'Submission / receipt date'), 'type' => 'date'],
        'parent_id' => [
            'label' => __d('projects', 'Related event'), 'options' => $parents,
            'empty' => true, 'class' => 'no-autoinit',
        ],
        'date_due' => ['label' => __d('projects', 'Next check / supplement due'), 'type' => 'date'],
        'date_valid' => ['label' => __d('projects', 'Valid until (conditions / opinion)'), 'type' => 'date'],
        'descript' => ['label' => __d('projects', 'Summary / next step / reason'), 'type' => 'textarea'],
        'document_ids' => [
            'label' => __d('projects', 'Linked documents'), 'options' => $documents,
            'multiple' => true, 'value' => $selectedDocuments,
        ],
    ],
]);
?>
<script>
$(document).ready(function() {
    var kind = document.getElementById('authority-event-kind');
    var reason = document.getElementById('authority-event-descript');
    var parent = document.getElementById('authority-event-parent-id');
    var parentLabel = document.querySelector('label[for="authority-event-parent-id"]');
    var parentEvents = <?= json_encode($parentEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    // A popup can run this block more than once for the same form.
    if (!kind || kind.dataset.authorityInit) {
        return;
    }
    kind.dataset.authorityInit = '1';
    var originalOptions = Array.from(parent.options).map(function(option) {
        return {value: option.value, text: option.text};
    });
    function compatible(event) {
        if (kind.value === 'supplement') { return event.kind === 'request'; }
        if (kind.value === 'void') { return true; }
        return ['conditions', 'opinion', 'request', 'other'].includes(kind.value) &&
            ['submission', 'supplement'].includes(event.kind);
    }
    function updateRequirements() {
        reason.required = kind.value === 'void';
        $(reason).parent().toggleClass('required', reason.required);
        var selected = parent.value;
        var instance = M.FormSelect.getInstance(parent);
        if (instance) {
            if (parentLabel) {
                instance.wrapper.before(parentLabel);
                parentLabel.htmlFor = parent.id;
            }
            instance.destroy();
        }
        parent.replaceChildren();
        originalOptions.forEach(function(option) {
            if (option.value === '' || (parentEvents[option.value] && compatible(parentEvents[option.value]))) {
                parent.add(new Option(option.text, option.value, false, option.value === selected));
            }
        });
        var select = M.FormSelect.init(parent, {classes: 'authority-parent-select'});
        if (parentLabel) {
            parentLabel.htmlFor = select.input.id;
        }
    }
    kind.addEventListener('change', updateRequirements);
    // Deferred: a popup initialises all its selects right after this block has run.
    setTimeout(updateRequirements, 0);
});
</script>

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
    'fields' => [
        'kind' => ['label' => __d('projects', 'Event type'), 'options' => AuthorityWorkflow::kinds()],
        'event_date' => ['label' => __d('projects', 'Submission / receipt date'), 'type' => 'date'],
        'parent_id' => [
            'label' => __d('projects', 'Related event'), 'options' => $parents,
            'empty' => true, 'class' => 'no-autoinit',
        ],
        'date_due' => ['label' => __d('projects', 'Next check / supplement due'), 'type' => 'date'],
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
    var kind = document.getElementById('kind');
    var reason = document.getElementById('descript');
    var parent = document.getElementById('parent-id');
    var parentLabel = document.querySelector('label[for="parent-id"]');
    var parentEvents = <?= json_encode($parentEvents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var originalOptions = Array.from(parent.options).map(function(option) {
        return {value: option.value, text: option.text};
    });
    function compatible(event) {
        if (kind.value === 'approve') {
            return ['conditions', 'opinion'].includes(event.kind);
        }
        if (kind.value === 'supplement') { return event.kind === 'request'; }
        if (kind.value === 'revoke') { return event.kind === 'approve'; }
        if (kind.value === 'void') { return true; }
        return ['conditions', 'opinion', 'request', 'other'].includes(kind.value) &&
            ['submission', 'supplement'].includes(event.kind);
    }
    function updateRequirements() {
        reason.required = ['revoke', 'void'].includes(kind.value);
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
    updateRequirements();
});
</script>

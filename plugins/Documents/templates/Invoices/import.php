<?php

/**
 * @var \App\View\AppView $this
 * @var string $counterId Counter ID.
 * @var \Documents\Form\InvoiceImportForm $form Import form instance.
 */

$this->assign('title', __d('documents', 'Import Invoice'));
?>

<div class="invoices content">
    <h3><?= h(__d('documents', 'Import Invoice')) ?></h3>

    <?= $this->Form->create($form, [
        'type' => 'file',
        'url' => [
            'action' => 'import',
            '?' => ['counter' => $counterId],
        ],
    ]) ?>
    <fieldset>
        <legend><?= h(__d('documents', 'Upload eSlog XML or PDF')) ?></legend>

        <?= $this->Form->control('import_file', [
            'type' => 'file',
            'accept' => '.xml,.pdf,application/xml,application/pdf',
            'label' => __d('documents', 'XML or PDF File') . ':',
        ]) ?>

        <p class="helper-text">
            <?= __d('documents', 'Upload an invoice as an eSlog 2.0 XML or as a PDF. XML is read directly; a PDF is read with AI. The invoice form will be pre-filled and, for a PDF, the file is attached when you save. AI processing may take up to a minute.') ?>
        </p>
    </fieldset>

    <div class="form-actions">
        <?= $this->Form->button(__d('documents', 'Parse & Continue'), ['class' => 'btn btn-primary']) ?>
        <?= $this->Html->link(
            __d('documents', 'Cancel'),
            ['action' => 'index', '?' => ['counter' => $counterId]],
            ['class' => 'btn btn-default']
        ) ?>
    </div>

    <?= $this->Form->end() ?>
</div>

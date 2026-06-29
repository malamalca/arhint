<?php

/**
 * @var \App\View\AppView $this
 * @var string $counterId Counter ID.
 * @var \Documents\Form\PdfImportForm $form Import form instance.
 */

$this->assign('title', __d('documents', 'Import Invoice from PDF'));
?>

<div class="invoices content">
    <h3><?= h(__d('documents', 'Import Invoice from PDF')) ?></h3>

    <?= $this->Form->create($form, [
        'type' => 'file',
        'url' => [
            'action' => 'importPdf',
            '?' => ['counter' => $counterId],
        ],
    ]) ?>
    <fieldset>
        <legend><?= h(__d('documents', 'Upload PDF Invoice')) ?></legend>

        <?= $this->Form->control('pdf_file', [
            'type' => 'file',
            'accept' => '.pdf,application/pdf',
            'label' => __d('documents', 'PDF File') . ':',
        ]) ?>

        <p class="helper-text">
            <?= __d('documents', 'Upload an invoice as a PDF. The system will use AI to read the document and pre-fill the invoice form. This may take up to a minute.') ?>
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

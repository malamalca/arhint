<?php

/**
 * @var \App\View\AppView $this
 * @var string $counterId Counter ID.
 * @var \Documents\Form\EslogImportForm $form Import form instance.
 */

$this->assign('title', __d('documents', 'Import eSlog 2.0 Invoice'));
?>

<div class="invoices content">
    <h3><?= h(__d('documents', 'Import eSlog 2.0 Invoice')) ?></h3>

    <?= $this->Form->create($form, [
        'type' => 'file',
        'url' => [
            'action' => 'importEslog',
            '?' => ['counter' => $counterId],
        ],
    ]) ?>
    <fieldset>
        <legend><?= h(__d('documents', 'Upload eSlog 2.0 XML File')) ?></legend>

        <?= $this->Form->control('eslog_file', [
            'type' => 'file',
            'accept' => '.xml,application/xml',
            'label' => __d('documents', 'eSlog 2.0 XML File') . ':',
        ]) ?>

        <p class="helper-text">
            <?= __d('documents', 'Upload an invoice in eSlog 2.0 XML format. The system will parse the file and pre-fill the invoice form.') ?>
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

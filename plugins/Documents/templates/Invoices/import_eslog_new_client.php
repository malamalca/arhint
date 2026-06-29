<?php
use Cake\Routing\Router;

/**
 * @var \App\View\AppView $this
 * @var string|null $counterId Counter ID.
 * @var string|null $missingClientTaxNo Tax number of missing client.
 * @var string|null $missingClientTitle Title of missing client.
 */

$this->assign('title', __d('documents', 'Import eSlog 2.0 Invoice - New Client'));
?>

<div class="invoices content">
    <h3><?= h(__d('documents', 'New Client Required')) ?></h3>

    <div class="alert alert-warning">
        <p>
            <?= h(__d('documents', 'The client with tax number {0} was not found in the system.', $missingClientTaxNo)) ?>
        </p>
        <?php if (!empty($missingClientTitle)): ?>
            <p>
                <?= h(__d('documents', 'Client name from invoice: {0}', $missingClientTitle)) ?>
            </p>
        <?php endif; ?>
    </div>

    <p><?= h(__d('documents', 'Would you like to create this client first, or continue with manual entry?')) ?></p>

    <div class="form-actions">
        <?= $this->Html->link(
            __d('documents', 'Create New Client'),
            [
                'plugin' => 'Crm',
                'controller' => 'Contacts',
                'action' => 'edit',
                '?' => [
                    'kind' => 'C',
                    'redirect' => base64_encode(
                        Router::url([
                            'plugin' => 'Documents',
                            'controller' => 'Invoices',
                            'action' => 'edit',
                            '?' => ['counter' => $counterId, 'importFromEslog' => '1'],
                        ], true)
                    ),
                ],
            ],
            ['class' => 'btn btn-primary']
        ) ?>

        <?= $this->Html->link(
            __d('documents', 'Continue Without Creating Client'),
            [
                'action' => 'edit',
                '?' => ['counter' => $counterId, 'importFromEslog' => '1'],
            ],
            ['class' => 'btn btn-default']
        ) ?>

        <?= $this->Html->link(
            __d('documents', 'Cancel'),
            ['action' => 'index', '?' => ['counter' => $counterId]],
            ['class' => 'btn btn-default']
        ) ?>
    </div>
</div>

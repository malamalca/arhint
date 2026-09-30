<?php
$isAdmin = $this->getCurrentUser()->hasRole('admin');

$certificateInfo = $certificate && !empty($certificate->p12) ?
    __d(
        'documents',
        'Certificate valid to {0}',
        $certificate->valid_to ? $certificate->valid_to->format('d.m.Y') : '?',
    ) :
    __d('documents', 'No certificate uploaded');

$taxPremisesIndex = [
    'title_for_layout' => __d('documents', 'Business Premises'),
    'menu' => [
        'add' => [
            'title' => __d('documents', 'Add'),
            'visible' => $isAdmin,
            'url' => [
                'action' => 'edit',
            ],
        ],
        'certificate' => [
            'title' => __d('documents', 'Certificate'),
            'visible' => true,
            'url' => [
                'action' => 'certificate',
            ],
        ],
    ],
    'table' => [
        'parameters' => [
            'width' => '100%', 'cellspacing' => 0, 'cellpadding' => 0, 'id' => 'AdminTaxPremisesIndex',
        ],
        'head' => ['rows' => [['columns' => [
            'no' => __d('documents', 'No'),
            'title' => __d('documents', 'Title'),
            'kind' => __d('documents', 'Kind'),
            'address' => __d('documents', 'Address'),
            'validity_date' => __d('documents', 'Valid From'),
            'status' => __d('documents', 'Status'),
            'actions' => [],
        ]]]],
    ],
    'panels' => [
        'certificate' => sprintf('<p class="light">%s</p>', h($certificateInfo)),
    ],
];

foreach ($taxPremises as $taxPremise) {
    if ($taxPremise->closed) {
        $status = $taxPremise->active ? __d('documents', 'Closing pending') : __d('documents', 'Closed');
    } else {
        $status = $taxPremise->active ? __d('documents', 'Registered') : __d('documents', 'Not registered');
    }

    $address = $taxPremise->kind == 'RL' ?
        trim(sprintf(
            '%s %s%s, %s %s',
            $taxPremise->street,
            $taxPremise->house_number,
            $taxPremise->house_number_additional,
            $taxPremise->postal_code,
            $taxPremise->city,
        )) :
        __d('documents', 'Moveable Object');

    $taxPremisesIndex['table']['body']['rows'][]['columns'] = [
        'no' => h($taxPremise->no),
        'title' => h($taxPremise->title),
        'kind' => $taxPremise->kind == 'RL' ? __d('documents', 'Real Estate') : __d('documents', 'Moveable'),
        'address' => h($address),
        'validity_date' => $taxPremise->validity_date ? $taxPremise->validity_date->format('d.m.Y') : '',
        'status' => $status,
        'actions' => !$isAdmin ? '' : [
            'parameters' => ['class' => 'right-align'],
            'html' => $this->Form->postLink(
                '<i class="material-icons">' . ($taxPremise->closed ? 'cloud_off' : 'cloud_upload') . '</i>',
                ['action' => 'register', $taxPremise->id],
                [
                    'confirm' => __d('documents', 'Send business premise to tax authority?'),
                    'class' => 'btn-small filled',
                    'escape' => false,
                    'title' => $taxPremise->closed ? __d('documents', 'Send closing') : __d('documents', 'Register'),
                ],
            ) . ' ' . $this->Lil->editLink($taxPremise->id) . ' ' . $this->Lil->deleteLink($taxPremise->id),
        ],
    ];
}

echo $this->Lil->index($taxPremisesIndex, 'Documents.TaxPremises.index');

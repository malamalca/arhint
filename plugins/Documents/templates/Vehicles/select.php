<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Documents\Model\Entity\Vehicle> $vehicles
 */

$rows = '';
foreach ($vehicles as $vehicle) {
    $rows .= sprintf(
        '<tr class="vehicle-select-row" style="cursor:pointer"'
        . ' data-title="%s" data-registration="%s" data-owner="%s">'
        . '<td>%s</td><td>%s</td><td>%s</td></tr>',
        h($vehicle->title),
        h($vehicle->registration),
        h($vehicle->owner),
        h($vehicle->title),
        h($vehicle->registration),
        h($vehicle->owner),
    );
}

$pageData = [
    'title_for_layout' => __d('documents', 'Select Vehicle'),
    'panels' => [
        'vehicles' => sprintf(
            '<table class="responsive-table" style="width:100%%"><thead><tr>'
            . '<th>%s</th><th>%s</th><th>%s</th>'
            . '</tr></thead><tbody>%s</tbody></table>',
            __d('documents', 'Title'),
            __d('documents', 'Registration'),
            __d('documents', 'Owner'),
            $rows,
        ),
    ],
];

echo $this->Lil->panels($pageData, 'Documents.Vehicles.select');

<?php
/**
 * TravelOrders XML export template.
 *
 * For travel orders that are not yet completed, only the primary (first-input)
 * document data is exported. When the status is 'completed', the full dataset
 * including mileage entries and expense entries is included.
 *
 * @var \App\View\AppView $this
 * @var array<\Documents\Model\Entity\TravelOrder> $travelOrders
 */

use Cake\Utility\Xml;
use Documents\Model\Entity\TravelOrder;

$transformed = ['TravelOrders' => [
    'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
]];

$i = 0;
foreach ((array)$travelOrders as $travelOrder) {
    $isCompleted = $travelOrder->status === TravelOrder::STATUS_COMPLETED;

    ////////////////////////////////////////////////////////////////////
    // HEADER – always exported
    $record = [
        'No' => h($travelOrder->no ?? ''),
        'Title' => h($travelOrder->title ?? ''),
        'DatIssue' => $travelOrder->dat_issue ? $travelOrder->dat_issue->format('Y-m-d') : '',
        'DatTask' => $travelOrder->dat_task ? $travelOrder->dat_task->format('Y-m-d') : '',
        'Location' => h($travelOrder->location ?? ''),
        'Taskee' => h($travelOrder->taskee ?? ''),
        'Departure' => $travelOrder->departure ? $travelOrder->departure->format('c') : '',
        'Arrival' => $travelOrder->arrival ? $travelOrder->arrival->format('c') : '',
        'Descript' => h($travelOrder->descript ?? ''),
        'Status' => h($travelOrder->status ?? ''),
        'Employee' => h($travelOrder->employee->name ?? ''),
        'Vehicle' => [
            'Title' => h($travelOrder->vehicle_title ?? ''),
            'Registration' => h($travelOrder->vehicle_registration ?? ''),
            'Owner' => h($travelOrder->vehicle_owner ?? ''),
        ],
        'Advance' => $travelOrder->advance !== null ? (string)$travelOrder->advance : '',
        'DatAdvance' => $travelOrder->dat_advance ? $travelOrder->dat_advance->format('Y-m-d') : '',
        'EnteredBy' => h($travelOrder->entered_by->name ?? ''),
        'EnteredAt' => $travelOrder->entered_at ? $travelOrder->entered_at->format('c') : '',
        'ApprovedBy' => h($travelOrder->approved_by->name ?? ''),
        'ApprovedAt' => $travelOrder->approved_at ? $travelOrder->approved_at->format('c') : '',
    ];

    ////////////////////////////////////////////////////////////////////
    // FULL DATASET – only when completed
    if ($isCompleted) {
        // Processed workflow info
        $record['ProcessedBy'] = h($travelOrder->processed_by->name ?? '');
        $record['ProcessedAt'] = $travelOrder->processed_at ? $travelOrder->processed_at->format('c') : '';

        // Mileages
        if (!empty($travelOrder->travel_orders_mileages)) {
            $mileages = [];
            $fmt = ['places' => 2, 'locale' => 'en-US'];
            foreach ($travelOrder->travel_orders_mileages as $j => $m) {
                $mileages[$j] = [
                    'StartTime' => $m->start_time ? $m->start_time->format('c') : '',
                    'EndTime' => $m->end_time ? $m->end_time->format('c') : '',
                    'RoadDescription' => h($m->road_description ?? ''),
                    'DistanceKm' => $m->distance_km !== null
                        ? $this->Number->format($m->distance_km, $fmt) : '',
                    'PricePerKm' => $m->price_per_km !== null
                        ? $this->Number->format($m->price_per_km, ['places' => 4, 'locale' => 'en-US']) : '',
                    'Total' => $m->total !== null
                        ? $this->Number->format($m->total, $fmt) : '',
                ];
            }
            $record['Mileages'] = ['Mileage' => $mileages];
        }

        // Expenses
        if (!empty($travelOrder->travel_orders_expenses)) {
            $expenses = [];
            $fmt = ['places' => 2, 'locale' => 'en-US'];
            foreach ($travelOrder->travel_orders_expenses as $j => $e) {
                $expenses[$j] = [
                    'StartTime' => $e->start_time ? $e->start_time->format('c') : '',
                    'EndTime' => $e->end_time ? $e->end_time->format('c') : '',
                    'Description' => h($e->description ?? ''),
                    'Type' => h($e->type ?? ''),
                    'Quantity' => $e->quantity !== null
                        ? $this->Number->format($e->quantity, $fmt) : '',
                    'Price' => $e->price !== null
                        ? $this->Number->format($e->price, $fmt) : '',
                    'Currency' => h($e->currency ?? ''),
                    'Total' => $e->total !== null
                        ? $this->Number->format($e->total, $fmt) : '',
                    'ApprovedTotal' => $e->approved_total !== null
                        ? $this->Number->format($e->approved_total, $fmt) : '',
                ];
            }
            $record['Expenses'] = ['Expense' => $expenses];
        }
    }

    ////////////////////////////////////////////////////////////////////
    // TOTALS – always exported when available
    $fmt = ['places' => 2, 'locale' => 'en-US'];
    $record['NetTotal'] = $travelOrder->net_total !== null
        ? $this->Number->format($travelOrder->net_total, $fmt) : '';
    $record['Total'] = $travelOrder->total !== null
        ? $this->Number->format($travelOrder->total, $fmt) : '';
    if ($travelOrder->total !== null && $travelOrder->advance !== null) {
        $record['TotalAdvance'] = $this->Number->format($travelOrder->advance, $fmt);
        $record['TotalPayout'] = $this->Number->format(
            $travelOrder->total - $travelOrder->advance,
            $fmt,
        );
    } else {
        $record['TotalAdvance'] = '';
        $record['TotalPayout'] = '';
    }

    $transformed['TravelOrders']['TravelOrder'][$i] = $record;
    $i++;
}

$XmlObject = Xml::fromArray($transformed, ['format' => 'tags', 'return' => 'domdocument', 'pretty' => true]);

echo $XmlObject->saveXML();

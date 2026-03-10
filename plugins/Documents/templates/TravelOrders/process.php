<?php
/**
 * @var \App\View\AppView $this
 * @var \Documents\Model\Entity\TravelOrder $document
 */
use Cake\Routing\Router;
use Cake\Utility\Text;

$this->set('head_for_layout', false);

$pdfName = Text::slug($document->title ?? $document->no ?? 'travel-order') . '.pdf';
$pdfUrl = Router::url(['action' => 'export', $document->id, $pdfName], true);

$iframeStyle = 'width:100%;border:0;display:block;';

$pageData = [
    'title_for_layout' => __d('documents', 'Process Travel Order #{0}', h($document->no)),
    'menu' => [
        'back' => [
            'title' => __d('documents', 'Back'),
            'visible' => true,
            'url' => ['action' => 'view', $document->id],
        ],
    ],
    'panels' => [
        'preview' => sprintf('<iframe id="travel-order-view" src="%s" style="%s"></iframe>', $pdfUrl, $iframeStyle),
        'confirm' => [
            'params' => ['id' => 'ProcessPanel'],
            'form' => [
                'defaultHelper' => $this->Form,
                'lines' => [
                    'form_start' => [
                        'method' => 'create',
                        'parameters' => [
                            $document,
                            ['url' => ['action' => 'process', $document->id]],
                        ],
                    ],
                    'submit' => [
                        'method' => 'button',
                        'parameters' => [
                            __d('documents', 'Sign & Complete'),
                            ['type' => 'submit', 'class' => 'btn waves-effect waves-light'],
                        ],
                    ],
                    'form_end' => [
                        'method' => 'end',
                        'parameters' => [],
                    ],
                ],
            ],
        ],
    ],
];

echo $this->Lil->panels($pageData, 'Documents.TravelOrders.process');

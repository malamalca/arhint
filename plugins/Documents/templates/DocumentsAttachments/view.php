<?php
use Cake\Routing\Router;

$this->set('head_for_layout', false);

$action = Router::url(['action' => 'download', $a->id, 0, $a->original], true);

$attachmentPreview = [
    'title_for_layout' => __d('documents', 'Attachment Preview'),
    'menu' => [
        'edit' => empty($id) ? null : [
            'title' => __d('documents', 'Back'),
            'visible' => true,
            'url' => [
                'action' => 'view',
                $id,
            ],
        ],
    ],
    'entity' => $a,
    'panels' => [
       sprintf('<iframe id="attachment-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($attachmentPreview, 'Documents.DocumentsAttachments.view');

$this->Lil->jsReady('$("#attachment-view").height(window.innerHeight - $("#attachment-view").offset().top - 30);');

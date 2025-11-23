<?php
use Cake\Routing\Router;
use Cake\Core\Configure;

$this->set('head_for_layout', false);

$action = Router::url(['action' => 'download', $attachment->id, 0, $attachment->filename], true);

$returnUrl = $this->request->getQuery('redirect');

$attachmentPreview = [
    'title_for_layout' => __('Attachment'),
    'menu' => [
        'edit' => empty($returnUrl) ? null : [
            'title' => __('Back'),
            'visible' => true,
            'url' => $returnUrl,
        ],
    ],
    'entity' => $attachment,
    'panels' => [
       sprintf('<iframe id="attachment-view" src="%s"></iframe>', $action),
    ],
];

echo $this->Lil->panels($attachmentPreview, 'Attachments.preview');

$this->Lil->jsReady('$("#attachment-view").height(window.innerHeight - $("#attachment-view").offset().top - 30);');
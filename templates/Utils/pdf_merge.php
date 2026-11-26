<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Routing\Router;

$this->Html->script('/js/vendor/pdf-lib/pdf-lib.min.js', ['block' => true]);
$this->Html->script('jquery/jquery-ui.min.js', ['block' => true]);
$this->Html->script('pdfTools.js', ['block' => true]);
$this->Html->css('pdfTools.css', ['block' => true]);

$preloader = '<div class="preloader-wrapper" style="display: none; width: 20px; height: 20px;">' .
    '<div class="spinner-layer spinner-red-only">' .
    '<div class="circle-clipper left"><div class="circle"></div></div>' .
    '<div class="gap-patch"><div class="circle"></div></div>' .
    '<div class="circle-clipper right"><div class="circle"></div></div>' .
    '</div></div>';

$compressionOptions = [
    'default' => __('Default'),
    'printer' => __('Printer (300 dpi)'),
    'prepress' => __('Prepress (300 dpi)'),
    'ebook' => __('Ebook (150 dpi)'),
    'screen' => __('Screen (72 dpi)'),
];

$markup = '
<div id="pdf-merge" class="pdft"
    data-url="' . h(Router::url(['action' => 'pdfMerge'])) . '"
    data-viewer="' . h(Router::url(['controller' => 'Pages', 'action' => 'pdf'])) . '"
    data-messages="' . $this->element('pdf_tools_messages') . '">

    <p class="pdft-hint">' . __('The files are merged in the browser and are not sent to the server. '
        . 'Drag the files in the list to change their order.') . '</p>

    <div class="upload-area pdft-drop">
        <h1 class="pdft-empty">' . __('Drag and Drop file here<br/>Or<br/>Click to select file') . '</h1>
        <div class="pdft-list"></div>
    </div>
    <input type="file" class="pdft-file" accept="application/pdf,.pdf" multiple hidden>
    <div class="progress pdft-progress"><div class="determinate" style="width: 0%"></div></div>

    ' . $this->Form->control('filename', [
        'label' => __('Filename') . ':',
        'class' => 'pdft-filename',
        'default' => 'download.pdf',
    ]) . '
    ' . $this->Form->control('compression', [
        'type' => 'select',
        'class' => 'pdft-compression',
        'label' => __('Compression') . ':',
        'value' => 'default',
        'options' => $compressionOptions,
    ]) . '
    ' . $this->Form->control('pdfa', [
        'type' => 'checkbox',
        'label' => __('Encode to PDF/A'),
        'class' => 'pdft-pdfa',
    ]) . '
    <p class="pdft-hint">' . __('Compression and PDF/A are only available on the server, '
        . 'so the files are uploaded when one of them is selected.') . '</p>

    <a class="btn filled pdft-submit"><span class="pdft-caption">' . __('Merge') . '</span>' . $preloader . '</a>
</div>';

echo $this->Lil->form([
    'title' => __('PDF Merge'),
    'menu' => [
        'sign' => [
            'title' => __('PDF Sign'),
            'active' => false,
            'visible' => true,
            'url' => ['action' => 'pdfSignImages'],
        ],
        'merge' => [
            'title' => __('PDF Merge'),
            'active' => true,
            'visible' => true,
            'url' => ['action' => 'pdfMerge'],
        ],
        'splice' => [
            'title' => __('PDF Splice'),
            'active' => false,
            'visible' => true,
            'url' => ['action' => 'pdfSplice'],
        ],
    ],
    'form' => [
        'lines' => [$markup],
    ],
], 'App.Utils.PdfMerge');

echo $this->Html->meta('csrfToken', $this->getRequest()->getAttribute('csrfToken'));

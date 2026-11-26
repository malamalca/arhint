<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Routing\Router;

$this->Html->script('/js/vendor/pdf-lib/pdf-lib.min.js', ['block' => true]);
$this->Html->script('pdfTools.js', ['block' => true]);
$this->Html->css('pdfTools.css', ['block' => true]);

$preloader = '<div class="preloader-wrapper" style="display: none; width: 20px; height: 20px;">' .
    '<div class="spinner-layer spinner-red-only">' .
    '<div class="circle-clipper left"><div class="circle"></div></div>' .
    '<div class="gap-patch"><div class="circle"></div></div>' .
    '<div class="circle-clipper right"><div class="circle"></div></div>' .
    '</div></div>';

$markup = '
<div id="pdf-split" class="pdft"
    data-url="' . h(Router::url(['action' => 'pdfSplice'])) . '"
    data-messages="' . $this->element('pdf_tools_messages') . '">

    <p class="pdft-hint">' . __('The file is split in the browser and is not sent to the server.') . '</p>

    <div class="upload-area pdft-drop">
        <h1>' . __('Drag and Drop file here<br/>Or<br/>Click to select file') . '</h1>
        <p class="pdft-info" hidden></p>
    </div>
    <input type="file" class="pdft-file" accept="application/pdf,.pdf" hidden>
    <div class="progress pdft-progress"><div class="determinate" style="width: 0%"></div></div>

    <div class="pdft-options" hidden>
        ' . $this->Form->control('pages', [
            'type' => 'text',
            'class' => 'pdft-pages',
            'label' => ['text' => __('Pages') . ':', 'class' => 'active'],
            'placeholder' => __('e.g. 1,2,3-5,15-20'),
            'autocomplete' => 'off',
        ]) . '
        <p class="pdft-hint">' . __('Separate pages and ranges with commas, e.g. 1,2,3-5,15-20. '
            . '"15-" means from page 15 to the end; leave the field empty for all pages.') . '</p>
        ' . $this->Form->control('multiPage', [
            'type' => 'checkbox',
            'class' => 'pdft-multi',
            'label' => __('Split into multiple pages'),
        ]) . '

        <a class="btn filled pdft-submit"><span class="pdft-caption">' . __('Split') . '</span>' . $preloader . '</a>
    </div>
</div>';

echo $this->Lil->form([
    'title' => __('PDF Splice'),
    'menu' => [
        'sign' => [
            'title' => __('PDF Sign'),
            'active' => false,
            'visible' => true,
            'url' => ['action' => 'pdfSignImages'],
        ],
        'merge' => [
            'title' => __('PDF Merge'),
            'active' => false,
            'visible' => true,
            'url' => ['action' => 'pdfMerge'],
        ],
        'splice' => [
            'title' => __('PDF Splice'),
            'active' => true,
            'visible' => true,
            'url' => ['action' => 'pdfSplice'],
        ],
    ],
    'form' => [
        'lines' => [$markup],
    ],
], 'App.Utils.PdfSplice');

echo $this->Html->meta('csrfToken', $this->getRequest()->getAttribute('csrfToken'));

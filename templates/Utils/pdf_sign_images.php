<?php
/**
 * @var \App\View\AppView $this
 */
use Cake\Core\Configure;

$this->Html->script('/js/vendor/pdf-lib/pdf-lib.min.js', ['block' => true]);
$this->Html->script('pdfSignImages.js', ['block' => true]);
$this->Html->css('pdfSignImages.css', ['block' => true]);

$messages = [
    'loadError' => __('The file could not be opened as a PDF.'),
    'imageError' => __('The selected file is not a usable image.'),
    'saveError' => __('Error while saving the PDF.'),
    'discard' => __('Signatures that were not saved will be lost. Continue?'),
    'signerDown' => __('ArhintSigner is not running. Start arhint-signer-webservice.exe and try again.'),
    'noCertificates' => __('ArhintSigner did not report any usable certificate.'),
    'rsaOnly' => __('Only certificates with an RSA key can be used for signing.'),
    'signError' => __('Error while signing the PDF.'),
    'signerOutdated' => __('ArhintSigner returned damaged data. Update it to the latest version.'),
];

$markup = '
<div id="pdf-sign-images"
    data-pdfjs="' . h($this->Url->webroot('js/vendor/pdfjs/pdf.min.mjs')) . '"
    data-worker="' . h($this->Url->webroot('js/vendor/pdfjs/pdf.worker.min.mjs')) . '"
    data-signer="' . h(Configure::read('Signer.url', 'http://localhost:8082')) . '"
    data-messages="' . h(json_encode($messages)) . '">

    <div class="psi-step psi-upload">
        <p>' . __('Select a PDF file you want to sign with signature images.') . '</p>
        <input type="file" id="psi-pdf-file" accept="application/pdf,.pdf">
    </div>

    <div class="psi-step psi-editor" hidden>
        <div class="psi-toolbar">
            <button type="button" class="btn-small" id="psi-zoom-out" title="' . h(__('Zoom out')) . '">&minus;</button>
            <button type="button" class="btn-small" id="psi-zoom-in" title="' . h(__('Zoom in')) . '">+</button>
            <button type="button" class="btn-small" id="psi-zoom-extents">' . __('Zoom extents') . '</button>
            <button type="button" class="btn-small" id="psi-zoom-width">' . __('Zoom width') . '</button>
            <span class="psi-sep"></span>
            <button type="button" class="btn-small" id="psi-prev" title="' . h(__('Previous page')) . '">&lsaquo;</button>
            <span id="psi-page-info">1 / 1</span>
            <button type="button" class="btn-small" id="psi-next" title="' . h(__('Next page')) . '">&rsaquo;</button>
            <span class="psi-sep"></span>
            <button type="button" class="btn" id="psi-add">' . __('Add signature') . '</button>
            <button type="button" class="btn-small" id="psi-remove" disabled>' . __('Remove signature') . '</button>
            <span class="psi-sep"></span>
            <button type="button" class="btn" id="psi-save" disabled>' . __('Save') . '</button>
            <button type="button" class="btn" id="psi-sign">' . __('Sign with certificate') . '</button>
            <button type="button" class="btn-small" id="psi-reset">' . __('Another PDF') . '</button>
            <input type="file" id="psi-image-file" accept="image/*" hidden>
        </div>
        <div class="psi-viewport" id="psi-viewport">
            <div class="psi-stage" id="psi-stage"><canvas id="psi-canvas"></canvas></div>
        </div>
    </div>

    <dialog id="psi-cert-dialog" class="psi-dialog">
        <h5>' . __('Select a certificate') . '</h5>
        <ul id="psi-cert-list"></ul>
        <div class="psi-dialog-footer">
            <button type="button" class="btn-small" id="psi-cert-cancel">' . __('Cancel') . '</button>
            <button type="button" class="btn" id="psi-cert-ok" disabled>' . __('Sign') . '</button>
        </div>
    </dialog>
</div>';

echo $this->Lil->form([
    'title' => __('PDF Sign'),
    'menu' => [
        'merge' => [
            'title' => __('PDF Merge'),
            'active' => false,
            'visible' => true,
            'url' => ['action' => 'pdfMerge'],
        ],
        'sign' => [
            'title' => __('PDF Sign'),
            'active' => true,
            'visible' => true,
            'url' => ['action' => 'pdfSignImages'],
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
], 'App.Utils.PdfSignImages');

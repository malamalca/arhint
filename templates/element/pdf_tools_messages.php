<?php
/**
 * Messages of webroot/js/pdfTools.js, escaped for a data-messages attribute.
 *
 * @var \App\View\AppView $this
 */
echo h((string)json_encode([
    'notPdf' => __('Only pdf files are allowed'),
    'notSinglePdf' => __('Drop a single PDF file.'),
    'loadError' => __('The file could not be opened as a PDF.'),
    'noFiles' => __('Upload a file first!'),
    'noFilename' => __('Please enter target file name!'),
    'pagesShort' => __('pages'),
    'badRange' => __('Invalid page selection. Enter pages between 1 and {0}, e.g. 1,2,3-5.'),
    'encryptedHint' => __('encrypted, can only be processed on the server'),
    'encryptedMerge' => __('At least one file is encrypted and cannot be merged in the browser. '
        . 'Upload the files and merge them on the server?'),
    'encryptedSplit' => __('The file is encrypted and cannot be split in the browser. '
        . 'Upload it and split it on the server?'),
    'processError' => __('Error processing pdf files.'),
    'saveError' => __('Error while saving the PDF.'),
    'saveFallback' => __('That location was refused, so the file was saved to the downloads folder.'),
]));

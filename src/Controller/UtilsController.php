<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\LilPdfProcessor;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Exception;
use ZipArchive;

/**
 * This controller is intended for Utility funcs
 */
class UtilsController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->FormProtection->setConfig('validate', false);
        $this->FormProtection->setConfig('pdfSplice', ['bulk']);
    }

    /**
     * pdfMerge method
     *
     * @return \Cake\Http\Response|null
     */
    public function pdfMerge(): ?Response
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $compression = $this->getRequest()->getData('compression', 'default');
            $pdfa = (bool)$this->getRequest()->getData('pdfa', false);
            $outputPdfFilename = $this->getRequest()->getData('filename', 'merged.pdf');

            $pdfProcessor = new LilPdfProcessor();
            foreach ($this->getRequest()->getData('file') as $file) {
                if (!empty($file) && empty($file->getError())) {
                    $pdfProcessor->addFile($file->getStream()->getMetadata('uri'));
                }
            }

            try {
                $outputFilePath = $pdfProcessor->mergeFiles($outputPdfFilename, $compression, $pdfa);
                $response = $this->getResponse()
                    ->withType('application/json')
                    ->withStringBody((string)json_encode(['filename' => basename($outputFilePath)]));

                return $response;
            } catch (Exception $e) {
                throw new BadRequestException('Error processing pdf files: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * pdfSignImages method
     *
     * Places signature images on a pdf; the pdf is processed in the browser.
     *
     * @return void
     */
    public function pdfSignImages(): void
    {
        $this->Authorization->skipAuthorization();
    }

    /**
     * pdfSplice method
     *
     * @return \Cake\Http\Response|null
     */
    public function pdfSplice(): ?Response
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $file = $this->getRequest()->getData('file');
            if (!empty($file) && !$file->getError()) {
                $clientBasename = pathinfo((string)$file->getClientFilename(), PATHINFO_FILENAME);
                $doMultiPage = (bool)$this->getRequest()->getData('multiPage');

                $pdfProcessor = new LilPdfProcessor();
                $pdfProcessor->addFile($file->getStream()->getMetadata('uri'));
                $extractedFiles = $pdfProcessor->extractPages(
                    (int)$this->getRequest()->getData('firstPage'),
                    (int)$this->getRequest()->getData('lastPage'),
                    $doMultiPage,
                    $clientBasename,
                );

                if ($extractedFiles === false || empty($extractedFiles)) {
                    throw new Exception('Error extracting pages from PDF.');
                }

                if ($doMultiPage) {
                    $downloadFile = $clientBasename . '.zip';

                    $zip = new ZipArchive();
                    $res = $zip->open(TMP . $downloadFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                    if ($res) {
                        foreach ($extractedFiles as $extractedFile) {
                            $zip->addFile($extractedFile, basename($extractedFile));
                        }

                        if ($zip->count() == 0) {
                            throw new Exception('No files in archive.');
                        }
                        $zip->close();
                        foreach ($extractedFiles as $extractedFile) {
                            unlink($extractedFile);
                        }
                    } else {
                        $downloadFile = false;
                    }
                } else {
                    $downloadFile = basename($extractedFiles[0]);
                }

                if (!$downloadFile || !file_exists(TMP . $downloadFile)) {
                    throw new Exception('Download File Does Not Exist.');
                }

                $response = $this->getResponse()
                    ->withFile(TMP . $downloadFile, ['download' => true, 'name' => $downloadFile]);

                return $response;
            } else {
                throw new BadRequestException('Error processing pdf files.');
            }
        }

        return null;
    }
}

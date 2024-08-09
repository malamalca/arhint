<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use ddn\sapp\PDFDoc;

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
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->FormProtection->setConfig('validate', false);
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
            $gsProgram = Configure::read('Ghostscript.executable');
            $gsParams = '-dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dPDFSETTINGS=/' . $compression . ' -sOutputFile=%2$s %1$s';

            if ($this->getRequest()->getData('pdfa')) {
                // slightly different parameters
                $gsParams = '-dPDFA -dNOPAUSE -sDEVICE=pdfwrite -dPDFSETTINGS=/' . $compression . ' ' .
                    '-sColorConversionStrategy=UseDeviceIndependentColor -dPDFACompatibilityPolicy=2 ' .
                    '-sOutputFile=%2$s %1$s';
            }

            $files = $this->getRequest()->getData('file');

            $sourcePDF = [];
            foreach ($files as $file) {
                if (!empty($file) && empty($file->getError())) {
                    $sourcePDF[] = escapeshellarg($file->getStream()->getMetadata('uri'));
                }
            }

            $outputPDF = $this->getRequest()->getData('filename');

            $command = escapeshellarg($gsProgram) . ' ' . $gsParams;
            $command = sprintf($command, implode(' ', $sourcePDF), escapeshellarg(TMP . $outputPDF));

            exec($command);

            $encodedPdf = json_encode(['filename' => $outputPDF]);
            if (!$encodedPdf) {
                throw new BadRequestException('Error processing pdf files.');
            }

            //if ($ret) {
                $response = $this->getResponse()
                    ->withType('application/json')
                    ->withStringBody($encodedPdf);

                    return $response;
            //} else {
            //    throw new BadRequestException('Error processing pdf files.');
            //}
        }

        return null;
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
            $gsProgram = Configure::read('Ghostscript.executable');
            $gsParams = '-dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dFirstPage=%3$s -dLastPage=%4$s -sOutputFile=%2$s %1$s';

            $file = $this->getRequest()->getData('file');
            if (!empty($file) && !$file->getError()) {
                $outputPDF = $file->getClientFilename();

                $command = escapeshellarg($gsProgram) . ' ' . $gsParams;
                $command = sprintf(
                    $command,
                    escapeshellarg($file->getStream()->getMetadata('uri')),
                    escapeshellarg(TMP . $outputPDF),
                    $this->getRequest()->getData('firstPage'),
                    $this->getRequest()->getData('lastPage')
                );

                exec($command);

                $response = $this->getResponse()
                    ->withFile(TMP . $outputPDF, ['download' => true, 'name' => $outputPDF]);

                return $response;
            } else {
                throw new BadRequestException('Error processing pdf files.');
            }
        }

        return null;
    }

    /**
     * Create signature image
     *
     * @param array<string, mixed> $data Certificate data
     * @return string|null
     */
    public function signature(array $data): ?string
    {
        $certData = $data;
        //if (empty($data)) {
        //    openssl_pkcs12_read(file_get_contents(TMP . 'arhim.pfx'), $certData, 'password');
        //    $certData = openssl_x509_parse($certData['cert'], true);
        //}

        $thumbSizeX = 250;
        $thumbSizeY = 105;
        $leftWidth = 70;

        $newImage = imagecreatetruecolor($thumbSizeX, $thumbSizeY);
        if (!$newImage) {
            return null;
        }
        //imageantialias($newImage, true);
        //imagealphablending($newImage, false);
        //imagesavealpha($newImage, true);
        $textColor = '#000000';
        $textColor = (int)imagecolorallocatealpha(
            $newImage,
            (int)hexdec(substr($textColor, 1, 2)),
            (int)hexdec(substr($textColor, 3, 2)),
            (int)hexdec(substr($textColor, 5, 2)),
            0
        );
        $transparent = (int)imagecolorallocate($newImage, 240, 240, 240);
        $gray = (int)imagecolorallocatealpha($newImage, 200, 200, 200, 0);

        imagefilledrectangle($newImage, $leftWidth, 0, $thumbSizeX, $thumbSizeY, $transparent);
        imagefilledrectangle($newImage, 0, 0, $leftWidth - 1, $thumbSizeY, $gray);
        imagefilledrectangle($newImage, 0, 0, $thumbSizeX - 1, 20, $gray);

        $fontFile = constant('WWW_ROOT') . 'font' . constant('DS') . 'arialn.ttf';

        imagettftext($newImage, 10, 0, 5, 15, $textColor, $fontFile, 'DOKUMENT JE ELEKTRONSKO PODPISAN');

        imagettftext($newImage, 10, 0, 5, 35, $textColor, $fontFile, 'Podpisnik:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 35, $textColor, $fontFile, $certData['subject']['O'] .
            ' (' . $certData['subject']['GN'] . ' ' . $certData['subject']['SN'] . ')');

        imagettftext($newImage, 10, 0, 5, 50, $textColor, $fontFile, 'Izdajatelj:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 50, $textColor, $fontFile, $certData['issuer']['CN']);

        imagettftext($newImage, 10, 0, 5, 65, $textColor, $fontFile, 'Št. certifikata: ');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 65, $textColor, $fontFile, $certData['serialNumberHex']);

        $certValidity = (string)(new Date(DateTime::createFromTimestamp($certData['validTo_time_t'])));
        imagettftext($newImage, 10, 0, 5, 80, $textColor, $fontFile, 'Veljavnost:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 80, $textColor, $fontFile, $certValidity);

        imagettftext($newImage, 10, 0, 5, 95, $textColor, $fontFile, 'Čas podpisa:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 95, $textColor, $fontFile, (string)(new DateTime()));

        ob_start();
        imagepng($newImage);
        $imageData = ob_get_contents();
        ob_end_clean();

        imagedestroy($newImage);

        if (empty($data)) {
            header('Content-type:image/png');
            echo $imageData;
            die;
        } else {
            return (string)$imageData;
        }
    }

    /**
     * Base function for signing pdf files
     *
     * @return \Cake\Http\Response|null
     */
    public function pdfSign(): ?Response
    {
        $this->Authorization->skipAuthorization();

        define('__TMP_FOLDER', TMP);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $file = $this->getRequest()->getData('file');
            $cert = $this->getRequest()->getData('cert');

            if (empty($file) || $file->getError() || empty($cert) || $cert->getError()) {
                throw new BadRequestException('Error in uploaded files.');
            }

            $pdfContents = file_get_contents($file->getStream()->getMetadata('uri'));
            $pdfObj = PDFDoc::from_string($pdfContents);

            if ($pdfObj === false) {
                $gsProgram = Configure::read('Ghostscript.executable');
                $gsParams = '-dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%2$s %1$s';

                //$outputPDF = $this->getRequest()->getData('filename');
                $tmpPDF = tempnam(constant('TMP'), 'Sign') . '.pdf';

                $command = escapeshellarg($gsProgram) . ' ' . $gsParams;
                $command = sprintf(
                    $command,
                    escapeshellarg($file->getStream()->getMetadata('uri')),
                    escapeshellarg($tmpPDF)
                );

                $ret = exec($command);
                if ($ret) {
                    $pdfContents = file_get_contents($tmpPDF);
                    $pdfObj = PDFDoc::from_string($pdfContents);
                } else {
                    throw new BadRequestException('Error processing pdf files.');
                }
            }

            if ($pdfObj === false) {
                throw new BadRequestException('Error processing pdf files.');
            } else {
                $pdfSignedContents = false;

                $certBinary = (string)file_get_contents($cert->getStream()->getMetadata('uri'));
                $ret = openssl_pkcs12_read($certBinary, $certdata, (string)$this->getRequest()->getData('pass', ''));
                if ($ret) {
                    $certdata = openssl_x509_parse($certdata['cert'], true);
                    if (!$certdata) {
                        throw new BadRequestException('Certificate Error.');
                    }
                } else {
                    throw new BadRequestException('Certificate Error.');
                }

                $signatureFile = $this->getRequest()->getData('signature');
                if (!empty($signatureFile) && !$signatureFile->getError()) {
                    $image = $signatureFile->getStream()->getMetadata('uri');
                } else {
                    $imageData = $this->signature($certdata);
                    $image = TMP . uniqid() . '.png';
                    file_put_contents($image, $imageData);
                }

                $imagesize = getimagesize($image);
                if ($imagesize === false) {
                    throw new BadRequestException('Filed to open image ' . $image);
                }
                $pagesize = $pdfObj->get_page_size(0);
                if ($pagesize === false) {
                    throw new BadRequestException('Failed to get PDF page size');
                }

                $pagesize = explode(' ', $pagesize[0]->val());
                // Calculate the position of the image according to its size and the size of the page;
                //   the idea is to keep the aspect ratio and center the image in the page with a size
                //   of 1/3 of the size of the page.
                $p_x = intval('' . $pagesize[0]);
                $p_y = intval('' . $pagesize[1]);
                $p_w = intval('' . $pagesize[2]) - $p_x;
                $p_h = intval('' . $pagesize[3]) - $p_y;
                $i_w = $imagesize[0];
                $i_h = $imagesize[1];

                //$ratio_x = $p_w / $i_w;
                //$ratio_y = $p_h / $i_h;

                $i_w = $imagesize[0] / 2;
                $i_h = $imagesize[1] / 2;

                //$p_x = $p_w / 3;
                $p_x = $p_x + round($p_w * $this->getRequest()->getData('x') / 100);
                $p_y = $p_y + round($p_h * $this->getRequest()->getData('y') / 100);
                //$p_y = $p_h / 3;

                // Set the image appearance and the certificate file
                $pdfObj->set_signature_appearance(
                    (int)$this->getRequest()->getData('page'),
                    [ $p_x, $p_y, $p_x + $i_w, $p_y + $i_h ],
                    $image
                );

                $res = $pdfObj->set_signature_certificate(
                    $cert->getStream()->getMetadata('uri'),
                    $this->getRequest()->getData('pass')
                );

                if ($res) {
                    $pdfSignedContents = $pdfObj->to_pdf_file_s(false);

                    if ($pdfSignedContents === false) {
                        throw new BadRequestException('Error signing pdf files.');
                    } else {
                        $signedFilename = substr($file->getClientFilename(), 0, -4) . '_signed.pdf';
                        file_put_contents(TMP . $signedFilename, $pdfSignedContents);

                        return $this->redirect(['controller' => 'Pages', 'action' => 'pdf', $signedFilename]);
                    }
                } else {
                    throw new BadRequestException('Error parsing certificate.');
                }
            }
        }

        return null;
    }
}

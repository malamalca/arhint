<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
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
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Security->setConfig('validatePost', false);

        return null;
    }

    /**
     * index method
     *
     * @return \Cake\Http\Response|null
     */
    public function pdfMerge()
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $gsProgram = Configure::read('Ghostscript.executable');
            $gsParams = '-dBATCH -dNOPAUSE -sDEVICE=pdfwrite -sOutputFile="%2$s" %1$s';

            if ($this->getRequest()->getData('pdfa')) {
                $gsParams = '-dPDFA -dBATCH -dNOPAUSE -sDEVICE=pdfwrite ' .
                    '-sColorConversionStrategy=UseDeviceIndependentColor -dPDFACompatibilityPolicy=2 ' .
                    '-sOutputFile="%2$s" %1$s';
            }

            $sourcePDF = '';
            $files = $this->getRequest()->getData('file');

            foreach ($files as $file) {
                if (is_file($file['tmp_name'])) {
                    $sourcePDF .= '"' . $file['tmp_name'] . '" ';
                }
            }

            $outputPDF = $this->getRequest()->getData('filename');

            $command = sprintf('"' . $gsProgram . '" ' . $gsParams, $sourcePDF, TMP . $outputPDF);

            ///dd($command);
            $ret = shell_exec($command);
            if ($ret) {
                $response = $this->getResponse()
                    ->withType('application/json')
                    ->withStringBody(json_encode(['filename' => $outputPDF]));

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
     * @param array $data Certificate data
     * @return string
     */
    public function signature($data = null)
    {
        $certData = $data;
        if (empty($data)) {
            openssl_pkcs12_read(file_get_contents(TMP . 'arhim.pfx'), $certData, 'arhim3869');
            $certData = openssl_x509_parse($certData['cert'], true);
        }

        $thumbSizeX = 250;
        $thumbSizeY = 105;
        $leftWidth = 70;

        $newImage = imagecreatetruecolor($thumbSizeX, $thumbSizeY);
        //imageantialias($newImage, true);
        //imagealphablending($newImage, false);
        //imagesavealpha($newImage, true);
        $transparent = imagecolorallocate($newImage, 240, 240, 240);
        $gray = imagecolorallocatealpha($newImage, 200, 200, 200, 0);
        imagefilledrectangle($newImage, $leftWidth, 0, $thumbSizeX, $thumbSizeY, $transparent);
        imagefilledrectangle($newImage, 0, 0, $leftWidth - 1, $thumbSizeY, $gray);
        imagefilledrectangle($newImage, 0, 0, $thumbSizeX - 1, 20, $gray);

        $textColor = '#000000';
        $textColor = imagecolorallocatealpha(
            $newImage,
            hexdec(substr($textColor, 1, 2)),
            hexdec(substr($textColor, 3, 2)),
            hexdec(substr($textColor, 5, 2)),
            0
        );
        $fontFile = constant('WWW_ROOT') . 'font' . constant('DS') . 'arialn.ttf';

        imagettftext($newImage, 10, 0, 5, 15, $textColor, $fontFile, 'DOKUMENT JE ELEKTRONSKO PODPISAN');

        imagettftext($newImage, 10, 0, 5, 35, $textColor, $fontFile, 'Podpisnik:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 35, $textColor, $fontFile, $certData['subject']['O'] .
            ' (' . $certData['subject']['GN'] . ' ' . $certData['subject']['SN'] . ')');

        imagettftext($newImage, 10, 0, 5, 50, $textColor, $fontFile, 'Izdajatelj:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 50, $textColor, $fontFile, $certData['issuer']['CN']);

        imagettftext($newImage, 10, 0, 5, 65, $textColor, $fontFile, 'Št. certifikata: ');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 65, $textColor, $fontFile, $certData['serialNumberHex']);

        $certValidity = (string)(new FrozenDate($certData['validTo_time_t']));
        imagettftext($newImage, 10, 0, 5, 80, $textColor, $fontFile, 'Veljavnost:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 80, $textColor, $fontFile, $certValidity);

        imagettftext($newImage, 10, 0, 5, 95, $textColor, $fontFile, 'Čas podpisa:');
        imagettftext($newImage, 10, 0, $leftWidth + 5, 95, $textColor, $fontFile, (string)(new FrozenTime()));

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
            return $imageData;
        }
    }

    /**
     * Base function for signing pdf files
     *
     * @return \Cake\Http\Response|null
     */
    public function pdfSign()
    {
        $this->Authorization->skipAuthorization();

        define('__TMP_FOLDER', TMP);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $file = $this->getRequest()->getData('file');
            $cert = $this->getRequest()->getData('cert');

            $pdfContents = file_get_contents($file['tmp_name']);
            $pdfObj = PDFDoc::from_string($pdfContents);

            if ($pdfObj === false) {
                throw new BadRequestException('Error processing pdf files.');
            } else {
                $pdfSignedContents = false;

                $certBinary = file_get_contents($cert['tmp_name']);
                $ret = openssl_pkcs12_read($certBinary, $certdata, $this->getRequest()->getData('pass'));
                if ($ret) {
                    $certdata = openssl_x509_parse($certdata['cert'], true);
                } else {
                    throw new BadRequestException('Certificate Error.');
                }

                $position = [ ];

                $signatureFile = $this->getRequest()->getData('signature');
                if (!empty($signatureFile['tmp_name']) && is_file($signatureFile['tmp_name'])) {
                    $image = $signatureFile['tmp_name'];
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

                $ratio_x = $p_w / $i_w;
                $ratio_y = $p_h / $i_h;
                $ratio = min($ratio_x, $ratio_y);

                $i_w = $imagesize[0] / 2;
                $i_h = $imagesize[1] / 2;

                //$p_x = $p_w / 3;
                $p_x = $p_x + round($p_w * $this->getRequest()->getData('x') / 100);
                $p_y = $p_y + round($p_h * $this->getRequest()->getData('y') / 100);
                //$p_y = $p_h / 3;

                // Set the image appearance and the certificate file
                $ret = $pdfObj->set_signature_appearance(
                    (int)$this->getRequest()->getData('page'),
                    [ $p_x, $p_y, $p_x + $i_w, $p_y + $i_h ],
                    $image
                );

                $res = $pdfObj->set_signature_certificate($cert['tmp_name'], $this->getRequest()->getData('pass'));

                if ($res) {
                    $pdfSignedContents = $pdfObj->to_pdf_file_s(true);

                    if ($pdfSignedContents === false) {
                        throw new BadRequestException('Error signing pdf files.');
                    } else {
                        $signedFilename = substr($file['name'], 0, -4) . '_signed.pdf';
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

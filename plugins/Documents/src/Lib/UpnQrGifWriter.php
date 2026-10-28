<?php
declare(strict_types=1);

namespace Documents\Lib;

use BaconQrCode\Common\Version;
use BaconQrCode\Encoder\Encoder;
use Endroid\QrCode\Bacon\ErrorCorrectionLevelConverter;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\Matrix\Matrix;
use Endroid\QrCode\Matrix\MatrixInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\GdTrait;
use Endroid\QrCode\Writer\Result\GifResult;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\WriterInterface;

/**
 * Endroid GIF writer retaining the fixed version 15 required by UPN QR.
 */
class UpnQrGifWriter implements WriterInterface
{
    use GdTrait;

    /**
     * @param \Endroid\QrCode\QrCodeInterface $qrCode QR code settings.
     * @return \Endroid\QrCode\Matrix\MatrixInterface
     */
    public function getMatrix(QrCodeInterface $qrCode): MatrixInterface
    {
        $matrix = Encoder::encode(
            $qrCode->getData(),
            ErrorCorrectionLevelConverter::convertToBaconErrorCorrectionLevel($qrCode->getErrorCorrectionLevel()),
            (string)$qrCode->getEncoding(),
            Version::getVersionForNumber(15),
        )->getMatrix();

        $blocks = [];
        $height = $matrix->getHeight();
        $width = $matrix->getWidth();
        for ($row = 0; $row < $height; $row++) {
            for ($column = 0; $column < $width; $column++) {
                $blocks[$row][$column] = $matrix->get($column, $row) === 1 ? 1 : 0;
            }
        }

        return new Matrix($blocks, $qrCode->getSize(), $qrCode->getMargin(), $qrCode->getRoundBlockSizeMode());
    }

    /**
     * @param \Endroid\QrCode\QrCodeInterface $qrCode QR code settings.
     * @param \Endroid\QrCode\Logo\LogoInterface|null $logo Optional logo.
     * @param \Endroid\QrCode\Label\LabelInterface|null $label Optional label.
     * @param array<string, mixed> $options Writer options.
     * @return \Endroid\QrCode\Writer\Result\ResultInterface
     */
    public function write(
        QrCodeInterface $qrCode,
        ?LogoInterface $logo = null,
        ?LabelInterface $label = null,
        array $options = [],
    ): ResultInterface {
        $result = $this->writeGd($qrCode, $logo, $label, $options);

        return new GifResult($result->getMatrix(), $result->getImage());
    }
}

<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\TestSuite\TestCase;
use Documents\Lib\UpnQrGifWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;

class UpnQrGifWriterTest extends TestCase
{
    public function testKeepsVersion15ForShortPayload(): void
    {
        $qrCode = new QrCode(
            data: "UPNQR\nTest\n",
            encoding: new Encoding('ISO-8859-2'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 77,
            margin: 0,
        );
        $result = (new UpnQrGifWriter())->write($qrCode);

        $this->assertSame(77, $result->getMatrix()->getBlockCount());
        $this->assertSame('image/gif', $result->getMimeType());
        $image = getimagesizefromstring($result->getString());
        $this->assertNotFalse($image);
        $this->assertSame(77, $image[0]);
        $this->assertSame(77, $image[1]);
        $this->assertSame(IMAGETYPE_GIF, $image[2]);
    }

    public function testSupportsSlovenianCharactersWithoutPreencoding(): void
    {
        $result = (new UpnQrGifWriter())->write(new QrCode(
            data: "UPNQR\nŠpela Černe\nŽupančičeva ulica\n",
            encoding: new Encoding('ISO-8859-2'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 77,
            margin: 0,
        ));

        $this->assertSame(77, $result->getMatrix()->getBlockCount());
        $this->assertNotFalse(imagecreatefromstring($result->getString()));
    }
}

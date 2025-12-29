<?php
declare(strict_types=1);

/**
 * WKHtml2Pdf LilPdf Engine
 *
 * @category Lib
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
namespace App\Lib\PdfEngines;

use Cake\Utility\Hash;
use mikehaertl\wkhtmlto\Pdf;

/**
 * LilWKHTML2PDFEngine Lib
 *
 * This class manages PDF exporting.
 *
 * @category Lib
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class LilWKHTML2PDFEngine extends Pdf implements LilPdfEngineInterface
{
    /**
     * PDF options
     *
     * @var array<string, mixed>
     */
    private array $_localOptions = [];

    /**
     * @var array<string, mixed>|array<int, string>
     */
    private array $_defaultOptions = [
            'binary' => 'C:\bin\wkhtmltopdf\bin\wkhtmltopdf.exe',
            'no-outline', // Make Chrome not complain
            'print-media-type',
            'margin-top' => 0,
            'margin-right' => 0,
            'margin-bottom' => 0,
            'margin-left' => 0,
            'disable-smart-shrinking',
            //'user-style-sheet' => dirname(dirname(__FILE__)) . DS . 'webroot' . DS . 'css' . DS . 'lil_pdf.css',
    ];

    /**
     * Temporary files
     *
     * @var array<string>
     */
    private array $_tempFiles = [];

    /**
     * __construct
     *
     * @param array<string, mixed> $enigneOptions Array of options.
     * @return void
     */
    public function __construct($enigneOptions)
    {
        $this->options(Hash::merge($this->_defaultOptions, $enigneOptions));
        parent::__construct($enigneOptions);

        if (!empty($enigneOptions['headerHtml'])) {
            $this->setHeaderHtml($enigneOptions['headerHtml']);
        }
        if (!empty($enigneOptions['footerHtml'])) {
            $this->setFooterHtml($enigneOptions['footerHtml']);
        }
    }

    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        foreach ($this->_tempFiles as $fileName) {
            unlink($fileName);
        }
        //parent::__destruct();
    }

    /**
     * Save PDF as file.
     *
     * @param string $fileName Filename.
     * @return bool
     */
    public function saveAs($fileName): bool
    {
        $result = parent::saveAs($fileName);

        return $result;
    }

    /**
     * Add page with html contents
     *
     * @param string $html Html page content.
     * @param array<string, mixed> $options Page options.
     * @return void
     */
    public function newPage($html, array $options = []): void
    {
        $fileName = TMP . uniqid('', true) . '.html';
        file_put_contents($fileName, $html);
        if (file_exists($fileName)) {
            $this->addPage($fileName);
            $this->_tempFiles[] = $fileName;
        } else {
            die('No File');
        }
    }

    /**
     * Get last error.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return parent::getError();
    }

    /**
     * Returns image typ
     *
     * @param string $binary Binary data
     * @return string|bool
     */
    private function getImageType($binary): string|bool
    {
        $types = [
            'jpeg' => "\xFF\xD8\xFF",
            'gif' => 'GIF',
            'png' => "\x89\x50\x4e\x47\x0d\x0a",
        ];

        $found = false;
        foreach ($types as $type => $header) {
            if (strpos($binary, $header) === 0) {
                $found = $type;
                break;
            }
        }

        return $found;
    }

    /**
     * Set page header html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setHeaderHtml($html): void
    {
        if (substr($html, 0, 2) == '{"') {
            $data = json_decode($html, true);
            if ($data) {
                $binary = base64_decode($data['image']);
                $type = $this->getImageType($binary);
                if ($type) {
                    $html = '<img src="data:image/' . $type . ';base64,' . $data['image'] . '" />';
                }
            }
        }

        $this->setOptions(['header-html' => '<!doctype html><head>' .
                            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                            '<html><body><div id="header">' .
                            $html .
                            '</div></body></html>']);
    }

    /**
     * Set page footer html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setFooterHtml($html): void
    {
        if (substr($html, 0, 2) == '{"') {
            $data = json_decode($html, true);
            if ($data) {
                $binary = base64_decode($data['image']);
                $type = $this->getImageType($binary);
                if ($type) {
                    $html = '<img src="data:image/' . $type . ';base64,' . $data['image'] . '" />';
                }
            }
        }

        $this->setOptions(['footer-html' => '<!doctype html><head>' .
                            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head>' .
                            '<html><body><div id="footer">' .
                            $html .
                            '</div></body></html>']);
    }

    /**
     * Get/set options.
     *
     * @param mixed $values Options values.
     * @return mixed
     */
    public function options(mixed $values = null): mixed
    {
        if ($values === null) {
            return $this->_localOptions;
        }
        $this->_localOptions = $values;

        return $this;
    }
}

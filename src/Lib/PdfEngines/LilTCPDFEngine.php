<?php
declare(strict_types=1);

/**
 * TcPDF LilPdf Engine
 *
 * @category Lib
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
namespace App\Lib\PdfEngines;

use Cake\Utility\Hash;
use TCPDF;

/**
 * LilTCPDFEngine Lib
 *
 * This class manages PDF exporting.
 *
 * @category Lib
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class LilTCPDFEngine extends TCPDF implements LilPdfEngineInterface
{
    /**
     * PDF options
     *
     * @var array<string, mixed>
     */
    private array $_options = [];

    /**
     * @var array<string, mixed>
     */
    private array $_defaultOptions = [
        'orientation' => PDF_PAGE_ORIENTATION, // 'P' or 'L'
        'unit' => PDF_UNIT, // default 'mm'
        'format' => PDF_PAGE_FORMAT, // default 'A4'
        'unicode' => true,
        'encoding' => 'UTF-8',
        'diskcache' => false,
        'creator' => 'Lil',
        'author' => 'Lil',
        'title' => 'Lil PDF Report',
        'subject' => 'PDF',
        'keywords' => '',
        'font' => 'dejavusans',
        'font_size' => 10,
        'language' => [
            'a_meta_charset' => 'UTF-8',
            'a_meta_dir' => 'ltr',
            'a_meta_language' => 'sl',
        ],
        'margin' => [
            'left' => PDF_MARGIN_LEFT,
            'top' => PDF_MARGIN_TOP,
            'right' => PDF_MARGIN_RIGHT,
        ],
        'header' => [
            'margin' => PDF_MARGIN_HEADER, // minimum distance between header and top page margin
            'font_size' => 8,
        ],
        'footer' => [
            'margin' => PDF_MARGIN_FOOTER, // minimum distance between footer and bottom page margin
            'font_size' => 8,
        ],
    ];

    /**
     * __construct
     *
     * @param array<string, mixed> $enigneOptions Array of options.
     * @return void
     */
    public function __construct($enigneOptions)
    {
        $this->options(Hash::merge($this->_defaultOptions, $enigneOptions));
        $options = $this->options();
        parent::__construct(
            $options['orientation'],
            $options['unit'],
            $options['format'],
            $options['unicode'],
            $options['encoding'],
            $options['diskcache'],
        );

        mb_internal_encoding('UTF-8');

        $this->SetCreator($options['creator']);
        $this->SetAuthor($options['author']);
        $this->SetTitle($options['title']);
        $this->SetSubject($options['subject']);
        $this->SetKeywords($options['keywords']);

        // lang
        $this->setLanguageArray($options['language']);

        //set auto page breaks
        $this->SetAutoPageBreak(true, $options['footer']['margin']);

        // set font
        $this->SetFont($options['font'], '', $options['font_size']);

        $this->SetCellPadding(2);

        // margins
        $this->SetMargins(
            $options['margin']['left'],
            $options['margin']['top'],
            $options['margin']['right'],
            true, // keep margins
        );

        if (empty($options['header'])) {
            $this->SetPrintHeader(false);
        } else {
            $this->SetHeaderMargin($options['header']['margin']);
        }

        if (empty($options['footer'])) {
            $this->SetPrintFooter(false);
        } else {
            $this->SetFooterMargin($options['footer']['margin']);
        }
    }

    /**
     * Save PDF as file.
     *
     * @param string $fileName Filename.
     * @return bool
     */
    public function saveAs($fileName): bool
    {
        $this->Output($fileName, 'F');

        return true;
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
        $this->addPage();
        $this->writeHTML($html);
    }

    /**
     * Get last error.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return null;
    }

    /**
     * Set page header html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setHeaderHtml($html): void
    {
        $this->_options['header']['data'] = $html;
    }

    /**
     * Set page footer html.
     *
     * @param string $html Html page content.
     * @return void
     */
    public function setFooterHtml($html): void
    {
        $this->_options['footer']['data'] = $html;
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
            return $this->_options;
        }
        $this->_options = $values;

        return $this;
    }

    /**
     * Header
     *
     * @return void
     */
    public function Header(): void // phpcs:ignore
    {
        $this->SetFont($this->_options['font'], '', $this->_options['header']['font_size']);

        // data means base64 encoded image
        if (!empty($this->_options['header']['data'])) {
            if (substr($this->_options['header']['data'], 0, 2) == '{"') {
                $data = json_decode($this->_options['header']['data'], true);
                if ($data) {
                    $margins = $this->getMargins();
                    $decoded = base64_decode($data['image']);
                    $this->Image(
                        '@' . $decoded,
                        $margins['left'],
                        0,
                        $this->getPageWidth() - $margins['left'] - $margins['right'],
                    );
                }
            } else {
                $margins = $this->getMargins();
                $this->writeHTMLCell(
                    $this->getPageWidth() - $margins['left'] - $margins['right'],
                    0,
                    $margins['left'],
                    $this->_options['header']['margin'],
                    $this->_options['header']['data'],
                );
            }
        }

        if (!empty($this->_options['header']['lines'])) {
            foreach ($this->_options['header']['lines'] as $l) {
                if (
                    isset($l['image'])
                    && is_string($l['image'])
                    && file_exists($l['image'])
                ) {
                    $margins = $this->getMargins();
                    $this->Image(
                        $l['image'],
                        $margins['left'],
                        0,
                        $this->getPageWidth()
                        - $margins['left']
                        - $margins['right'],
                    );
                } elseif (
                    isset($l['image'])
                    && is_array($l['image'])
                    && file_exists(reset($l['image']))
                ) {
                    call_user_func_array([$this, 'Image'], $l['image']);
                } elseif (isset($l['text']) && is_array($l['text'])) {
                    call_user_func_array([$this, 'Text'], $l['text']);
                } elseif (isset($l['write']) && is_array($l['write'])) {
                    call_user_func_array([$this, 'Write'], $l['write']);
                } elseif (isset($l['line']) && is_array($l['line'])) {
                    call_user_func_array([$this, 'Line'], $l['line']);
                }
            }
        }

        if (!empty($this->_options['headerHtml'])) {
            $this->writeHTML($this->_options['headerHtml']);
        }
    }

    /**
     * Footer
     *
     * @return void
     */
    public function Footer(): void // phpcs:ignore
    {
        $this->SetFont(
            $this->_options['font'],
            '',
            $this->_options['footer']['font_size'],
        );

        // data means base64 encoded image
        if (!empty($this->_options['footer']['data'])) {
            if (substr($this->_options['footer']['data'], 0, 2) == '{"') {
                $data = json_decode($this->_options['footer']['data'], true);
                if ($data) {
                    $margins = $this->getMargins();
                    $decoded = base64_decode($data['image']);
                    $this->Image(
                        '@' . $decoded,
                        $margins['left'],
                        0,
                        $this->getPageWidth()
                        - $margins['left']
                        - $margins['right'],
                    );
                }
            } else {
                $margins = $this->getMargins();
                $this->writeHTMLCell(
                    $this->getPageWidth() - $margins['left'] - $margins['right'],
                    20,
                    $margins['left'],
                    $this->getPageHeight() - 20,
                    $this->_options['footer']['data'],
                );
            }
        }

        if (!empty($this->_options['footer']['lines'])) {
            foreach ($this->_options['footer']['lines'] as $l) {
                if (
                    isset($l['image'])
                    && is_string($l['image'])
                    && file_exists($l['image'])
                ) {
                    $margins = $this->getMargins();
                    $this->Image(
                        $l['image'],
                        $margins['left'],
                        275,
                        $this->getPageWidth()
                        - $margins['left']
                        - $margins['right'],
                    );
                } elseif (
                    isset($l['image'])
                    && is_array($l['image'])
                    && file_exists(reset($l['image']))
                ) {
                    call_user_func_array([$this, 'Image'], $l['image']);
                } elseif (isset($l['text']) && is_array($l['text'])) {
                    call_user_func_array([$this, 'Text'], $l['text']);
                } elseif (isset($l['write']) && is_array($l['write'])) {
                    call_user_func_array([$this, 'Write'], $l['write']);
                } elseif (isset($l['line']) && is_array($l['line'])) {
                    call_user_func_array([$this, 'Line'], $l['line']);
                }
            }
        }

        if (!empty($this->_options['footerHtml'])) {
            $this->writeHTML($this->_options['footerHtml']);
        }
    }
}

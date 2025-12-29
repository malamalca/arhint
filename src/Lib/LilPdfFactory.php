<?php
declare(strict_types=1);

namespace App\Lib;

use App\Lib\PdfEngines\LilPdfEngineInterface;
use App\Lib\PdfEngines\LilTCPDFEngine;
use App\Lib\PdfEngines\LilWKHTML2PDFEngine;

/**
 * Factory class for PDF library.
 *
 * @category Lib
 * @package  Lil
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class LilPdfFactory
{
    /**
     * Create PDF engine instance.
     *
     * @param string $engine PDF engine name.
     * @param array<string, mixed> $enigneOptions PDF engine options.
     * @return \App\Lib\PdfEngines\LilPdfEngineInterface PDF engine instance.
     */
    public static function create($engine, $enigneOptions): LilPdfEngineInterface
    {
        switch ($engine) {
            case 'TCPDF':
                return new LilTCPDFEngine($enigneOptions);
            default:
                return new LilWKHTML2PDFEngine($enigneOptions);
        }
    }
}

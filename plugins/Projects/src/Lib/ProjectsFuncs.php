<?php
declare(strict_types=1);

namespace Projects\Lib;

use Cake\I18n\Number;

class ProjectsFuncs
{
    protected const THUMB_SIZE = 50;

    /**
     * Return thumbnail image for specified project
     *
     * @param \Projects\Model\Entity\Project $project Project
     * @param int $thumbSize Thumbnail size
     * @return mixed
     */
    public static function thumb($project, $thumbSize = self::THUMB_SIZE)
    {
        if (empty($project->ico)) {
            $newImage = imagecreatetruecolor($thumbSize, $thumbSize);
            imagealphablending($newImage, true);
            imagesavealpha($newImage, true);

            $white = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $white);

            $textColor = empty($project->colorize) ? '#ffffff' : $project->colorize;
            $textColor = imagecolorallocatealpha(
                $newImage,
                hexdec(substr($textColor, 1, 2)),
                hexdec(substr($textColor, 3, 2)),
                hexdec(substr($textColor, 5, 2)),
                0
            );

            $caption = mb_substr($project->title, 0, 1);
            $parts = explode(' ', $project->title);
            $caption .= mb_substr($parts[count($parts) - 1], 0, 1);

            $fontFile = constant('WWW_ROOT') . 'font' . constant('DS') . 'arialbd.ttf';
            imagettftext(
                $newImage,
                (int)($thumbSize * 0.55),
                0,
                0,
                (int)(0.75 * $thumbSize),
                $textColor,
                $fontFile,
                strtoupper($caption)
            );
        } else {
            $im = imagecreatefromstring(base64_decode($project->ico));
            $width = imagesx($im);
            $height = imagesy($im);

            if ($width > $height) {
                $newHeight = $thumbSize;
                $newWidth = (int)floor($width * $newHeight / $height);
                $cropX = (int)ceil(($width - $height) / 2);
                $cropY = 0;
            } else {
                $newWidth = $thumbSize;
                $newHeight = (int)floor($height * $newWidth / $width);
                $cropX = 0;
                $cropY = (int)ceil(($height - $width) / 2);
            }

            $newImage = imagecreatetruecolor($thumbSize, $thumbSize);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $thumbSize, $thumbSize, $transparent);
            imagecopyresampled($newImage, $im, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);

            if (!empty($project->colorize)) {
                imagefilter(
                    $newImage,
                    IMG_FILTER_COLORIZE,
                    hexdec(substr($project->colorize, 1, 2)),
                    hexdec(substr($project->colorize, 3, 2)),
                    hexdec(substr($project->colorize, 5, 2))
                );
            }
        }

        $im = $newImage;

        ob_start();
        imagepng($im);
        $imageData = ob_get_contents();
        ob_end_clean();
        imagedestroy($im);

        return $imageData;
    }

    /**
     * Return word document with compositest
     *
     * @param mixed $projectsComposites Projects Composites list
     * @return string
     */
    public static function exportComposites2Word($projectsComposites)
    {
        $phpWord = new \PhpOffice\PhpWord\PhpWord();

        $tabs = [
            new \PhpOffice\PhpWord\Style\Tab('left', 1000),
            new \PhpOffice\PhpWord\Style\Tab('right', 7500),
            new \PhpOffice\PhpWord\Style\Tab('left', 7800),
        ];

        // Define styles
        $titleParagraphStyleName = 'Title Paragraph';
        $phpWord->addParagraphStyle(
            $titleParagraphStyleName,
            ['tabs' => $tabs, 'borderBottomSize' => 6, 'spaceAfter' => 0],
        );

        $textParagraphStyleName = 'Text Paragraph';
        $phpWord->addParagraphStyle(
            $textParagraphStyleName,
            ['tabs' => $tabs, 'spaceBefore' => 0, 'spaceAfter' => 0],
        );

        $footerParagraphStyleName = 'Footer Paragraph';
        $phpWord->addParagraphStyle(
            $footerParagraphStyleName,
            ['tabs' => [
                new \PhpOffice\PhpWord\Style\Tab('right', 6800),
                new \PhpOffice\PhpWord\Style\Tab('right', 7500),
                new \PhpOffice\PhpWord\Style\Tab('left', 7800),
            ], 'borderTopSize' => 6, ],
        );

        $fontStyleTitle = 'Composite Title';
        $phpWord->addFontStyle(
            $fontStyleTitle,
            ['name' => 'Open Sans Condensed', 'size' => 12, 'bold' => true, 'borderBottomSize' => 1]
        );

        $fontStyleMaterial = 'Composite Material';
        $phpWord->addFontStyle(
            $fontStyleMaterial,
            ['name' => 'Open Sans Condensed Light', 'size' => 11, 'bold' => false]
        );

        $fontStyleFooter = 'Composite Footer';
        $phpWord->addFontStyle(
            $fontStyleFooter,
            ['name' => 'Open Sans Condensed', 'size' => 11, 'bold' => true, 'borderBottomSize' => 1]
        );

        // New portrait section
        $section = $phpWord->addSection();

        foreach ($projectsComposites as $composite) {
            $totalThickness = 0;
            $section->addText(
                implode("\t", [$composite->no, $composite->title]),
                $fontStyleTitle,
                $titleParagraphStyleName
            );

            foreach ($composite->composites_materials as $i => $material) {
                $textlines = explode(PHP_EOL, $material->descript);
                $section->addText(
                    implode(
                        "\t",
                        ['-', $textlines[0], Number::precision((float)$material->thickness, 2), 'cm']
                    ),
                    $fontStyleMaterial,
                    $textParagraphStyleName
                );

                if (count($textlines) > 1) {
                    array_shift($textlines);
                    foreach ($textlines as $line) {
                        $section->addText("\t" . $line, $fontStyleMaterial, $textParagraphStyleName);
                    }
                }

                $totalThickness += $material->thickness;
            }

            $section->addText(
                "\t" . implode(
                    "\t",
                    [__d('projects', 'Total Thickness') . ':', Number::precision($totalThickness, 2), 'cm']
                ),
                $fontStyleFooter,
                $footerParagraphStyleName
            );

            $section->addTextBreak(1, $fontStyleMaterial, $textParagraphStyleName);
        }

        // Save file
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');

        ob_start();
        $objWriter->save('php://output');
        $ret = ob_get_contents();
        ob_end_clean();

        return $ret;

        //$objWriter->save(TMP . DS . 'composites.docx');
    }
}

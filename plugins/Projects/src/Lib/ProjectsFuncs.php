<?php
declare(strict_types=1);

namespace Projects\Lib;

use Exception;
use Projects\Model\Entity\Project;

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
    public static function thumb(Project $project, int $thumbSize = self::THUMB_SIZE): mixed
    {
        if (empty($project->ico)) {
            $newImage = imagecreatetruecolor($thumbSize, $thumbSize);
            if (!$newImage) {
                throw new Exception('Error creating GD image.');
            }

            imagealphablending($newImage, true);
            imagesavealpha($newImage, true);

            $textColor = empty($project->colorize) ? '#ffffff' : $project->colorize;
            $textColor = (int)imagecolorallocatealpha(
                $newImage,
                (int)hexdec(substr($textColor, 1, 2)),
                (int)hexdec(substr($textColor, 3, 2)),
                (int)hexdec(substr($textColor, 5, 2)),
                0
            );
            $white = (int)imagecolorallocatealpha($newImage, 255, 255, 255, 127);

            imagefill($newImage, 0, 0, $white);

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
            if (!$im) {
                throw new Exception('Error creating GD image.');
            }

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
            if (!$newImage) {
                throw new Exception('Error creating GD image.');
            }

            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);

            $transparent = (int)imagecolorallocatealpha($newImage, 255, 255, 255, 127);

            imagefilledrectangle($newImage, 0, 0, $thumbSize, $thumbSize, $transparent);
            imagecopyresampled($newImage, $im, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);

            if (!empty($project->colorize)) {
                imagefilter(
                    $newImage,
                    IMG_FILTER_COLORIZE,
                    (int)hexdec(substr($project->colorize, 1, 2)),
                    (int)hexdec(substr($project->colorize, 3, 2)),
                    (int)hexdec(substr($project->colorize, 5, 2))
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
}

<?php
declare(strict_types=1);

namespace LilProjects\Lib;

class LilProjectsFuncs
{
    protected const THUMB_SIZE = 50;
    /**
     * Return thumbnail image for specified project
     *
     * @param \LilProjects\Model\Entity\Project $project Project
     * @return mixed
     */
    public static function thumb($project)
    {
        if (empty($project->ico)) {
            $newImage = imagecreatetruecolor(50, 50);
            imagealphablending($newImage, true);
            imagesavealpha($newImage, true);

            $white = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefill($newImage, 0, 0, $white);

            $lime = imagecolorallocatealpha($newImage, 255, 255, 255, 0);

            $caption = mb_substr($project->title, 0, 1);
            $parts = explode(' ', $project->title);
            $caption .= mb_substr($parts[count($parts) - 1], 0, 1);

            $fontFile = constant('WWW_ROOT') . 'font' . constant('DS') . 'arialbd.ttf';
            imagettftext($newImage, 28, 0, 0, 38, $lime, $fontFile, strtoupper($caption));
        } else {
            $im = imagecreatefromstring(base64_decode($project->ico));
            $width = imagesx($im);
            $height = imagesy($im);

            if ($width > $height) {
                $newHeight = self::THUMB_SIZE;
                $newWidth = (int)floor($width * $newHeight / $height);
                $cropX = (int)ceil(($width - $height) / 2);
                $cropY = 0;
            } else {
                $newWidth = self::THUMB_SIZE;
                $newHeight = (int)floor($height * $newWidth / $width);
                $cropX = 0;
                $cropY = (int)ceil(($height - $width) / 2);
            }

            $newImage = imagecreatetruecolor(self::THUMB_SIZE, self::THUMB_SIZE);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, self::THUMB_SIZE, self::THUMB_SIZE, $transparent);
            imagecopyresampled($newImage, $im, 0, 0, $cropX, $cropY, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);
        }

        if (!empty($project->colorize)) {
            imagefilter(
                $newImage,
                IMG_FILTER_COLORIZE,
                hexdec(substr($project->colorize, 0, 2)),
                hexdec(substr($project->colorize, 2, 2)),
                hexdec(substr($project->colorize, 4, 2))
            );
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

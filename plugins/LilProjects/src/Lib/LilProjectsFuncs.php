<?php
declare(strict_types=1);

namespace LilProjects\Lib;

class LilProjectsFuncs
{
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
            $ratio = 50 / $height;

            $newWidth = (int)round($width * $ratio);
            $newHeight = 50;

            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
            imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            imagecopyresampled($newImage, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
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

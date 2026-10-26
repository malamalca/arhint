<?php
declare(strict_types=1);

namespace Projects\Lib;

use Exception;
use Projects\Model\Entity\Project;

class ProjectsFuncs
{
    protected const THUMB_SIZE = 50;

    public const STATUS_OPEN = 1;
    public const STATUS_REOPENED = 2;
    public const STATUS_CLOSED = 3;
    public const STATUS_INVALID = 4;

    /**
     * Returns an array of task statuses for use in dropdowns and similar UI elements.
     *
     * @return array<int, string> Associative array of status ID => status label.
     */
    public static function getTaskStatuses(): array
    {
        return [
            self::STATUS_OPEN => __d('projects', 'Open'),
            self::STATUS_REOPENED => __d('projects', 'Reopened'),
            self::STATUS_CLOSED => __d('projects', 'Closed'),
            self::STATUS_INVALID => __d('projects', 'Invalid'),
        ];
    }

    /**
     * Color of a project: the one set on the project, or a color made from the number of the project when it
     * has none (empty, invalid or black, which is what the color input of the form submits by default).
     *
     * The hue is the number of the project times the golden angle, so consecutive numbers get colors that are
     * far apart; saturation and lightness are fixed so every generated color reads well on a light background.
     *
     * @param \Projects\Model\Entity\Project $project Project
     * @return string Color as #rrggbb.
     */
    public static function colorFor(Project $project): string
    {
        // The color input of the form submits black when no color was picked, so black counts as not set.
        $color = (string)$project->colorize;
        if (preg_match('/^#[0-9a-f]{6}$/i', $color) === 1 && strtolower($color) !== '#000000') {
            return $color;
        }

        $digits = substr((string)preg_replace('/\D/', '', (string)$project->no), -9);
        $seed = $digits !== '' ? (int)$digits : crc32((string)$project->title);

        return self::hsl(fmod($seed * 137.508, 360), 0.55, 0.42);
    }

    /**
     * Convert a color from HSL to #rrggbb.
     *
     * @param float $hue Hue in degrees, 0 to 360.
     * @param float $saturation Saturation, 0 to 1.
     * @param float $lightness Lightness, 0 to 1.
     * @return string
     */
    private static function hsl(float $hue, float $saturation, float $lightness): string
    {
        $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
        $x = $chroma * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $lightness - $chroma / 2;

        [$red, $green, $blue] = match ((int)floor($hue / 60) % 6) {
            0 => [$chroma, $x, 0],
            1 => [$x, $chroma, 0],
            2 => [0, $chroma, $x],
            3 => [0, $x, $chroma],
            4 => [$x, 0, $chroma],
            default => [$chroma, 0, $x],
        };

        return sprintf(
            '#%02x%02x%02x',
            (int)round(($red + $m) * 255),
            (int)round(($green + $m) * 255),
            (int)round(($blue + $m) * 255),
        );
    }

    /**
     * Return thumbnail image for specified project
     *
     * @param \Projects\Model\Entity\Project $project Project
     * @param int $thumbSize Thumbnail size
     * @return mixed
     */
    public static function thumb(Project $project, int $thumbSize = self::THUMB_SIZE): mixed
    {
        $thumbSize = max(1, $thumbSize);

        if (empty($project->ico)) {
            $newImage = imagecreatetruecolor($thumbSize, $thumbSize);
            if (!$newImage) {
                throw new Exception('Error creating GD image.');
            }

            imagealphablending($newImage, true);
            imagesavealpha($newImage, true);

            $textColor = self::colorFor($project);
            $textColor = (int)imagecolorallocatealpha(
                $newImage,
                min(255, max(0, (int)hexdec(substr($textColor, 1, 2)))),
                min(255, max(0, (int)hexdec(substr($textColor, 3, 2)))),
                min(255, max(0, (int)hexdec(substr($textColor, 5, 2)))),
                0,
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
                strtoupper($caption),
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

            if (preg_match('/^#[0-9a-f]{6}$/i', (string)$project->colorize) === 1) {
                imagefilter(
                    $newImage,
                    IMG_FILTER_COLORIZE,
                    (int)hexdec(substr($project->colorize, 1, 2)),
                    (int)hexdec(substr($project->colorize, 3, 2)),
                    (int)hexdec(substr($project->colorize, 5, 2)),
                );
            }
        }

        $im = $newImage;

        ob_start();
        imagepng($im);
        $imageData = ob_get_contents();
        ob_end_clean();

        return $imageData;
    }
}

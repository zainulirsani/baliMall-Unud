<?php

namespace App\Traits;

trait ImageProcessorTrait
{
    protected function rotateImage($resource): void
    {
        $exif = @exif_read_data($resource);

        if (!empty($exif['Orientation'])) {
            $contentType = exif_imagetype($resource);
            $imageType = 'jpeg';
            $rotate = 0;

            if ($contentType === IMAGETYPE_GIF) {
                $imageType = 'gif';
            } elseif ($contentType === IMAGETYPE_PNG) {
                $imageType = 'png';
            }

            if ($exif['Orientation'] === 3) {
                $rotate = 180;
            } elseif ($exif['Orientation'] === 6) {
                $rotate = -90;
            } elseif ($exif['Orientation'] === 8) {
                $rotate = 90;
            }

            switch ($imageType) {
                case 'gif':
                    $file = imagecreatefromgif($resource);
                    $image = imagerotate($file, $rotate, 0);
                    imagegif($image, $resource);
                    break;
                case 'png':
                    $file = imagecreatefrompng($resource);
                    $image = imagerotate($file, $rotate, 0);
                    imagepng($image, $resource);
                    break;
                default:
                    $file = imagecreatefromjpeg($resource);
                    $image = imagerotate($file, $rotate, 0);
                    imagejpeg($image, $resource);
                    break;
            }

            imagedestroy($file);
        }
    }

    protected function resizeImage($resource, $config): void
    {
        $width = !empty($config['width']) ? $config['width'] : $config['height'];
        $height = !empty($config['height']) ? $config['height'] : $config['width'];
        $contentType = exif_imagetype($resource);
        $imageType = 'jpeg';

        if ($contentType === IMAGETYPE_GIF) {
            $imageType = 'gif';
        } elseif ($contentType === IMAGETYPE_PNG) {
            $imageType = 'png';
        }

        [$widthSrc, $heightSrc] = getimagesize($resource);

        $ratio = $widthSrc / $heightSrc;

        if ($width / $height > $ratio) {
            $width = $height * $ratio;
        } else {
            $height = $width / $ratio;
        }

        $file = imagecreatetruecolor($width, $height);

        switch ($imageType) {
            case 'gif':
                $image = imagecreatefromgif($resource);
                imagecopyresampled($file, $image, 0, 0, 0, 0, $width, $height, $widthSrc, $heightSrc);
                imagegif($file, $resource);
                break;
            case 'png':
                $image = imagecreatefrompng($resource);
                imagecopyresampled($file, $image, 0, 0, 0, 0, $width, $height, $widthSrc, $heightSrc);
                imagepng($file, $resource, 100);
                break;
            default:
                $image = imagecreatefromjpeg($resource);
                imagecopyresampled($file, $image, 0, 0, 0, 0, $width, $height, $widthSrc, $heightSrc);
                imagejpeg($file, $resource, 100);
                break;
        }

        imagedestroy($file);
        imagedestroy($image);
    }
}

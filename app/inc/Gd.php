<?php
namespace app\inc;

/**
 *
 * @author andrey.a.sirotkin@megafon.ru
 */
class Gd
{

    /**
     *
     * @param \GdImage $image
     * @param int $xFrom
     * @param int $yFrom
     * @param int $xTo
     * @param int $yTo
     *
     * @return \GdImage
     */
    public static function clipBw(\GdImage $image, int $xFrom, int $yFrom, int $xTo, int $yTo, int $avg = 0)
    {
        if (!$avg) {
            $avg = self::getAvg($image, $xFrom, $yFrom, $xTo, $yTo);
        }
        $xMin = $xTo;
        $xMax = 0;
        $yMin = $yTo;
        $yMax = 0;
        $founrBlack = false;
        for ($x = $xFrom; $x < $xTo; $x ++) {
            $isWhite = true;
            for ($y = $yFrom; $y < $yTo; $y ++) {
                if (!self::isWhite($image, $x, $y, $avg)) {
                    $isWhite = false;
                    break;
                }
            }
            if ($isWhite) {
                if ($founrBlack) {
                    $xMax = $x;
                    break;
                }
            } else  if (!$founrBlack) {
                $founrBlack = true;
                $xMin = $x;
            }
        }
        if ($xMax > $xMin) {
            $xFrom = $xMin;
            $xTo   = $xMax;
        }

        $founrBlack = false;
        for ($y = $yFrom; $y < $yTo; $y ++) {
            $isWhite = true;
            for ($x = $xFrom; $x < $xTo; $x ++) {
                if (!self::isWhite($image, $x, $y, $avg)) {
                    $isWhite = false;
                    break;
                }
            }
            if ($isWhite) {
                if ($founrBlack) {
                    $yMax = $y;
                    break;
                }
            } else if (!$founrBlack) {
                $founrBlack = true;
                $yMin = $y;
            }
        }
        if ($yMax > $yMin) {
            $yFrom = $yMin;
            $yTo   = $yMax;
        }
        $imageOut = imagecreatetruecolor($xTo - $xFrom, $yTo - $yFrom);
        $white    = imagecolorallocate($imageOut, 255, 255, 255);
        for ($x = $xFrom; $x <= $xTo; $x ++) {
            for ($y = $yFrom; $y <= $yTo; $y ++) {
                if (self::isWhite($image, $x, $y, $avg)) {
                    imagesetpixel($imageOut, $x - $xFrom, $y - $yFrom, $white);
                }
            }
        }
        return $imageOut;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $x
     * @param int $y
     * @param int $avg
     * @return boolean
     */
    public static function isNegative(\GdImage $image, int $x, int $y, int $avg = 576)
    {
        return array_sum(self::getRGB($image, $x, $y)) < $avg;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $x
     * @param int $y
     * @return boolean
     */
    public static function isWhite(\GdImage $image, int $x, int $y, int $avg = 384)
    {
        $sum = array_sum(self::getRGB($image, $x, $y));
        return $avg >= 384 ? $sum > $avg : $sum < $avg;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $xFrom
     * @param int $yFrom
     * @param int $xTo
     * @param int $yTo
     * @return number
     */
    public static function getAvg(\GdImage $image, int $xFrom, int $yFrom, int $xTo, int $yTo)
    {
        $cnt = 0;
        $sum = 0;
        for ($x = $xFrom; $x < $xTo; $x ++) {
            for ($y = $yFrom; $y < $yTo; $y ++) {
                $cnt ++;
                $sum += array_sum(Gd::getRGB($image, $x, $y));
            }
        }
        return intval($sum / $cnt);
    }

    /**
     *
     * @param string $filename
     * @param string $type
     * @return \GdImage|boolean
     * @throws
     */
    public static function getImageFromFile(string $filename = "", $type = "image/jpeg")
    {
        if ("image/jpeg" == $type) {
            $image  = @imagecreatefromjpeg($filename);
        } else if ("image/webp" == $type) {
            $image  = @imagecreatefromwebp($filename);
        } else {
            throw new \Exception("Не известный тип файла: {$type}");
        }
        if (!$image) {
            throw new \Exception("Не удалось распознать файл: {$filename}");
        }
        $width  = imagesx($image);
        if (864 == $width) {
            $imageSrc   = $image;
            $image      = imagecreatetruecolor(576, 1280);
            imagecopyresampled($image, $imageSrc, 0, 0, 0, 0, 576, 1280, 864, 1920);
        }
        return $image;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $x
     * @param int $y
     * @return array
     */
    public static function getRGB(\GdImage $image, int $x, int $y)
    {
        $rgb = imagecolorat($image, $x, $y);
        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

}
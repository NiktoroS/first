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
     * @param int $x
     * @param int $y
     * @return array
     */
    public static function getRGB($image, int $x, int $y)
    {
        $rgb = imagecolorat($image, $x, $y);
        return [($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF];
    }

    /**
     *
     * @param \GdImage $image
     * @param int $x
     * @param int $y
     * @param int $limit
     * @return boolean
     */
    public static function isNegative($image, int $x, int $y, int $limit = 576)
    {
        return array_sum(self::getRGB($image, $x, $y)) < $limit;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $x
     * @param int $y
     * @param bool $isNegative
     * @return boolean
     */
    public static function isWhite($image, int $x, int $y, bool $isNegative = false)
    {
        return self::isNegative($image, $x, $y, $isNegative ? 576 : 192) === $isNegative;
    }

    /**
     *
     * @param \GdImage $image
     * @param int $xFrom
     * @param int $yFrom
     * @param int $xTo
     * @param int $yTo
     * @param bool $isNegative
     * @return \GdImage
     */
    public static function clipForX($image, int $xFrom, int $yFrom, int $xTo, int $yTo, $isNegative = false)
    {
        $xMin = $xTo;
        $xMax = 0;
        for ($x = $xFrom; $x < $xTo; $x ++) {
            for ($y = $yFrom; $y < $yTo; $y ++) {
                if (self::isWhite($image, $x, $y, $isNegative)) {
                    continue;
                }
                if ($xMin > $x) {
                    $xMin = $x;
                }
                if ($xMax < $x) {
                    $xMax = $x;
                }
            }
        }
        $imageOut = imagecreatetruecolor($xMax + 1 - $xMin, $yTo - $yFrom);
        $white    = imagecolorallocate($imageOut, 255, 255, 255);
        for ($x = $xMin; $x <= $xMax; $x ++) {
            for ($y = $yFrom; $y <= $yTo; $y ++) {
                if (self::isWhite($image, $x, $y, $isNegative)) {
                    imagesetpixel($imageOut, $x - $xMin, $y - $yFrom, $white);
                }
            }
        }
        return $imageOut;
    }

}
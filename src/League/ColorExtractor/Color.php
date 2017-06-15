<?php

namespace League\ColorExtractor;

class Color
{
    /**
     * @param int  $color
     * @param bool $prependHash = true
     *
     * @return string
     */
    public static function fromIntToHex($color, $prependHash = true)
    {
        return ($prependHash ? '#' : '').sprintf('%06X', $color);
    }

    /**
     * @param string $color
     *
     * @return int
     */
    public static function fromHexToInt($color)
    {
        return hexdec(ltrim($color, '#'));
    }

    /**
     * @param int $color
     *
     * @return array
     */
    public static function fromIntToRgb($color)
    {
        return [
            'r' => $color >> 16 & 0xFF,
            'g' => $color >> 8 & 0xFF,
            'b' => $color & 0xFF,
        ];
    }

    /**
     * @param array $components
     *
     * @return int
     */
    public static function fromRgbToInt(array $components)
    {
        return ($components['r'] * 65536) + ($components['g'] * 256) + ($components['b']);
    }

    /**
     * @param int $color
     *
     * @return array
     */
    public static function intColorToLab($color)
    {
        return self::xyzToLab(
            self::srgbToXyz(
                self::rgbToSrgb(
                    [
                        'R' => ($color >> 16) & 0xFF,
                        'G' => ($color >> 8) & 0xFF,
                        'B' => $color & 0xFF,
                    ]
                )
            )
        );
    }

    /**
     * @param int $value
     *
     * @return float
     */
    protected static function rgbToSrgbStep($value)
    {
        $value /= 255;

        return $value <= .03928 ?
            $value / 12.92 :
            pow(($value + .055) / 1.055, 2.4);
    }

    /**
     * @param array $rgb
     *
     * @return array
     */
    public static function rgbToSrgb($rgb)
    {
        return [
            'R' => self::rgbToSrgbStep($rgb['R']),
            'G' => self::rgbToSrgbStep($rgb['G']),
            'B' => self::rgbToSrgbStep($rgb['B']),
        ];
    }

    /**
     * @param float $value
     *
     * @return int
     */
    public static function srgbToRgbStep($value)
    {
        $value = $value * 12.92 <= .03928 ?
            $value * 12.92 :
            pow($value, 1 / 2.4) * 1.055 - .055;

        return round($value * 255);
    }

    /**
     * @param $srgb
     *
     * @return array
     */
    public static function srgbToRgb($srgb)
    {
        return [
            'R' => self::srgbToRgbStep($srgb['R']),
            'G' => self::srgbToRgbStep($srgb['G']),
            'B' => self::srgbToRgbStep($srgb['B']),
        ];
    }

    /**
     * @param array $rgb
     *
     * @return array
     */
    public static function srgbToXyz($rgb)
    {
        return [
            'X' => (.4124564 * $rgb['R']) + (.3575761 * $rgb['G']) + (.1804375 * $rgb['B']),
            'Y' => (.2126729 * $rgb['R']) + (.7151522 * $rgb['G']) + (.0721750 * $rgb['B']),
            'Z' => (.0193339 * $rgb['R']) + (.1191920 * $rgb['G']) + (.9503041 * $rgb['B']),
        ];
    }

    /**
     * @param $xyz
     *
     * @return array
     */
    public static function xyzToSrgb($xyz)
    {
        return [
            'R' => (3.2404548360214087 * $xyz['X']) + (-1.537138850102575 * $xyz['Y']) + (-0.4985315468684809 * $xyz['Z']),
            'G' => (-0.9692663898756537 * $xyz['X']) + (1.876010928842491 * $xyz['Y']) + ( 0.04155608234667351 * $xyz['Z']),
            'B' => (0.055643419604213644 * $xyz['X']) + (-0.20402585426769815 * $xyz['Y']) + (1.0572251624579287 * $xyz['Z']),
        ];
    }

    /**
     * @param float $value
     *
     * @return float
     */
    protected static function xyzToLabStep($value)
    {
        return $value > 216 / 24389 ? pow($value, 1 / 3) : 841 * $value / 108 + 4 / 29;
    }

    /**
     * @param array $xyz
     *
     * @return array
     */
    public static function xyzToLab($xyz)
    {
        //http://en.wikipedia.org/wiki/Illuminant_D65#Definition
        $Xn = .95047;
        $Yn = 1;
        $Zn = 1.08883;

        // http://en.wikipedia.org/wiki/Lab_color_space#CIELAB-CIEXYZ_conversions
        return [
            'L' => 116 * self::xyzToLabStep($xyz['Y'] / $Yn) - 16,
            'a' => 500 * (self::xyzToLabStep($xyz['X'] / $Xn) - self::xyzToLabStep($xyz['Y'] / $Yn)),
            'b' => 200 * (self::xyzToLabStep($xyz['Y'] / $Yn) - self::xyzToLabStep($xyz['Z'] / $Zn)),
        ];
    }

    /**
     * @param float $value
     *
     * @return float
     */
    protected static function labToXyzStep($value)
    {
        return pow($value, 3) > 216 / 24389 ? pow($value, 3) : ($value - 4 / 29) * 108 / 841;
    }

    /**
     * @param array $lab
     *
     * @return array
     */
    public static function labToXyz($lab)
    {
        $Xn = .95047;
        $Yn = 1;
        $Zn = 1.08883;

        $Y = ($lab['L'] + 16) / 116;
        $X = $lab['a'] / 500 + $Y;
        $Z = $Y - $lab['b'] / 200;

        return [
            'X' => $Xn * self::labToXyzStep($X),
            'Y' => $Yn * self::labToXyzStep($Y),
            'Z' => $Zn * self::labToXyzStep($Z),
        ];
    }

    /**
     * @param array $lab
     *
     * @return array
     */
    public static function labToRgb($lab)
    {
        return self::srgbToRgb(
            self::xyzToSrgb(
                self::labToXyz($lab)
            )
        );
    }
}

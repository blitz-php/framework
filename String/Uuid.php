<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Utilities\String;

/**
 * @credit		https://www.php.net/manual/en/function.uniqid.php#94959
 */
class Uuid
{
    /**
     * Génère un UUID de type V3
     *
     * @return false|string
     */
    public static function v3(string $namespace, string $name)
    {
        if (! static::isValid($namespace)) {
            return false;
        }

        $nhex = str_replace(['-', '{', '}'], '', $namespace);

        $nstr = '';

        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        $hash = md5($nstr . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x3000,
            (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    /**
     * Génère un UUID de type V4
     */
    public static function v4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }

    /**
     * Génère un UUID de type V5
     *
     * @return false|string
     */
    public static function v5(string $namespace, string $name)
    {
        if (! static::isValid($namespace)) {
            return false;
        }

        $nhex = str_replace(['-', '{', '}'], '', $namespace);

        $nstr = '';

        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        $hash = sha1($nstr . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0FFF) | 0x5000,
            (hexdec(substr($hash, 16, 4)) & 0x3FFF) | 0x8000,
            substr($hash, 20, 12)
        );
    }

    /**
     * Verifie si une chaîne donnée est un UUID valide.
     *
     * @param string $value
     */
    public static function isValid($value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/iD', $value) === 1;
    }
}

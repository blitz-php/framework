<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Spec;

use BlitzPHP\Facades\Fs;
use php_user_filter;
use ReturnTypeWillChange;

/**
 * Pour tester la sortie de la console.
 */
class CliOutputHelper
{
    protected static $ou = TEMP_PATH . 'output.test';

    public static function setUpBeforeClass(): void
    {
        if (! is_dir($dirname = pathinfo(static::$ou, PATHINFO_DIRNAME))) {
            mkdir($dirname, 0o777, true);
        }

        // Thanks: https://stackoverflow.com/a/39785995
        stream_filter_register('intercept', StreamInterceptor::class);
        stream_filter_append(\STDOUT, 'intercept');
        stream_filter_append(\STDERR, 'intercept');
    }

    public static function setUp(): void
    {
        ob_start();
        StreamInterceptor::$buffer = '';
        file_put_contents(static::$ou, '', LOCK_EX);
    }

    public static function tearDown(): void
    {
        ob_end_clean();
    }

    public static function tearDownAfterClass(): void
    {
        // Make sure we clean up after ourselves:
        if (is_dir($dirname = pathinfo(static::$ou, PATHINFO_DIRNAME))) {
            Fs::deleteDirectories($dirname);
        }
    }

    public static function buffer()
    {
        return StreamInterceptor::$buffer ?: file_get_contents(static::$ou);
    }
}

class StreamInterceptor extends php_user_filter
{
    public static $buffer = '';

    #[ReturnTypeWillChange]
    public function filter($in, $out, &$consumed, $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            static::$buffer .= $bucket->data;
        }

        return PSFS_PASS_ON;
    }
}

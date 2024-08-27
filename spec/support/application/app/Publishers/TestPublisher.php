<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\App\Publishers;

use BlitzPHP\Publisher\Publisher;

final class TestPublisher extends Publisher
{
    /**
     * Renvoie la valeur de publish()
     */
    private static bool $result = true;

    /**
     *{@inheritDoc}
     */
    protected string $source = SUPPORT_PATH . 'Files';

    /**
     * {@inheritDoc}
     */
    protected string $destination = STORAGE_PATH;

    /**
     * Fakes an error on the given file.
     */
    public static function setResult(bool $result): void
    {
        self::$result = $result;
    }

    /**
     * Fakes a publish event so no files are actually copied.
     */
    public function publish(): bool
    {
        $this->addPath('');

        return self::$result;
    }
}

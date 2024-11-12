<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\Services\Translation;

class TranslationTwo
{
    public function list(): void
    {
        // Error language keys
        lang('TranslationTwo');
        lang(' ');
        lang('');
        lang('.invalid_key');
        lang('TranslationTwo.');
        lang('TranslationTwo...');
        lang('..invalid_nested_key..');
        lang('TranslationTwo');
        lang(' ');
        lang('');
        lang('.invalid_key');
        lang('TranslationTwo.');
        lang('TranslationTwo...');
        lang('..invalid_nested_key..');
        // Empty in comments lang('') lang(' ')
    }
}

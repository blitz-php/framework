<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\Services\Translation\TranslationNested;

class TranslationFour
{
    public function list(): void
    {
        lang('TranslationOne.title');
        lang('TranslationOne.last_operation_success');

        lang('TranslationThree.alerts.created');
        lang('TranslationThree.alerts.failed_insert');

        lang('TranslationThree.formFields.new.name');
        lang('TranslationThree.formFields.new.short_tag');

        lang('Translation-Four.dashed.key-with-dash');
        lang('Translation-Four.dashed.key-with-dash-two');
    }
}

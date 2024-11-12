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

class TranslationOne
{
    public function list(): void
    {
        lang('TranslationOne.title');
        lang('TranslationOne.DESCRIPTION');
        lang('TranslationOne.metaTags');
        lang('TranslationOne.Copyright');
        lang('TranslationOne.last_operation_success');

        lang('TranslationThree.alerts.created');
        lang('TranslationThree.alerts.failed_insert');
        lang('TranslationThree.alerts.Updated');
        lang('TranslationThree.alerts.DELETED');

        lang('TranslationThree.formFields.new.name');
        lang('TranslationThree.formFields.new.TEXT');
        lang('TranslationThree.formFields.new.short_tag');
        lang('TranslationThree.formFields.edit.name');
        lang('TranslationThree.formFields.edit.TEXT');
        lang('TranslationThree.formFields.edit.short_tag');
    }
}

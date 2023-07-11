<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Validation;

use BlitzPHP\Container\Injector;
use Dimtrovich\Validation\Validation as BaseValidation;

class Validation extends BaseValidation
{
    public function __construct()
    {
        parent::__construct(config('app.language'));

        $this->registerRules([
            Rules\Unique::class,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function registerRules(array $rules): void
    {
        foreach ($rules as $key => $value) {
            if (is_int($key)) {
                $name = $value::name();
                $rule = $value;
            } else {
                $name = $value;
                $rule = $key;
            }

            $this->addValidator($name, Injector::get($rule));
        }
    }
}

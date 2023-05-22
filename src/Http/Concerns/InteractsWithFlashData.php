<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http\Concerns;

/**
 * @credit <a href="http://laravel.com/">Laravel - Illuminate\Http\Concerns\InteractsWithFlashData</a>
 */
trait InteractsWithFlashData
{
    /**
     * Retrieve an old input item.
     * 
     * @param  Model|string|array|null  $default
     * @return string|array|null
     */
    public function old(?string $key = null, $default = null)
    {
        $default = $default instanceof Model ? $default->getAttribute($key) : $default;

        return $this->hasSession() ? $this->session()->getOldInput($key, $default) : $default;
    }

    /**
     * Flashez l'entrée de la demande actuelle à la session.
     */
    public function flash(): void
    {
        $this->session()->flashInput($this->input());
    }

    /**
     * Ne flashez qu'une partie des entrées de la session.
     *
     * @param  array|mixed  $keys
     */
    public function flashOnly($keys): void
    {
        $this->session()->flashInput(
            $this->only(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Ne flashez qu'une partie des entrées de la session.
     *
     * @param  array|mixed  $keys
     */
    public function flashExcept($keys): void
    {
        $this->session()->flashInput(
            $this->except(is_array($keys) ? $keys : func_get_args())
        );
    }

    /**
     * Videz toutes les anciennes entrées de la session.
     */
    public function flush()
    {
        $this->session()->flashInput([]);
    }
}

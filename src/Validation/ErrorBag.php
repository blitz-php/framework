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

use Dimtrovich\Validation\ErrorBag as DimtrovichErrorBag;

class ErrorBag extends DimtrovichErrorBag
{
    /**
     * Obtenir des messages à partir d'une clé donnée, peut être utilisé dans un format personnalisé
     */
    public function get(string $key, string $format = ':message'): array
    {
        [$key, $ruleName] = $this->parsekey($key);
        $results          = [];
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);

            foreach ($messages as $explicitKey => $keyMessages) {
                foreach ($keyMessages as $rule => $message) {
                    $results[$explicitKey][$rule] = $this->formatMessage($message, $format);
                }
            }
        } else {
            $keyMessages = $this->messages[$key] ?? [];

            foreach ((array) $keyMessages as $rule => $message) {
                if ($ruleName && $ruleName !== $rule) {
                    continue;
                }
                $results[$rule] = $this->formatMessage($message, $format);
            }
        }

        return $results;
    }

    /**
     * Obtenir tous les messages
     */
    public function all(string $format = ':message'): array
    {
        $messages = $this->messages;

        $results = [];

        foreach ($messages as $key => $keyMessages) {
            foreach ((array) $keyMessages as $message) {
                $results[] = $this->formatMessage($message, $format);
            }
        }

        return $results;
    }

    /**
     * Filtrer les messages avec une clé générique
     *
     * @param mixed|null $ruleName
     */
    protected function filterMessagesForWildcardKey(string $key, $ruleName = null): array
    {
        $messages = $this->messages;
        $pattern  = preg_quote($key, '#');
        $pattern  = str_replace('\*', '.*', $pattern);

        $filteredMessages = [];

        foreach ($messages as $k => $keyMessages) {
            if ((bool) preg_match('#^' . $pattern . '\z#u', $k) === false) {
                continue;
            }

            foreach ((array) $keyMessages as $rule => $message) {
                if ($ruleName && $rule !== $ruleName) {
                    continue;
                }
                $filteredMessages[$k][$rule] = $message;
            }
        }

        return $filteredMessages;
    }
}

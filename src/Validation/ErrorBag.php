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

use Rakit\Validation\ErrorBag as RakitErrorBag;

class ErrorBag extends RakitErrorBag
{
    /**
     * Renvoie les erreurs de validation d'une cle sous forme de chaine
     */
    public function line(string $key, string $separator = ', ', string $format = ':message'): ?string
    {
        if ([] === $errors = $this->get($key, $format)) {
            return null;
        }

        return join($separator, $errors);
    }

    /**
     * Get messages from given key, can be use custom format
     *
     */
    public function get(string $key, string $format = ':message'): array
    {
        list($key, $ruleName) = $this->parsekey($key);
        $results = [];
        if ($this->isWildcardKey($key)) {
            $messages = $this->filterMessagesForWildcardKey($key, $ruleName);
            foreach ($messages as $explicitKey => $keyMessages) {
                foreach ($keyMessages as $rule => $message) {
                    $results[$explicitKey][$rule] = $this->formatMessage($message, $format);
                }
            }
        } else {
            $keyMessages = isset($this->messages[$key])? $this->messages[$key] : [];
            foreach ((array) $keyMessages as $rule => $message) {
                if ($ruleName and $ruleName != $rule) {
                    continue;
                }
                $results[$rule] = $this->formatMessage($message, $format);
            }
        }

        return $results;
    }

    /**
     * Get all messages
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
     * Filter messages with wildcard key
     */
    protected function filterMessagesForWildcardKey(string $key, $ruleName = null): array
    {
        $messages = $this->messages;
        $pattern = preg_quote($key, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        $filteredMessages = [];

        foreach ($messages as $k => $keyMessages) {
            if ((bool) preg_match('#^'.$pattern.'\z#u', $k) === false) {
                continue;
            }

            foreach ((array) $keyMessages as $rule => $message) {
                if ($ruleName and $rule != $ruleName) {
                    continue;
                }
                $filteredMessages[$k][$rule] = $message;
            }
        }

        return $filteredMessages;
    }
}

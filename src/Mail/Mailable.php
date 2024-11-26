<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Mail;

use ReflectionClass;
use ReflectionProperty;

abstract class Mailable
{
    /**
     * Définition des pièces jointes du mail
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Définition des adresses de copie (BCC) au mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     *  [
     *      'johndoe@mail.com' => 'john doe',
     *      'janedoe@mail.com',
     *  ]
     * ```
     */
    public function bcc(): array
    {
        return [];
    }

    /**
     * Définition des adresses de copie (CC) au mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     *  [
     *      'johndoe@mail.com' => 'john doe',
     *      'janedoe@mail.com',
     *  ]
     * ```
     */
    public function cc(): array
    {
        return [];
    }

    /**
     * Définition des éléments du contenu du mail
     */
    public function content(): array
    {
        return [
            'view' => '',
            'html' => '',
            'text' => '',
        ];
    }

    /**
     * Définition de l'adresse de l'expediteur du mail
     *
     * @return list<string>
     *
     * @example
     * ```php
     *  ['johndoe@mail.com', 'John Doe']
     *  ['johndoe@mail.com']
     * ```
     */
    public function from(): array
    {
        $from = config('mail.from');

        return [$from['address'] ?? '', $from['name'] ?? ''];
    }

    /**
     * Définition des entetes supplementaires du mail
     *
     * @return array<string, string>
     *
     * @example
     * ```php
     *  [
     *      'X-Custom-Header' => 'Custom Value',
     *  ]
     * ```
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * Définition du niveau de priorité du mail
     */
    public function priority(): int
    {
        return Mail::PRIORITY_NORMAL;
    }

    /**
     * Définition des adresses de reponse (ReplyTo) du mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     *  [
     *      'johndoe@mail.com' => 'john doe',
     *      'janedoe@mail.com',
     *  ]
     * ```
     */
    public function replyTo(): array
    {
        return [];
    }

    /**
     * Définition du sujet du mail
     */
    public function subject(): string
    {
        return '';
    }

    /**
     * Définition des adresses de destination (to) au mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     *  [
     *      'johndoe@mail.com' => 'john doe',
     *      'janedoe@mail.com',
     *  ]
     * ```
     */
    public function to(): array
    {
        return [];
    }

    /**
     * Définition des données à transférer à la vue qui générera le mail
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Données à transférer à la vue qui générera le mail
     *
     * @internal
     */
    public function data(): array
    {
        $reflection = new ReflectionClass(static::class);

        $data = [];

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $data[$prop->getName()] = $prop->getValue($this);
        }

        return array_merge($data, $this->with());
    }

    /**
     * Envoi du mail
     *
     * @internal
     */
    public function send(Mail $mail): bool
    {
        foreach ($this->bcc() as $key => $value) {
            if (empty($value) || ! is_string($value)) {
                continue;
            }

            if (is_string($key)) {
                $mail->bcc($key, $value);
            } else {
                $mail->bcc($value);
            }
        }

        foreach ($this->cc() as $key => $value) {
            if (empty($value) || ! is_string($value)) {
                continue;
            }

            if (is_string($key)) {
                $mail->cc($key, $value);
            } else {
                $mail->cc($value);
            }
        }

        $content = $this->content();

        if (! empty($content['view'])) {
            $mail->view($content['view'], $this->data());
        } elseif (! empty($content['html'])) {
            $mail->html($content['html']);
        }
        if (! empty($content['text'])) {
            $mail->text($content['text']);
        }

        $mail->from(...$this->from());
        $mail->header($this->headers());
        $mail->priority($this->priority());

        foreach ($this->replyTo() as $key => $value) {
            if (empty($value) || ! is_string($value)) {
                continue;
            }

            if (is_string($key)) {
                $mail->replyTo($key, $value);
            } else {
                $mail->replyTo($value);
            }
        }

        $mail->subject($this->subject());

		foreach ($this->to() as $key => $value) {
            if (empty($value) || ! is_string($value)) {
                continue;
            }

            if (is_string($key)) {
                $mail->to($key, $value);
            } else {
                $mail->to($value);
            }
        }

        return $mail->send();
    }
}

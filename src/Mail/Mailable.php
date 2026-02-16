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

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Classe de base pour la création de mails via objets.
 * Fournit une interface fluide pour configurer et envoyer des emails.
 */
abstract class Mailable
{
    /**
     * Définition des pièces jointes du mail
     *
     * Chaque pièce jointe doit être un tableau avec les clés suivantes :
     * - path: Chemin vers le fichier
     * - name: Nom du fichier (optionnel)
     * - type: Type MIME (optionnel)
     * - disposition: 'attachment' ou 'inline' (défaut: 'attachment')
     *
     * @return list<array{
     *     path: string,
     *     name?: string,
     *     type?: string,
     *     disposition?: string
     * }>
     *
     * @example
     * ```php
     * return [
     *     ['path' => '/chemin/vers/fichier.pdf', 'name' => 'document.pdf'],
     *     ['path' => '/chemin/vers/image.jpg', 'disposition' => 'inline']
     * ];
     * ```
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Définition des adresses de copie cachée (BCC) du mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     * // Avec noms
     * ['johndoe@mail.com' => 'John Doe', 'janedoe@mail.com' => 'Jane Doe']
     * // Sans noms
     * ['johndoe@mail.com', 'janedoe@mail.com']
     * ```
     */
    public function bcc(): array
    {
        return [];
    }

    /**
     * Définition des adresses de copie (CC) du mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     * // Avec noms
     * ['johndoe@mail.com' => 'John Doe', 'janedoe@mail.com' => 'Jane Doe']
     * // Sans noms
     * ['johndoe@mail.com', 'janedoe@mail.com']
     * ```
     */
    public function cc(): array
    {
        return [];
    }

    /**
     * Définition des éléments du contenu du mail
     *
     * @return array{
     *     view?: string,
     *     markdown?: string,
     *     html?: string,
     *     text?: string
     * }
     */
    public function content(): array
    {
        return [
            'view'     => '',
            'markdown' => '',
            'html'     => '',
            'text'     => '',
        ];
    }

    /**
     * Définition de l'adresse de l'expéditeur du mail
     *
     * @return list<string> Tableau contenant [email, nom] ou [email]
     *
     * @example
     * ```php
     * // Avec nom
     * ['johndoe@mail.com', 'John Doe']
     * // Sans nom
     * ['johndoe@mail.com']
     * ```
     */
    public function from(): array
    {
        $from = config('mail.from');

        return [$from['address'] ?? '', $from['name'] ?? ''];
    }

    /**
     * Définition des en-têtes supplémentaires du mail
     *
     * @return array<string, string>
     *
     * @example
     * ```php
     * [
     *     'X-Custom-Header' => 'Custom Value',
     *     'X-Priority' => '1'
     * ]
     * ```
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * Définition du niveau de priorité du mail
     *
     * @return int Une des constantes Mail::PRIORITY_*
     */
    public function priority(): int
    {
        return Mail::PRIORITY_NORMAL;
    }

    /**
     * Définition des adresses de réponse (ReplyTo) du mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     * // Avec noms
     * ['johndoe@mail.com' => 'John Doe', 'janedoe@mail.com' => 'Jane Doe']
     * // Sans noms
     * ['johndoe@mail.com', 'janedoe@mail.com']
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
     * Définition des adresses de destination (TO) du mail
     *
     * @return array<string, string>|list<string>
     *
     * @example
     * ```php
     * // Avec noms
     * ['johndoe@mail.com' => 'John Doe', 'janedoe@mail.com' => 'Jane Doe']
     * // Sans noms
     * ['johndoe@mail.com', 'janedoe@mail.com']
     * ```
     */
    public function to(): array
    {
        return [];
    }

    /**
     * Définition des données à transférer à la vue qui générera le mail
     *
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Données à transférer à la vue qui générera le mail
     *
     * Combine les propriétés publiques de la classe avec les données
     * retournées par la méthode with().
     *
     * @internal
     *
     * @return array<string, mixed>
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
     * @param Mail $mail Instance du mailer
     *
     * @throws InvalidArgumentException Si le mail n'est pas valide
     *
     * @internal
     */
    public function send(Mail $mail): bool
    {
        foreach ($this->bcc() as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if (is_string($key)) {
                $mail->bcc($key, $value);
            } else {
                $mail->bcc($value);
            }
        }

        foreach ($this->cc() as $key => $value) {
            if (! is_string($value) || trim($value) === '') {
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
        } elseif (! empty($content['markdown'])) {
            $mail->markdown($content['markdown'], $this->data());
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
            if (! is_string($value) || trim($value) === '') {
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
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            if (is_string($key)) {
                $mail->to($key, $value);
            } else {
                $mail->to($value);
            }
        }

        foreach ($this->attachments() as $attachment) {
            if (! isset($attachment['path']) || ! file_exists($attachment['path'])) {
                continue;
            }

            $name        = $attachment['name'] ?? '';
            $type        = $attachment['type'] ?? '';
            $disposition = $attachment['disposition'] ?? 'attachment';

            $mail->attach($attachment['path'], $name, $type, Mail::ENCODING_BASE64, $disposition);
        }

        return $mail->send();
    }
}

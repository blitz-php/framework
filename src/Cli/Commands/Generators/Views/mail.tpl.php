<@php

namespace {namespace};

use BlitzPHP\Mail\Mailable;

class {class} extends Mailable
{
    /**
     * Définition du sujet du mail
     */
    public function subject(): string
    {
        return '';
    }

    /**
     * Définition des éléments du contenu du mail
     */
    public function content(): array
    {
        return [
            'view' => 'path/to/email/view',
            // 'html' => '',
            // 'text' => ''
        ];
    }
    
    /**
     * Définition des données à transférer à la vue qui générera le mail
     */
    public function with(): array
    {
        return [];
    }
}

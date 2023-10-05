<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Commands\Encryption;

use BlitzPHP\Cli\Console\Command;
use BlitzPHP\Loader\DotEnv;

/**
 * Genere une nouvelle cle d'encryption.
 */
class GenerateKey extends Command
{
    /**
     * @var string Groupe
     */
    protected $group = 'Encryption';

    /**
     * @var string Nom
     */
    protected $name = 'key:generate';

    /**
     * @var string Description
     */
    protected $description = 'Génère une nouvelle clé d\'encryption et la met dans le fichier `.env`.';

    /**
     * @var string
     */
    protected $service = 'Service de d\'encryption';

    /**
     * @var array Options
     */
    protected $options = [
        '--force'  => 'Force l\'écrasement de clé existante dans le fichier `.env`.',
        '--length' => ['La longeuur de la chaine aléatoire qui doit être retournée en bytes. Par défaut "32".', 32],
        '--prefix' => ['Prefix à ajouter à la clé encodée (doit être hex2bin ou base64). Par défaut "hex2bin".', 'hex2bin'],
        '--show'   => 'Indique qu\'on souhaite afficher la clé générée dans le terminal après l\'avoir mis dans le fichier `.env`.',
    ];

    /**
     * {@inheritDoc}
     */
    public function execute(array $params)
    {
        $prefix = $params['prefix'] ?? null;

        if (in_array($prefix, [null, true], true)) {
            $prefix = 'hex2bin';
        } elseif (! in_array($prefix, ['hex2bin', 'base64'], true)) {
            $prefix = $this->choice('Veuillez utiliser un prefixe validee.', ['hex2bin', 'base64']); // @codeCoverageIgnore
        }

        $length = $params['length'] ?? null;

        if (in_array($length, [null, true], true)) {
            $length = 32;
        }

        $this->task('Génération d\'une nouvelle clé d\'encryption');

        $encodedKey = $this->generateRandomKey($prefix, $length);

        if ($this->getOption('show')) {
            $this->writer->warn($encodedKey, true);

            return;
        }

        if (! $this->setNewEncryptionKey($encodedKey)) {
            $this->writer->error('Erreur dans la configuration d\'une nouvelle cle d\'encryption dans le fichier `.env`.', true);

            return;
        }

        $this->success('Une nouvelle clé d\'encryption de l\'application a été définie avec succès.');
    }

    /**
     * Genere une cle et l'encode.
     */
    protected function generateRandomKey(string $prefix, int $length): string
    {
        $key = random_bytes($length); // @todo prevoir l'utilisation d'une classe Encryption

        if ($prefix === 'hex2bin') {
            return 'hex2bin:' . bin2hex($key);
        }

        return 'base64:' . base64_encode($key);
    }

    /**
     * Definit la nouvelle cle d'encryption dans le fichier .env
     */
    protected function setNewEncryptionKey(string $key): bool
    {
        $currentKey = env('encryption.key', '');

        if ($currentKey !== '' && ! $this->confirmOverwrite()) {
            // Pas testable car require une entree au clavier
            return false; // @codeCoverageIgnore
        }

        return $this->writeNewEncryptionKeyToFile($key);
    }

    /**
     * Verifie si on doit ecraser la cle d'encryption existante.
     */
    protected function confirmOverwrite(): bool
    {
        return $this->getOption('force') || $this->confirm('Voulez-vous modifier la clé existante ?');
    }

    /**
     * Writes the new encryption key to .env file.
     */
    protected function writeNewEncryptionKeyToFile(string $key): bool
    {
        $baseEnv = ROOTPATH . '.env.example';
        $envFile = ROOTPATH . '.env';

        if (! is_file($envFile)) {
            if (! is_file($baseEnv)) {
                $this->writer->warn('Both default shipped `.env.example` file and custom `.env` are missing.');
                $this->write('Here\'s your new key instead: ' . $this->writer->warn($key), true);

                return false;
            }

            copy($baseEnv, $envFile);
        }

        return DotEnv::instance()->replace(['encryption.key' => $key]);
    }
}

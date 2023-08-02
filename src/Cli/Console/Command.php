<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Cli\Console;

use Ahc\Cli\Helper\Terminal;
use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Cursor;
use Ahc\Cli\Output\ProgressBar;
use Ahc\Cli\Output\Writer;
use BlitzPHP\Exceptions\CLIException;
use Psr\Log\LoggerInterface;

/**
 * Classe de base utilisée pour créer des commandes pour la console
 *
 * @property string          $alias
 * @property array           $arguments
 * @property string          $description
 * @property string          $group
 * @property LoggerInterface $logger
 * @property string          $name
 * @property array           $options
 * @property array           $required
 * @property string          $service
 * @property string          $usage
 */
abstract class Command
{
    /**
     * Le groupe sous lequel la commande est regroupée
     * lors de la liste des commandes.
     *
     * @var string
     */
    protected $group;

    /**
     * Le nom de la commande
     *
     * @var string
     */
    protected $name;

    /**
     * La description de l'usage de la commande
     *
     * @var string
     */
    protected $usage = '';

    /**
     * La description courte de la commande
     *
     * @var string
     */
    protected $description;

    /**
     * la description des options de la commande
     *
     * @var array
     *
     * @example
     * `[
     *      'option' => [string $description, mixed|null $default_value, callable|null $filter]
     * ]`
     */
    protected $options = [];

    /**
     * La description des arguments de la commande
     *
     * @var array
     *
     * @example
     * `[
     *      'argument' => [string $description, mixed|null $default_value]
     * ]`
     */
    protected $arguments = [];

    /**
     * L'alias de la commande
     *
     * @var string
     */
    protected $alias = '';

    /**
     * La version de la commande
     *
     * @var string
     */
    protected $version = '';

    /**
     * Le nom du service de la commande
     *
     * @var string
     */
    protected $service = '';

    /**
     * Liste des packets requis pour le fonctionnement d'une commande
     * Par exemple, toutes le commande du groupe Database ont besoin de blitz/database
     *
     * @var array
     *
     * @example
     * `[
     *      'vendor/package', 'vendor/package:version'
     * ]`
     */
    protected $required = [];

    /**
     * @var Interactor
     */
    protected $io;

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var Color
     */
    protected $color;

    /**
     * @var Cursor
     */
    protected $cursor;

    /**
     * Arguments recus apres executions
     */
    private array $_arguments = [];

    /**
     * Options recus apres executions
     */
    private array $_options = [];

	/**
	 * @param Console $app Application Console
	 * @param LoggerInterface $logger Le Logger à utiliser
	 */
    public function __construct(protected Console $app, protected LoggerInterface $logger)
    {
        $this->initProps();
    }

    /**
     * Instance de l'application console
     */
    final public function app(): Console
    {
        return $this->app;
    }

    /**
     * Exécution réelle de la commande.
     *
     * @param array<int|string, string|null> $params
     */
    abstract public function execute(array $params);

    /**
     * Definit les options recues par la commande a l'execution
     *
     * @internal Utiliser seulement par le framework pour fournir les options a la commande
     */
    final public function setOptions(array $options = []): self
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Definit les arguments recus par la commande a l'execution
     *
     * @internal Utiliser seulement par le framework pour fournir les arguments a la commande
     */
    final public function setArguments(array $arguments = []): self
    {
        $this->_arguments = $arguments;

        return $this;
    }

    /**
     * Recupere la valeur d'un argument lors de l'execution de la commande
     */
    final protected function argument(string $name, mixed $default = null): mixed
    {
        return $this->_arguments[$name] ?? $default;
    }

    /**
     * @deprecated 1.1 Utilisez argument() a la place
     */
    final protected function getArg(string $name, mixed $default = null)
    {
        return $this->argument($name, $default);
    }

    /**
     * Recupere la valeur d'une option lors de l'execution de la commande
     *
     * @param mixed $default
     */
    final protected function option(string $name, mixed $default = null): mixed
    {
        return $this->_options[$name] ?? $default;
    }

    /**
     * @deprecated 1.1 Utilisez option() a la place
     */
    final protected function getOption(string $name, mixed $default = null)
    {
        return $this->option($name, $default);
    }

    /**
     * Recupere la valeur d'un parametre (option ou argument) lors de l'execution de la commande.
     */
    final protected function param(string $name, mixed $default = null): mixed
    {
        $params = array_merge($this->_arguments, $this->_options);

        return $params[$name] ?? $default;
    }

    /**
     * @deprecated 1.1 Utilisez param() a la place
     */
    final protected function getParam(string $name, mixed $default = null)
    {
        return $this->param($name, $default);
    }

    /**
     * Ecrit un message dans une couleur spécifique
     */
    final protected function colorize(string $message, string $color): self
    {
        $this->writer->colors('<' . $color . '>' . $message . '</end><eol>');

        return $this;
    }

    /**
     * Ecrit un message de reussite
     */
    final protected function ok(string $message, bool $eol = false): self
    {
        $this->writer->ok($message, $eol);

        return $this;
    }

    /**
     * Ecrit un message d'echec
     */
    final protected function fail(string $message, bool $eol = false): self
    {
        $this->writer->error($message, $eol);

        return $this;
    }

    /**
     * Ecrit un message de succes
     */
    final protected function success(string $message, bool $badge = true, string $label = 'SUCCESS'): self
    {
        if (! $badge) {
            $this->writer->okBold($label);
        } else {
            $this->writer->boldWhiteBgGreen(" {$label} ");
        }

        return $this->write(' ' . $message, true);
    }

    /**
     * Ecrit un message d'avertissement
     */
    final protected function warning(string $message, bool $badge = true, string $label = 'WARNING'): self
    {
        if (! $badge) {
            $this->writer->warnBold($label);
        } else {
            $this->writer->boldWhiteBgYellow(" {$label} ");
        }

        return $this->write(' ' . $message, true);
    }

    /**
     * Ecrit un message d'information
     */
    final protected function info(string $message, bool $badge = true, string $label = 'INFO'): self
    {
        if (! $badge) {
            $this->writer->infoBold($label);
        } else {
            $this->writer->boldWhiteBgCyan(" {$label} ");
        }

        return $this->write(' ' . $message, true);
    }

    /**
     * Ecrit un message d'erreur
     */
    final protected function error(string $message, bool $badge = true, string $label = 'ERROR'): self
    {
        if (! $badge) {
            $this->writer->errorBold($label);
        } else {
            $this->writer->boldWhiteBgRed(" {$label} ");
        }

        return $this->write(' ' . $message, true);
    }

    /**
     * Ecrit la tâche actuellement en cours d'execution
     */
    final protected function task(string $task): self
    {
        $this->write('>> ' . $task, true);

        return $this;
    }

    /**
     * Écrit EOL n fois.
     */
    final protected function eol(int $n = 1): self
    {
        $this->writer->eol($n);

        return $this;
    }

    /**
     * Écrit une nouvelle ligne vide (saut de ligne).
     */
    final protected function newLine(): self
    {
        return $this->eol(1);
    }

    /**
     * Générer une table pour la console. Les clés de la première ligne sont prises comme en-tête.
     *
     * @param array[] $rows   Tableau de tableaux associés.
     * @param array   $styles Par exemple : ['head' => 'bold', 'odd' => 'comment', 'even' => 'green']
     */
    final protected function table(array $rows, array $styles = []): self
    {
        $this->writer->table($rows, $styles);

        return $this;
    }

    /**
     * Écrit le texte formaté dans stdout ou stderr.
     */
    final protected function write(string $texte, bool $eol = false): self
    {
        $this->writer->write($texte, $eol);

        return $this;
    }

    /**
     * Écrit le texte de maniere commentée.
     */
    final protected function comment(string $text, bool $eol = false): self
    {
        $this->writer->comment($text, $eol);

        return $this;
    }

    /**
     * Efface la console
     */
    final protected function clear(): self
    {
        $this->cursor->clear();

        return $this;
    }

    /**
     * Affiche une bordure en pointillés
     */
    final protected function border(?int $length = null, string $char = '-'): self
    {
        if ($length === null) {
            $terminal = new Terminal();
            $length   = $terminal->width() ?: 100;
        }

        $str = str_repeat($char, $length);
        $str = substr($str, 0, $length);

        $this->comment($str, true);

        return $this;
    }

    /**
     * Affiche les donnees formatees en json
     *
     * @param mixed $data
     */
    final protected function json($data): self
    {
        $this->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), true);

        return $this;
    }

    /**
     * Effectue des tabulations
     */
    final protected function tab(int $repeat = 1): self
    {
        $this->write(str_repeat("\t", $repeat));

        return $this;
    }

    /**
     * Laissez l'utilisateur faire un choix parmi les choix disponibles.
     *
     * @param string $text    Texte d'invite.
     * @param array  $choices Choix possibles pour l'utilisateur.
     * @param mixed  $default Valeur par défaut - si non choisie ou invalide.
     * @param bool   $case    Si l'entrée utilisateur doit être sensible à la casse.
     *
     * @return mixed Entrée utilisateur ou valeur par défaut.
     */
    final protected function choice(string $text, array $choices, $default = null, bool $case = false): mixed
    {
        return $this->io->choice($text, $choices, $default, $case);
    }

    /**
     * Laissez l'utilisateur faire plusieurs choix parmi les choix disponibles.
     *
     * @param string $text    Texte d'invite.
     * @param array  $choices Choix possibles pour l'utilisateur.
     * @param mixed  $default Valeur par défaut - si non choisie ou invalide.
     * @param bool   $case    Si l'entrée utilisateur doit être sensible à la casse.
     *
     * @return mixed Entrée utilisateur ou valeur par défaut.
     */
    final protected function choices(string $text, array $choices, $default = null, bool $case = false): mixed
    {
        return $this->io->choices($text, $choices, $default, $case);
    }

    /**
     * Confirme si l'utilisateur accepte une question posée par le texte donné.
     *
     * @param string $default `y|n`
     */
    final protected function confirm(string $text, string $default = 'y'): bool
    {
        return $this->io->confirm($text, $default);
    }

    /**
     * Demander à l'utilisateur d'entrer une donnée
     *
     * @param callable|null $fn      L'assainisseur/validateur pour l'entrée utilisateur
     *                               Tout message d'exception est imprimé et démandé à nouveau.
     * @param int           $retry   Combien de fois encore pour réessayer en cas d'échec.
     * @param mixed|null    $default
     */
    final protected function prompt(string $text, $default = null, ?callable $fn = null, int $retry = 3): mixed
    {
        return $this->io->prompt($text, $default, $fn, $retry);
    }

    /**
     * Demander à l'utilisateur une entrée secrète comme un mot de passe. Actuellement pour unix uniquement.
     *
     * @param callable|null $fn    L'assainisseur/validateur pour l'entrée utilisateur
     *                             Tout message d'exception est imprimé en tant qu'erreur.
     * @param int           $retry Combien de fois encore pour réessayer en cas d'échec.
     */
    final protected function promptHidden(string $text, ?callable $fn = null, int $retry = 3): mixed
    {
        return $this->io->promptHidden($text, $fn, $retry);
    }

    /**
     * Peut etre utiliser par la commande pour executer d'autres commandes.
     *
     * @return mixed
     *
     * @throws CLIException
     */
    final protected function call(string $command, array $arguments = [], array $options = [])
    {
        return $this->app->call($command, $arguments, $options);
    }

    /**
     * Initialise une bar de progression
     */
    final protected function progress(?int $total = null): ProgressBar
    {
        return new ProgressBar($total, $this->writer);
    }

    /**
     * Facilite l'accès à nos propriétés protégées.
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->{$key} ?? null;
    }

    /**
     * Facilite la vérification de nos propriétés protégées.
     */
    public function __isset(string $key): bool
    {
        return isset($this->{$key});
    }

    /**
     * Initalisation des proprieté necessaires
     *
     * @return void
     */
    private function initProps()
    {
        $this->io     = $this->app->io();
        $this->writer = $this->io->writer();
        $this->reader = $this->io->reader();
        $this->color  = $this->writer->colorizer();
        $this->cursor = $this->writer->cursor();
    }
}

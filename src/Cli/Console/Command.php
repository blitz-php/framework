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

use Ahc\Cli\Input\Reader;
use Ahc\Cli\IO\Interactor;
use Ahc\Cli\Output\Color;
use Ahc\Cli\Output\Writer;
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
     * Le Logger à utiliser
     *
     * @var LoggerInterface
     */
    protected $logger;

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
     * Application Console
     *
     * @var Console
     */
    protected $app;

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
     * Arguments recus apres executions
     */
    private array $_arguments = [];

    /**
     * Options recus apres executions
     */
    private array $_options = [];


    public function __construct(Console $app, LoggerInterface $logger)
    {
        $this->app    = $app;
        $this->logger = $logger;
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
     *
     * @param mixed $default
     * @return mixed
     */
    final protected function getArg(string $name, $default = null)
    {
        return $this->_arguments[$name] ?? $default;
    }

    /**
     * Recupere la valeur d'une option lors de l'execution de la commande
     *
     * @param mixed $default
     * @return mixed
     */
    final protected function getOption(string $name, $default = null)
    {
        return $this->_options[$name] ?? $default;
    }

    /**
     * Recupere la valeur d'un parametre (option ou argument) lors de l'execution de la commande
     *
     * @param mixed $default
     * @return mixed
     */
    final protected function getParam(string $name, $default = null)
    {
        $params = array_merge($this->_arguments, $this->_options);

        return $params[$name] ?? $default;
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
     * Ecrit un message de succes
     */
    final protected function success(string $message): self
    {
        return $this->ok($message, true);
    }

    /**
     * Ecrit un message d'erreur
     */
    final protected function error(string $message, bool $eol = false): self
    {
        $this->writer->error($message, $eol);

        return $this;
    }

    /**
     * Ecrit un message d'echec
     */
    final protected function fail(string $message): self
    {
        return $this->error($message, true);
    }

    /**
     * Ecrit la tâche actuellement en cours d'execution
     */
    final protected function task(string $task)
    {
        $this->write('>> ' . $task, true);
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
     * Can be used by a command to run other commands.
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    final protected function call(string $command, array $params = [])
    {
        // return $this->commands->run($command, $params);
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
    }
}

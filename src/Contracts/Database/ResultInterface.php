<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Database;

/**
 * Interface ResultInterface
 */
interface ResultInterface
{
    /**
     * Récupérer les résultats de la requête. Typiquement un tableau de
     * des lignes de données individuelles, qui peuvent être soit un "tableau", soit un
     * 'object', ou un nom de classe personnalisé.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     */
    public function result(int|string|null $type = 'object'): array;

    /**
     * Renvoie les résultats sous la forme d'un tableau de tableaux.
     *
     * Si aucun résultat, un tableau vide est renvoyé.
     */
    public function resultArray(): array;

    /**
     * Renvoie les résultats sous la forme d'un tableau d'objets.
     *
     * Si aucun résultat, un tableau vide est renvoyé.
     */
    public function resultObject(): array;

    /**
     * Objet wrapper pour renvoyer une ligne sous forme de tableau, d'objet ou
     * une classe personnalisée.
     *
     * Si la ligne n'existe pas, renvoie null.
     *
     * @param mixed           $index L'index des résultats à retourner
     * @param int|string|null $type  Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function row(int $index, int|string|null $type = 'object');

    /**
     * Renvoie la "première" ligne des résultats actuels.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function first(int|string|null $type = 'object');

    /**
     * Returns the "last" row of the current results.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function last(int|string|null $type = 'object');

    /**
     * Renvoie la ligne "suivante" des résultats actuels.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function next(int|string|null $type = 'object');

    /**
     * Renvoie la ligne "précédente" des résultats actuels.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function previous(int|string|null $type = 'object');

    /**
     * Renvoie une ligne non tamponnée et déplace le pointeur vers la ligne suivante.
     *
     * @param int|string|null $type Le type d'objet de résultat. 'tableau', 'objet', nom de classe ou constante d'extraction PDO.
     *
     * @return mixed
     */
    public function unbufferedRow(int|string|null $type = 'object');

    /**
     * Obtient le nombre de champs dans le jeu de résultats.
     */
    public function countField(): int;

    /**
     * Génère un tableau de noms de colonnes dans le jeu de résultats.
     */
    public function fieldNames(): array;

    /**
     * Génère un tableau d'objets représentant des métadonnées de champ.
     */
    public function fieldData(): array;

    /**
     * Libère le résultat courant.
     */
    public function freeResult();
}

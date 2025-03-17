<?php

/**
 * Configuration du hashage.
 *
 * Paramètres utilisés pour le hashage des mots de passe au cas où vous ne transmettez pas de tableau de paramètres au hasheur pour la création/initialisation.
 */

return [
    /**
     * --------------------------------------------------------------------------
     * Pilote de hashage à utiliser
     * ------------------------------------------------- -------------------------
     *
     * Cette option contrôle le pilote de hachage par défaut qui sera utilisé pour hacher les mots de passe de votre application. 
     * Par défaut, l'algorithme bcrypt est utilisé ; cependant, vous êtes libre de modifier cette option si vous le souhaitez.
     *
     * Pilotes disponibles : "bcrypt", "argon", "argon2id"
     * 
     * @var string
     */
    'driver' => 'bcrypt',

    /**
     * --------------------------------------------------------------------------
     * Options du pilote Bcrypt
     * ------------------------------------------------- -------------------------
     *
     * Vous pouvez spécifier ici les options de configuration à utiliser lorsque les mots de passe sont hachés à l'aide de l'algorithme Bcrypt. 
     * Elles vous permettent de contrôler le temps nécessaire pour hacher le mot de passe donné.
     * 
     * @var array<string,bool|int>
     */
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    /**
     * --------------------------------------------------------------------------
     * Options du pilote Argon
     * ------------------------------------------------- -------------------------
     *
     * Vous pouvez spécifier ici les options de configuration à utiliser lorsque les mots de passe sont hachés à l'aide de l'algorithme Argon. 
     * Elles vous permettent de contrôler le temps nécessaire pour hacher le mot de passe donné.
     * 
     * @var array<string,bool|int>
     */
    'argon' => [
        'memory'  => 65536,
        'threads' => 1,
        'time'    => 4,
        'verify'  => true,
    ],
];

<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Debug;

use BlitzPHP\Spec\Utilities\NativeHeadersStack;

/**
 * Imitation de la fonction native PHP `headers_sent()`.
 *
 * Au lieu de vérifier le tampon de sortie PHP réel, cette fonction
 * vérifie la propriété statique dans NativeHeadersStack.
 *
 * @return bool Vrai si les en-têtes sont considérés comme envoyés, faux dans le cas contraire.
 */
function headers_sent(): bool
{
    return NativeHeadersStack::$headersSent;
}

/**
 * Implémentation fictive de la fonction native PHP `headers_list()`.
 *
 * Récupère le tableau des en-têtes stockés dans la classe NativeHeadersStack
 * plutôt que les en-têtes réels envoyés par le serveur.
 *
 * @return array La liste des en-têtes simulés.
 */
function headers_list(): array
{
    return NativeHeadersStack::$headers;
}

<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

// Publisher language settings
return [
    'collision'             => 'Publisher encountered an unexpected {0} while copying {1} to {2}.',
    'destinationNotAllowed' => 'Destination is not on the allowed list of Publisher directories: {0}',
    'fileNotAllowed'        => '{0} fails the following restriction for {1}: {2}',

    // Publish Command
    'publishMissing'          => 'No Publisher classes detected in {0} across all namespaces.',
    'publishMissingNamespace' => 'No Publisher classes detected in {0} in namespace {1}.',
    'publishSuccess'          => '{0} published {1} file(s) to {2}.',
    'publishFailure'          => '{0} failed to publish to {1}!',
];

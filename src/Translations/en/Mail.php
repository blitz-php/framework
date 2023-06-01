<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

// Email language settings
return [
    'invalidHandler'         => 'The specified email handler `{0}` is invalid.',
    'undefinedHandler'       => 'Undefined email handler.',
    'handlerMustExtendClass' => 'The specified email handler `{0}` must be a subclass of `{1}`.',
    'dependancyNotFound'     => 'Dependency `{0}` of adapter `{1}` is not installed. Please install it : `composer require {2}`',

    'mustBeArray'          => 'The email validation method must be passed an array.',
    'invalidAddress'       => 'Invalid email address: {0}',
    'attachmentMissing'    => 'Unable to locate the following email attachment: {0}',
    'attachmentUnreadable' => 'Unable to open this attachment: {0}',
    'noFrom'               => 'Cannot send mail with no "From" header.',
    'noRecipients'         => 'You must include recipients: To, Cc, or Bcc',
    'sendFailurePHPMail'   => 'Unable to send email using PHP mail(). Your server might not be configured to send mail using this method.',
    'sendFailureSendmail'  => 'Unable to send email using PHP Sendmail. Your server might not be configured to send mail using this method.',
    'sendFailureSmtp'      => 'Unable to send email using PHP SMTP. Your server might not be configured to send mail using this method.',
    'sent'                 => 'Your message has been successfully sent using the following protocol: {0}',
    'noSocket'             => 'Unable to open a socket to Sendmail. Please check settings.',
    'noHostname'           => 'You did not specify a SMTP hostname.',
    'SMTPError'            => 'The following SMTP error was encountered: {0}',
    'noSMTPAuth'           => 'Error: You must assign a SMTP username and password.',
    'failedSMTPLogin'      => 'Failed to send AUTH LOGIN command. Error: {0}',
    'SMTPAuthUsername'     => 'Failed to authenticate username. Error: {0}',
    'SMTPAuthPassword'     => 'Failed to authenticate password. Error: {0}',
    'SMTPDataFailure'      => 'Unable to send data: {0}',
    'exitStatus'           => 'Exit status code: {0}',
];

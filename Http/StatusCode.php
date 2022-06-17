<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Contracts\Http;

/**
 * Codes HTTP
 *
 * Content from http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
 *
 * @credit https://gist.github.com/henriquemoody/6580488
 */
interface StatusCode
{
    /**
     * Codes non officiels
     */
    public const CHECKPOINT = 103;

    public const THIS_IS_FIND                         = 218;  // Apache Web Server
    public const PAGE_EXPIRED                         = 419;  // Laravel Framework
    public const METHOD_FAILURE                       = 420;  // Spring Framework
    public const ENHANCE_YOUR_CALM                    = 420;  // Twitter
    public const LOGIN_TIMEOUT                        = 440;  // IIS
    public const NO_RESPONSE                          = 444;  // Nginx
    public const RETRY_WITH                           = 449;  // IIS
    public const BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;  // Microsoft
    public const REDIRECT                             = 451;  // IIS
    public const REQUEST_HEADER_TOO_LARGE             = 494;  // Nginx
    public const SSL_CERTIFICATE_ERROR                = 495;  // Nginx
    public const SSL_CERTIFICATE_REQUIRED             = 496;  // Nginx
    public const HTTP_REQUEST_SENT_TO_HTTPS_PORT      = 497;  // Nginx
    public const INVALID_TOKEN                        = 498;  // Esri
    public const CLIENT_CLOSED_REQUEST                = 499;  // Nginx
    public const TOKEN_REQUIRED                       = 499;  // Esri
    public const BANDWIDTH_LIMIT_EXCEEDED             = 509;  // Apache Web Server/cPanel
    public const WEB_SERVER_RETURNED_AN_UNKNOWN_ERROR = 520;  // Cloudflare
    public const WEB_SERVER_IS_DOWN                   = 521;  // Cloudflare
    public const CONNECTION_TIMEDOUT                  = 522;  // Cloudflare
    public const ORIGIN_IS_UNREACHABLE                = 523;  // Cloudflare
    public const A_TIMEOUT_OCCURRED                   = 524;  // Cloudflare
    public const SSL_HANDSHAKE_FAILED                 = 525;  // Cloudflare
    public const INVALID_SSL_CERTIFICATE              = 526;  // Cloudflare
    public const RAILGUN_ERROR                        = 527;  // Cloudflare
    public const SITE_IS_OVERLOADED                   = 529;  // Qualys in the SSLLabs
    public const SITE_IS_FROZEN                       = 530;  // Pantheon web platform
    public const NETWORK_READ_TIMEOUT_ERROR           = 598;  // Informal convention

    /**
     * Code officiels
     */
    // Informational 1xx
    public const CONTINUE            = 100;
    public const SWITCHING_PROTOCOLS = 101;
    public const PROCESSING          = 102;
    public const EARLY_HINTS         = 103;

    // Successful 2xx
    public const OK                            = 200;
    public const CREATED                       = 201;
    public const ACCEPTED                      = 202;
    public const NON_AUTHORITATIVE_INFORMATION = 203;  // Since HTTP/1.1
    public const NO_CONTENT                    = 204;
    public const RESET_CONTENT                 = 205;
    public const PARTIAL_CONTENT               = 206;  // RFC 7233
    public const MULTI_STATUS                  = 207;  // WebDAV; RFC 4918
    public const ALREADY_REPORTED              = 208;  // WebDAV; RFC 5842
    public const IM_USED                       = 226;  // RFC 3229

    // Redirection 3xx
    public const MULTIPLE_CHOICES   = 300;
    public const MOVED_PERMANENTLY  = 301;
    public const FOUND              = 302;  // Previously "Moved temporarily"
    public const SEE_OTHER          = 303;  // Since HTTP/1.1
    public const NOT_MODIFIED       = 304;
    public const USE_PROXY          = 305;  // Since HTTP/1.1
    public const SWITCH_PROXY       = 306;
    public const TEMPORARY_REDIRECT = 307;  // Since HTTP/1.1
    public const PERMANENT_REDIRECT = 308;  // RFC 7538

    // Client Errors 4xx
    public const BAD_REQUEST                     = 400;
    public const UNAUTHORIZED                    = 401;  // RFC 7235
    public const PAYMENT_REQUIRED                = 402;
    public const FORBIDDEN                       = 403;
    public const NOT_FOUND                       = 404;
    public const METHOD_NOT_ALLOWED              = 405;
    public const NOT_ACCEPTABLE                  = 406;
    public const PROXY_AUTHENTICATION_REQUIRED   = 407;  // RFC 7235
    public const REQUEST_TIMEOUT                 = 408;
    public const CONFLICT                        = 409;
    public const GONE                            = 410;
    public const LENGTH_REQUIRED                 = 411;
    public const PRECONDITION_FAILED             = 412;  // RFC 7232
    public const PAYLOAD_TOO_LARGE               = 413;
    public const URI_TOO_LONG                    = 414;  // RFC 7231
    public const UNSUPPORTED_MEDIA_TYPE          = 415;  // RFC 7231
    public const RANGE_NOT_SATISFIABLE           = 416;  // RFC 7233
    public const EXPECTATION_FAILED              = 417;
    public const IM_A_TEAPOT                     = 418;  // RFC 2324, RFC 7233
    public const MISDIRECTED_REQUEST             = 421;  // RFC 7540
    public const UNPROCESSABLE_ENTITY            = 422;  // WebDAV; RFC 4918
    public const LOCKED                          = 423;  // WebDAV; RFC 4918
    public const FAILED_DEPENDENCY               = 424;  // WebDAV; RFC 4918
    public const TOO_EARLY                       = 425;  // RFC 8470
    public const UPGRADE_REQUIRED                = 426;
    public const PRECONDITION_REQUIRED           = 428;  // RFC 6585
    public const TOO_MANY_REQUESTS               = 429;  // RFC 6585
    public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;  // RFC 6585
    public const UNAVAILABLE_FOR_LEGAL_REASONS   = 451;  // RFC 7725

    // Server Errors 5xx
    public const INTERNAL_ERROR                  = 500;
    public const NOT_IMPLEMENTED                 = 501;
    public const BAD_GATEWAY                     = 502;
    public const SERVICE_UNAVAILABLE             = 503;
    public const GATEWAY_TIMEOUT                 = 504;
    public const HTTP_VERSION_NOT_SUPPORTED      = 505;
    public const VARIANT_ALSO_NEGOTIATES         = 506;  // RFC 2295
    public const INSUFFICIENT_STORAGE            = 507;  // WebDAV; RFC 4918
    public const LOOP_DETECTED                   = 508;  // WebDAV; RFC 4918
    public const NOT_EXTENDED                    = 510;  // RFC 2774
    public const NOTWORK_AUTHENTICATION_REQUIRED = 511;  // RFC 6585
}

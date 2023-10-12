<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Http;

use BlitzPHP\Contracts\Http\StatusCode;
use BlitzPHP\Contracts\Session\CookieInterface;
use BlitzPHP\Exceptions\HttpException;
use BlitzPHP\Exceptions\LoadException;
use BlitzPHP\Http\Concerns\ResponseTrait;
use BlitzPHP\Session\Cookie\CookieCollection;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use GuzzleHttp\Psr7\MessageTrait;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;

/**
 * Les réponses contiennent le texte de la réponse, l'état et les en-têtes d'une réponse HTTP.
 *
 * Il existe des packages externes tels que `fig/http-message-util` qui fournissent HTTP
 * constantes de code d'état. Ceux-ci peuvent être utilisés avec n'importe quelle méthode qui accepte ou
 * renvoie un entier de code d'état. Gardez à l'esprit que ces constantes peuvent
 * inclure les codes d'état qui sont maintenant autorisés, ce qui lancera un
 * `\InvalidArgumentException`.
 *
 * @credit CakePHP <a href="https://api.cakephp.org/4.3/class-Cake.Http.Response.html">Cake\Http\Response</a>
 */
class Response implements ResponseInterface
{
    use MessageTrait;
    use ResponseTrait;

    /**
     * @var int
     */
    public const STATUS_CODE_MIN = 100;

    /**
     * @var int
     */
    public const STATUS_CODE_MAX = 599;

    /**
     * Codes d'état HTTP autorisés et leur description par défaut.
     *
     * @var array<int, string>
     */
    protected $_statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        226 => 'IM used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'Unsupported Version',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * Contient la clé de type pour les mappages de type mime pour les types mime connus.
     *
     * @var array<string, mixed>
     */
    protected $_mimeTypes = [
        'html'    => ['text/html', '*/*'],
        'json'    => 'application/json',
        'xml'     => ['application/xml', 'text/xml'],
        'xhtml'   => ['application/xhtml+xml', 'application/xhtml', 'text/xhtml'],
        'webp'    => 'image/webp',
        'rss'     => 'application/rss+xml',
        'ai'      => 'application/postscript',
        'bcpio'   => 'application/x-bcpio',
        'bin'     => 'application/octet-stream',
        'ccad'    => 'application/clariscad',
        'cdf'     => 'application/x-netcdf',
        'class'   => 'application/octet-stream',
        'cpio'    => 'application/x-cpio',
        'cpt'     => 'application/mac-compactpro',
        'csh'     => 'application/x-csh',
        'csv'     => ['text/csv', 'application/vnd.ms-excel'],
        'dcr'     => 'application/x-director',
        'dir'     => 'application/x-director',
        'dms'     => 'application/octet-stream',
        'doc'     => 'application/msword',
        'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'drw'     => 'application/drafting',
        'dvi'     => 'application/x-dvi',
        'dwg'     => 'application/acad',
        'dxf'     => 'application/dxf',
        'dxr'     => 'application/x-director',
        'eot'     => 'application/vnd.ms-fontobject',
        'eps'     => 'application/postscript',
        'exe'     => 'application/octet-stream',
        'ez'      => 'application/andrew-inset',
        'flv'     => 'video/x-flv',
        'gtar'    => 'application/x-gtar',
        'gz'      => 'application/x-gzip',
        'bz2'     => 'application/x-bzip',
        '7z'      => 'application/x-7z-compressed',
        'hal'     => ['application/hal+xml', 'application/vnd.hal+xml'],
        'haljson' => ['application/hal+json', 'application/vnd.hal+json'],
        'halxml'  => ['application/hal+xml', 'application/vnd.hal+xml'],
        'hdf'     => 'application/x-hdf',
        'hqx'     => 'application/mac-binhex40',
        'ico'     => 'image/x-icon',
        'ips'     => 'application/x-ipscript',
        'ipx'     => 'application/x-ipix',
        'js'      => 'application/javascript',
        'jsonapi' => 'application/vnd.api+json',
        'latex'   => 'application/x-latex',
        'jsonld'  => 'application/ld+json',
        'kml'     => 'application/vnd.google-earth.kml+xml',
        'kmz'     => 'application/vnd.google-earth.kmz',
        'lha'     => 'application/octet-stream',
        'lsp'     => 'application/x-lisp',
        'lzh'     => 'application/octet-stream',
        'man'     => 'application/x-troff-man',
        'me'      => 'application/x-troff-me',
        'mif'     => 'application/vnd.mif',
        'ms'      => 'application/x-troff-ms',
        'nc'      => 'application/x-netcdf',
        'oda'     => 'application/oda',
        'otf'     => 'font/otf',
        'pdf'     => 'application/pdf',
        'pgn'     => 'application/x-chess-pgn',
        'pot'     => 'application/vnd.ms-powerpoint',
        'pps'     => 'application/vnd.ms-powerpoint',
        'ppt'     => 'application/vnd.ms-powerpoint',
        'pptx'    => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppz'     => 'application/vnd.ms-powerpoint',
        'pre'     => 'application/x-freelance',
        'prt'     => 'application/pro_eng',
        'ps'      => 'application/postscript',
        'roff'    => 'application/x-troff',
        'scm'     => 'application/x-lotusscreencam',
        'set'     => 'application/set',
        'sh'      => 'application/x-sh',
        'shar'    => 'application/x-shar',
        'sit'     => 'application/x-stuffit',
        'skd'     => 'application/x-koan',
        'skm'     => 'application/x-koan',
        'skp'     => 'application/x-koan',
        'skt'     => 'application/x-koan',
        'smi'     => 'application/smil',
        'smil'    => 'application/smil',
        'sol'     => 'application/solids',
        'spl'     => 'application/x-futuresplash',
        'src'     => 'application/x-wais-source',
        'step'    => 'application/STEP',
        'stl'     => 'application/SLA',
        'stp'     => 'application/STEP',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc'  => 'application/x-sv4crc',
        'svg'     => 'image/svg+xml',
        'svgz'    => 'image/svg+xml',
        'swf'     => 'application/x-shockwave-flash',
        't'       => 'application/x-troff',
        'tar'     => 'application/x-tar',
        'tcl'     => 'application/x-tcl',
        'tex'     => 'application/x-tex',
        'texi'    => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tr'      => 'application/x-troff',
        'tsp'     => 'application/dsptype',
        'ttc'     => 'font/ttf',
        'ttf'     => 'font/ttf',
        'unv'     => 'application/i-deas',
        'ustar'   => 'application/x-ustar',
        'vcd'     => 'application/x-cdlink',
        'vda'     => 'application/vda',
        'xlc'     => 'application/vnd.ms-excel',
        'xll'     => 'application/vnd.ms-excel',
        'xlm'     => 'application/vnd.ms-excel',
        'xls'     => 'application/vnd.ms-excel',
        'xlsx'    => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlw'     => 'application/vnd.ms-excel',
        'zip'     => 'application/zip',
        'aif'     => 'audio/x-aiff',
        'aifc'    => 'audio/x-aiff',
        'aiff'    => 'audio/x-aiff',
        'au'      => 'audio/basic',
        'kar'     => 'audio/midi',
        'mid'     => 'audio/midi',
        'midi'    => 'audio/midi',
        'mp2'     => 'audio/mpeg',
        'mp3'     => 'audio/mpeg',
        'mpga'    => 'audio/mpeg',
        'ogg'     => 'audio/ogg',
        'oga'     => 'audio/ogg',
        'spx'     => 'audio/ogg',
        'ra'      => 'audio/x-realaudio',
        'ram'     => 'audio/x-pn-realaudio',
        'rm'      => 'audio/x-pn-realaudio',
        'rpm'     => 'audio/x-pn-realaudio-plugin',
        'snd'     => 'audio/basic',
        'tsi'     => 'audio/TSP-audio',
        'wav'     => 'audio/x-wav',
        'aac'     => 'audio/aac',
        'asc'     => 'text/plain',
        'c'       => 'text/plain',
        'cc'      => 'text/plain',
        'css'     => 'text/css',
        'etx'     => 'text/x-setext',
        'f'       => 'text/plain',
        'f90'     => 'text/plain',
        'h'       => 'text/plain',
        'hh'      => 'text/plain',
        'htm'     => ['text/html', '*/*'],
        'ics'     => 'text/calendar',
        'm'       => 'text/plain',
        'rtf'     => 'text/rtf',
        'rtx'     => 'text/richtext',
        'sgm'     => 'text/sgml',
        'sgml'    => 'text/sgml',
        'tsv'     => 'text/tab-separated-values',
        'tpl'     => 'text/template',
        'txt'     => 'text/plain',
        'text'    => 'text/plain',
        'avi'     => 'video/x-msvideo',
        'fli'     => 'video/x-fli',
        'mov'     => 'video/quicktime',
        'movie'   => 'video/x-sgi-movie',
        'mpe'     => 'video/mpeg',
        'mpeg'    => 'video/mpeg',
        'mpg'     => 'video/mpeg',
        'qt'      => 'video/quicktime',
        'viv'     => 'video/vnd.vivo',
        'vivo'    => 'video/vnd.vivo',
        'ogv'     => 'video/ogg',
        'webm'    => 'video/webm',
        'mp4'     => 'video/mp4',
        'm4v'     => 'video/mp4',
        'f4v'     => 'video/mp4',
        'f4p'     => 'video/mp4',
        'm4a'     => 'audio/mp4',
        'f4a'     => 'audio/mp4',
        'f4b'     => 'audio/mp4',
        'gif'     => 'image/gif',
        'ief'     => 'image/ief',
        'jpg'     => 'image/jpeg',
        'jpeg'    => 'image/jpeg',
        'jpe'     => 'image/jpeg',
        'pbm'     => 'image/x-portable-bitmap',
        'pgm'     => 'image/x-portable-graymap',
        'png'     => 'image/png',
        'pnm'     => 'image/x-portable-anymap',
        'ppm'     => 'image/x-portable-pixmap',
        'ras'     => 'image/cmu-raster',
        'rgb'     => 'image/x-rgb',
        'tif'     => 'image/tiff',
        'tiff'    => 'image/tiff',
        'xbm'     => 'image/x-xbitmap',
        'xpm'     => 'image/x-xpixmap',
        'xwd'     => 'image/x-xwindowdump',
        'psd'     => [
            'application/photoshop',
            'application/psd',
            'image/psd',
            'image/x-photoshop',
            'image/photoshop',
            'zz-application/zz-winassoc-psd',
        ],
        'ice'          => 'x-conference/x-cooltalk',
        'iges'         => 'model/iges',
        'igs'          => 'model/iges',
        'mesh'         => 'model/mesh',
        'msh'          => 'model/mesh',
        'silo'         => 'model/mesh',
        'vrml'         => 'model/vrml',
        'wrl'          => 'model/vrml',
        'mime'         => 'www/mime',
        'pdb'          => 'chemical/x-pdb',
        'xyz'          => 'chemical/x-pdb',
        'javascript'   => 'application/javascript',
        'form'         => 'application/x-www-form-urlencoded',
        'file'         => 'multipart/form-data',
        'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
        'atom'         => 'application/atom+xml',
        'amf'          => 'application/x-amf',
        'wap'          => ['text/vnd.wap.wml', 'text/vnd.wap.wmlscript', 'image/vnd.wap.wbmp'],
        'wml'          => 'text/vnd.wap.wml',
        'wmlscript'    => 'text/vnd.wap.wmlscript',
        'wbmp'         => 'image/vnd.wap.wbmp',
        'woff'         => 'application/x-font-woff',
        'appcache'     => 'text/cache-manifest',
        'manifest'     => 'text/cache-manifest',
        'htc'          => 'text/x-component',
        'rdf'          => 'application/xml',
        'crx'          => 'application/x-chrome-extension',
        'oex'          => 'application/x-opera-extension',
        'xpi'          => 'application/x-xpinstall',
        'safariextz'   => 'application/octet-stream',
        'webapp'       => 'application/x-web-app-manifest+json',
        'vcf'          => 'text/x-vcard',
        'vtt'          => 'text/vtt',
        'mkv'          => 'video/x-matroska',
        'pkpass'       => 'application/vnd.apple.pkpass',
        'ajax'         => 'text/html',
        'bmp'          => 'image/bmp',
    ];

    /**
     * Code de statut à envoyer au client
     *
     * @var int
     */
    protected $_status = 200;

    /**
     * Objet de fichier pour le fichier à lire comme réponse
     *
     * @var SplFileInfo|null
     */
    protected $_file;

    /**
     * Gamme de fichiers. Utilisé pour demander des plages de fichiers.
     *
     * @var array<int>
     */
    protected $_fileRange = [];

    /**
     * Le jeu de caractères avec lequel le corps de la réponse est encodé
     *
     * @var string
     */
    protected $_charset = 'UTF-8';

    /**
     * Contient toutes les directives de cache qui seront converties
     * dans les en-têtes lors de l'envoi de la requête
     *
     * @var array
     */
    protected $_cacheDirectives = [];

    /**
     * Collecte de cookies à envoyer au client
     *
     * @var CookieCollection
     */
    protected $_cookies;

    /**
     * Phrase de raison
     *
     * @var string
     */
    protected $_reasonPhrase = 'OK';

    /**
     * Options du mode flux.
     *
     * @var string
     */
    protected $_streamMode = 'wb+';

    /**
     * Cible de flux ou objet de ressource.
     *
     * @var resource|string
     */
    protected $_streamTarget = 'php://memory';

    /**
     * Constructeur
     *
     * @param array<string, mixed> $options liste de paramètres pour configurer la réponse. Les valeurs possibles sont :
     *
     * - body : le texte de réponse qui doit être envoyé au client
     * - status : le code d'état HTTP avec lequel répondre
     * - type : une chaîne complète de type mime ou une extension mappée dans cette classe
     * - charset : le jeu de caractères pour le corps de la réponse
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        $this->_streamTarget = $options['streamTarget'] ?? $this->_streamTarget;
        $this->_streamMode   = $options['streamMode'] ?? $this->_streamMode;

        if (isset($options['stream'])) {
            if (! $options['stream'] instanceof StreamInterface) {
                throw new InvalidArgumentException('Stream option must be an object that implements StreamInterface');
            }
            $this->stream = $options['stream'];
        } else {
            $this->_createStream();
        }

        if (isset($options['body'])) {
            $this->stream->write($options['body']);
        }

        if (isset($options['status'])) {
            $this->_setStatus($options['status']);
        }

        if (! isset($options['charset'])) {
            $options['charset'] = config('app.charset');
        }
        $this->_charset = $options['charset'];

        $type = 'text/html';
        if (isset($options['type'])) {
            $type = $this->resolveType($options['type']);
        }
        $this->_setContentType($type);

        $this->_cookies = new CookieCollection();
    }

    /**
     * Crée l'objet de flux.
     */
    protected function _createStream(): void
    {
        $this->stream = new Stream(Utils::tryFopen($this->_streamTarget, $this->_streamMode));
    }

    /**
     * Formate l'en-tête Content-Type en fonction du contentType et du jeu de caractères configurés
     * le jeu de caractères ne sera défini dans l'en-tête que si la réponse est de type texte
     */
    protected function _setContentType(string $type): void
    {
        if (in_array($this->_status, [304, 204], true)) {
            $this->_clearHeader('Content-Type');

            return;
        }
        $allowed = [
            'application/javascript', 'application/xml', 'application/rss+xml',
        ];

        $charset = false;
        if (
            $this->_charset
            && (
                str_starts_with($type, 'text/')
                || in_array($type, $allowed, true)
            )
        ) {
            $charset = true;
        }

        if ($charset && ! str_contains($type, ';')) {
            $this->_setHeader('Content-Type', "{$type}; charset={$this->_charset}");
        } else {
            $this->_setHeader('Content-Type', $type);
        }
    }

    /**
     * Effectuez une redirection vers une nouvelle URL, en deux versions : en-tête ou emplacement.
     *
     * @param string $uri  L'URI vers laquelle rediriger
     * @param int    $code Le type de redirection, par défaut à 302
     *
     * @throws HttpException Pour un code d'état invalide.
     */
    public function redirect(string $uri, string $method = 'auto', ?int $code = null): self
    {
        // Suppose une réponse de code d'état 302 ; remplacer si nécessaire
        if (empty($code)) {
            $code = StatusCode::FOUND;
        }

        // Environnement IIS probable ? Utilisez 'refresh' pour une meilleure compatibilité
        if ($method === 'auto' && isset($_SERVER['SERVER_SOFTWARE']) && str_contains($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS')) {
            $method = 'refresh';
        }

        // remplace le code d'état pour HTTP/1.1 et supérieur
        // reference: http://en.wikipedia.org/wiki/Post/Redirect/Get
        if (isset($_SERVER['SERVER_PROTOCOL'], $_SERVER['REQUEST_METHOD']) && $this->getProtocolVersion() >= 1.1 && $method !== 'refresh') {
            $code = ($_SERVER['REQUEST_METHOD'] !== 'GET') ? StatusCode::SEE_OTHER : ($code === StatusCode::FOUND ? StatusCode::TEMPORARY_REDIRECT : $code);
        }

        $new = $method === 'refresh'
            ? $this->withHeader('Refresh', '0;url=' . $uri)
            : $this->withLocation($uri);

        return $new->withStatus($code);
    }

    /**
     * Renvoie une instance avec un en-tête d'emplacement mis à jour.
     *
     * Si le code d'état actuel est 200, il sera remplacé
     * avec 302.
     *
     * @param string $url L'emplacement vers lequel rediriger.
     *
     * @return static Une nouvelle réponse avec l'en-tête Location défini.
     */
    public function withLocation(string $url): static
    {
        $new = $this->withHeader('Location', $url);
        if ($new->_status === StatusCode::OK) {
            $new->_status = StatusCode::FOUND;
        }

        return $new;
    }

    /**
     * Définit un en-tête.
     *
     * @phpstan-param non-empty-string $header
     */
    protected function _setHeader(string $header, string $value): void
    {
        $normalized                     = strtolower($header);
        $this->headerNames[$normalized] = $header;
        $this->headers[$header]         = [$value];
    }

    /**
     * Effacer l'en-tête
     *
     * @phpstan-param non-empty-string $header
     */
    protected function _clearHeader(string $header): void
    {
        $normalized = strtolower($header);
        if (! isset($this->headerNames[$normalized])) {
            return;
        }
        $original = $this->headerNames[$normalized];
        unset($this->headerNames[$normalized], $this->headers[$original]);
    }

    /**
     * Obtient le code d'état de la réponse.
     *
     * Le code d'état est un code de résultat entier à 3 chiffres de la tentative du serveur
     * pour comprendre et satisfaire la demande.
     */
    public function getStatusCode(): int
    {
        return $this->_status;
    }

    /**
     * Renvoie une instance avec le code d'état spécifié et, éventuellement, la phrase de raison.
     *
     * Si aucune expression de raison n'est spécifiée, les implémentations PEUVENT choisir par défaut
     * à la RFC 7231 ou à l'expression de raison recommandée par l'IANA pour la réponse
     * code d'état.
     *
     * Cette méthode DOIT être mise en œuvre de manière à conserver la
     * immuabilité du message, et DOIT retourner une instance qui a le
     * état mis à jour et expression de raison.
     *
     * Si le code d'état est 304 ou 204, l'en-tête Content-Type existant
     * sera effacé, car ces codes de réponse n'ont pas de corps.
     *
     * Il existe des packages externes tels que `fig/http-message-util` qui fournissent HTTP
     * constantes de code d'état. Ceux-ci peuvent être utilisés avec n'importe quelle méthode qui accepte ou
     * renvoie un entier de code d'état. Cependant, gardez à l'esprit que ces constantes
     * peut inclure des codes d'état qui sont maintenant autorisés, ce qui lancera un
     * `\InvalidArgumentException`.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6
     * @see https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int    $code         Le code d'état entier à 3 chiffres à définir.
     * @param string $reasonPhrase La phrase de raison à utiliser avec le
     *                             code d'état fourni ; si aucun n'est fourni, les implémentations PEUVENT
     *                             utilisez les valeurs par défaut comme suggéré dans la spécification HTTP.
     *
     * @throws HttpException Pour les arguments de code d'état non valides.
     */
    public function withStatus($code, $reasonPhrase = ''): static
    {
        $new = clone $this;
        $new->_setStatus($code, $reasonPhrase);

        return $new;
    }

    /**
     * Modificateur pour l'état de la réponse
     *
     * @throws HttpException Pour les arguments de code d'état non valides.
     */
    protected function _setStatus(int $code, string $reasonPhrase = ''): void
    {
        if ($code < static::STATUS_CODE_MIN || $code > static::STATUS_CODE_MAX) {
            throw HttpException::invalidStatusCode($code);
        }

        $this->_status = $code;
        if ($reasonPhrase === '' && isset($this->_statusCodes[$code])) {
            $reasonPhrase = $this->_statusCodes[$code];
        }
        $this->_reasonPhrase = $reasonPhrase;

        // Ces codes d'état n'ont pas de corps et ne peuvent pas avoir de types de contenu.
        if (in_array($code, [304, 204], true)) {
            $this->_clearHeader('Content-Type');
        }
    }

    /**
     * Obtient la phrase de motif de réponse associée au code d'état.
     *
     * Parce qu'une phrase de raison n'est pas un élément obligatoire dans une réponse
     * ligne d'état, la valeur de la phrase de raison PEUT être nulle. Implémentations MAI
     * choisissez de renvoyer la phrase de raison recommandée par défaut RFC 7231 (ou celles
     * répertorié dans le registre des codes d'état HTTP IANA) pour la réponse
     * code d'état.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-6
     * @see http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    public function getReasonPhrase(): string
    {
        return $this->_reasonPhrase;
    }

    /**
     * Définit une définition de type de contenu dans la collection.
     *
     * Ex : setTypeMap('xhtml', ['application/xhtml+xml', 'application/xhtml'])
     *
     * Ceci est nécessaire pour RequestHandlerComponent et la reconnaissance des types.
     *
     * @param string          $type     Type de contenu.
     * @param string|string[] $mimeType Définition du type mime.
     */
    public function setTypeMap(string $type, $mimeType): void
    {
        $this->_mimeTypes[$type] = $mimeType;
    }

    /**
     * Renvoie le type de contenu actuel.
     */
    public function getType(): string
    {
        $header = $this->getHeaderLine('Content-Type');
        if (str_contains($header, ';')) {
            return explode(';', $header)[0];
        }

        return $header;
    }

    /**
     * Obtenez une réponse mise à jour avec le type de contenu défini.
     *
     * Si vous tentez de définir le type sur une réponse de code d'état 304 ou 204, le
     * Le type de contenu ne prendra pas effet car ces codes d'état n'ont pas de types de contenu.
     *
     * @param string $contentType Soit une extension de fichier qui sera mappée à un type MIME, soit un type MIME concret.
     */
    public function withType(string $contentType): static
    {
        $mappedType = $this->resolveType($contentType);
        $new        = clone $this;
        $new->_setContentType($mappedType);

        return $new;
    }

    /**
     * Traduire et valider les types de contenu.
     *
     * @param string $contentType Type de contenu ou alias de type.
     *
     * @return string Le type de contenu résolu
     *
     * @throws InvalidArgumentException Lorsqu'un type de contenu ou un alias non valide est utilisé.
     */
    protected function resolveType(string $contentType): string
    {
        $mapped = $this->getMimeType($contentType);
        if ($mapped) {
            return is_array($mapped) ? current($mapped) : $mapped;
        }
        if (! str_contains($contentType, '/')) {
            throw new InvalidArgumentException(sprintf('"%s" is an invalid content type.', $contentType));
        }

        return $contentType;
    }

    /**
     * Renvoie la définition du type mime pour un alias
     *
     * par exemple `getMimeType('pdf'); // renvoie 'application/pdf'`
     *
     * @param string $alias l'alias du type de contenu à mapper
     *
     * @return array|false|string Type mime mappé en chaîne ou false si $alias n'est pas mappé
     */
    public function getMimeType(string $alias)
    {
        return $this->_mimeTypes[$alias] ?? false;
    }

    /**
     * Mappe un type de contenu vers un alias
     *
     * par exemple `mapType('application/pdf'); // renvoie 'pdf'`
     *
     * @param array|string $ctype Soit un type de contenu de chaîne à mapper, soit un tableau de types.
     *
     * @return array|string|null Alias pour les types fournis.
     */
    public function mapType($ctype)
    {
        if (is_array($ctype)) {
            return array_map([$this, 'mapType'], $ctype);
        }

        foreach ($this->_mimeTypes as $alias => $types) {
            if (in_array($ctype, (array) $types, true)) {
                return $alias;
            }
        }

        return null;
    }

    /**
     * Renvoie le jeu de caractères actuel.
     */
    public function getCharset(): string
    {
        return $this->_charset;
    }

    /**
     * Obtenez une nouvelle instance avec un jeu de caractères mis à jour.
     */
    public function withCharset(string $charset): static
    {
        $new           = clone $this;
        $new->_charset = $charset;
        $new->_setContentType($this->getType());

        return $new;
    }

    /**
     * Créez une nouvelle instance avec des en-têtes pour indiquer au client de ne pas mettre en cache la réponse
     */
    public function withDisabledCache(): static
    {
        return $this->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
            ->withHeader('Last-Modified', gmdate(DATE_RFC7231))
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    /**
     * Créez une nouvelle instance avec les en-têtes pour activer la mise en cache du client.
     *
     * @param int|string $since un temps valide depuis que le texte de la réponse n'a pas été modifié
     * @param int|string $time  une heure valide pour l'expiration du cache
     */
    public function withCache($since, $time = '+1 day'): static
    {
        if (! is_int($time)) {
            $time = strtotime($time);
            if ($time === false) {
                throw new InvalidArgumentException(
                    'Invalid time parameter. Ensure your time value can be parsed by strtotime'
                );
            }
        }

        return $this
            ->withModified($since)
            ->withExpires($time)
            ->withSharable(true)
            ->withMaxAge($time - time())
            ->withHeader('Date', gmdate(DATE_RFC7231, time()));
    }

    /**
     * Créez une nouvelle instance avec le jeu de directives public/privé Cache-Control.
     *
     * @param bool     $public Si défini sur true, l'en-tête Cache-Control sera défini comme public
     *                         si défini sur false, la réponse sera définie sur privé.
     * @param int|null $time   temps en secondes après lequel la réponse ne doit plus être considérée comme fraîche.
     */
    public function withSharable(bool $public, ?int $time = null): static
    {
        $new = clone $this;
        unset($new->_cacheDirectives['private'], $new->_cacheDirectives['public']);

        $key                         = $public ? 'public' : 'private';
        $new->_cacheDirectives[$key] = true;

        if ($time !== null) {
            $new->_cacheDirectives['max-age'] = $time;
        }
        $new->_setCacheControl();

        return $new;
    }

    /**
     * Créez une nouvelle instance avec la directive Cache-Control s-maxage.
     *
     * Le max-age est le nombre de secondes après lesquelles la réponse ne doit plus être prise en compte
     * un bon candidat pour être extrait d'un cache partagé (comme dans un serveur proxy).
     *
     * @param int $seconds Le nombre de secondes pour max-age partagé
     */
    public function withSharedMaxAge(int $seconds): static
    {
        $new                               = clone $this;
        $new->_cacheDirectives['s-maxage'] = $seconds;
        $new->_setCacheControl();

        return $new;
    }

    /**
     * Créez une instance avec l'ensemble de directives Cache-Control max-age.
     *
     * Le max-age est le nombre de secondes après lesquelles la réponse ne doit plus être prise en compte
     * un bon candidat à récupérer dans le cache local (client).
     *
     * @param int $seconds Les secondes pendant lesquelles une réponse mise en cache peut être considérée comme valide
     */
    public function withMaxAge(int $seconds): static
    {
        $new                              = clone $this;
        $new->_cacheDirectives['max-age'] = $seconds;
        $new->_setCacheControl();

        return $new;
    }

    /**
     * Créez une instance avec le jeu de directives must-revalidate de Cache-Control.
     *
     * Définit la directive Cache-Control must-revalidate.
     * must-revalidate indique que la réponse ne doit pas être servie
     * obsolète par un cache en toutes circonstances sans revalidation préalable
     * avec l'origine.
     *
     * @param bool $enable active ou désactive la directive.
     */
    public function withMustRevalidate(bool $enable): static
    {
        $new = clone $this;
        if ($enable) {
            $new->_cacheDirectives['must-revalidate'] = true;
        } else {
            unset($new->_cacheDirectives['must-revalidate']);
        }
        $new->_setCacheControl();

        return $new;
    }

    /**
     * Méthode d'assistance pour générer un en-tête Cache-Control valide à partir du jeu d'options
     * dans d'autres méthodes
     */
    protected function _setCacheControl(): void
    {
        $control = '';

        foreach ($this->_cacheDirectives as $key => $val) {
            $control .= $val === true ? $key : sprintf('%s=%s', $key, $val);
            $control .= ', ';
        }
        $control = rtrim($control, ', ');
        $this->_setHeader('Cache-Control', $control);
    }

    /**
     * Créez une nouvelle instance avec l'ensemble d'en-tête Expires.
     *
     * ### Exemples:
     *
     * ```
     * // Va expirer le cache de réponse maintenant
     * $response->withExpires('maintenant')
     *
     * // Définira l'expiration dans les prochaines 24 heures
     * $response->withExpires(new DateTime('+1 jour'))
     * ```
     *
     * @param DateTimeInterface|int|string|null $time Chaîne d'heure valide ou instance de \DateTime.
     */
    public function withExpires($time): static
    {
        $date = $this->_getUTCDate($time);

        return $this->withHeader('Expires', $date->format(DATE_RFC7231));
    }

    /**
     * Créez une nouvelle instance avec le jeu d'en-tête Last-Modified.
     *
     * ### Exemples:
     *
     * ```
     * // Va expirer le cache de réponse maintenant
     * $response->withModified('now')
     *
     * // Définira l'expiration dans les prochaines 24 heures
     * $response->withModified(new DateTime('+1 jour'))
     * ```
     *
     * @param DateTimeInterface|int|string $time Chaîne d'heure valide ou instance de \DateTime.
     */
    public function withModified($time): static
    {
        $date = $this->_getUTCDate($time);

        return $this->withHeader('Last-Modified', $date->format(DATE_RFC7231));
    }

    /**
     * Définit la réponse comme non modifiée en supprimant tout contenu du corps
     * définir le code d'état sur "304 Non modifié" et supprimer tous
     * en-têtes contradictoires
     *
     * *Avertissement* Cette méthode modifie la réponse sur place et doit être évitée.
     */
    public function notModified(): void
    {
        $this->_createStream();
        $this->_setStatus(StatusCode::NOT_MODIFIED);

        $remove = [
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified',
        ];

        foreach ($remove as $header) {
            $this->_clearHeader($header);
        }
    }

    /**
     * Créer une nouvelle instance comme "non modifiée"
     *
     * Cela supprimera tout contenu du corps défini le code d'état
     * à "304" et en supprimant les en-têtes qui décrivent
     * un corps de réponse.
     */
    public function withNotModified(): static
    {
        $new = $this->withStatus(StatusCode::NOT_MODIFIED);
        $new->_createStream();
        $remove = [
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified',
        ];

        foreach ($remove as $header) {
            $new = $new->withoutHeader($header);
        }

        return $new;
    }

    /**
     * Créez une nouvelle instance avec l'ensemble d'en-tête Vary.
     *
     * Si un tableau est passé, les valeurs seront implosées dans une virgule
     * chaîne séparée. Si aucun paramètre n'est passé, alors un
     * le tableau avec la valeur actuelle de l'en-tête Vary est renvoyé
     *
     * @param string|string[] $cacheVariances Une seule chaîne Vary ou un tableau contenant la liste des écarts.
     */
    public function withVary($cacheVariances): static
    {
        return $this->withHeader('Vary', (array) $cacheVariances);
    }

    /**
     * Créez une nouvelle instance avec l'ensemble d'en-tête Etag.
     *
     * Les Etags sont une indication forte qu'une réponse peut être mise en cache par un
     * Client HTTP. Une mauvaise façon de générer des Etags est de créer un hachage de
     * la sortie de la réponse, génère à la place un hachage unique du
     * composants uniques qui identifient une demande, comme un
     * l'heure de modification, un identifiant de ressource et tout ce que vous considérez
     * qui rend la réponse unique.
     *
     * Le deuxième paramètre est utilisé pour informer les clients que le contenu a
     * modifié, mais sémantiquement, il est équivalent aux valeurs mises en cache existantes. Envisager
     * une page avec un compteur de visites, deux pages vues différentes sont équivalentes, mais
     * ils diffèrent de quelques octets. Cela permet au client de décider s'il doit
     * utiliser les données mises en cache.
     *
     * @param string $hash Le hachage unique qui identifie cette réponse
     * @param bool   $weak Si la réponse est sémantiquement la même que
     *                     autre avec le même hash ou non. La valeur par défaut est false
     */
    public function withEtag(string $hash, bool $weak = false): static
    {
        $hash = sprintf('%s"%s"', $weak ? 'W/' : '', $hash);

        return $this->withHeader('Etag', $hash);
    }

    /**
     * Renvoie un objet DateTime initialisé au paramètre $time et utilisant UTC
     * comme fuseau horaire
     *
     * @param DateTimeInterface|int|string|null $time Chaîne d'heure valide ou instance de \DateTimeInterface.
     */
    protected function _getUTCDate($time = null): DateTimeInterface
    {
        if ($time instanceof DateTimeInterface) {
            $result = clone $time;
        } elseif (is_int($time)) {
            $result = new DateTime(date('Y-m-d H:i:s', $time));
        } else {
            $result = new DateTime($time ?? 'now');
        }

        /** @psalm-suppress UndefinedInterfaceMethod */
        return $result->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Définit le bon gestionnaire de mise en mémoire tampon de sortie pour envoyer une réponse compressée.
     * Les réponses seront compressé avec zlib, si l'extension est disponible.
     *
     * @return bool false si le client n'accepte pas les réponses compressées ou si aucun gestionnaire n'est disponible, true sinon
     */
    public function compress(): bool
    {
        $compressionEnabled = ini_get('zlib.output_compression') !== '1'
            && extension_loaded('zlib')
            && (str_contains((string) env('HTTP_ACCEPT_ENCODING'), 'gzip'));

        return $compressionEnabled && ob_start('ob_gzhandler');
    }

    /**
     * Retourne VRAI si la sortie résultante sera compressée par PHP
     */
    public function outputCompressed(): bool
    {
        return str_contains((string) env('HTTP_ACCEPT_ENCODING'), 'gzip')
            && (ini_get('zlib.output_compression') === '1' || in_array('ob_gzhandler', ob_list_handlers(), true));
    }

    /**
     * Créez une nouvelle instance avec l'ensemble d'en-tête Content-Disposition.
     *
     * @param string $filename Le nom du fichier car le navigateur téléchargera la réponse
     */
    public function withDownload(string $filename): static
    {
        return $this->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Créez une nouvelle réponse avec l'ensemble d'en-tête Content-Length.
     *
     * @param int|string $bytes Nombre d'octets
     */
    public function withLength($bytes): static
    {
        return $this->withHeader('Content-Length', (string) $bytes);
    }

    /**
     * Créez une nouvelle réponse avec l'ensemble d'en-tête de lien.
     *
     * ### Exemples
     *
     * ```
     * $response = $response->withAddedLink('http://example.com?page=1', ['rel' => 'prev'])
     * ->withAddedLink('http://example.com?page=3', ['rel' => 'next']);
     * ```
     *
     * Générera :
     *
     * ```
     * Link : <http://example.com?page=1> ; rel="prev"
     * Link : <http://example.com?page=3> ; rel="suivant"
     * ```
     *
     * @param string               $url     L'URL LinkHeader.
     * @param array<string, mixed> $options Les paramètres LinkHeader.
     */
    public function withAddedLink(string $url, array $options = []): static
    {
        $params = [];

        foreach ($options as $key => $option) {
            $params[] = $key . '="' . $option . '"';
        }

        $param = '';
        if ($params) {
            $param = '; ' . implode('; ', $params);
        }

        return $this->withAddedHeader('Link', '<' . $url . '>' . $param);
    }

    /**
     * Vérifie si une réponse n'a pas été modifiée selon le 'If-None-Match'
     * (Etags) et requête 'If-Modified-Since' (dernière modification)
     * en-têtes. Si la réponse est détectée comme n'étant pas modifiée, elle
     * est marqué comme tel afin que le client puisse en être informé.
     *
     * Pour marquer une réponse comme non modifiée, vous devez définir au moins
     * l'en-tête de réponse etag Last-Modified avant d'appeler cette méthode. Autrement
     * une comparaison ne sera pas possible.
     *
     * *Avertissement* Cette méthode modifie la réponse sur place et doit être évitée.
     *
     * @param ServerRequest $request Objet de requête
     *
     * @return bool Indique si la réponse a été marquée comme non modifiée ou non.
     */
    public function checkNotModified(ServerRequest $request): bool
    {
        $etags       = preg_split('/\s*,\s*/', $request->getHeaderLine('If-None-Match'), 0, PREG_SPLIT_NO_EMPTY);
        $responseTag = $this->getHeaderLine('Etag');
        $etagMatches = null;
        if ($responseTag) {
            $etagMatches = in_array('*', $etags, true) || in_array($responseTag, $etags, true);
        }

        $modifiedSince = $request->getHeaderLine('If-Modified-Since');
        $timeMatches   = null;
        if ($modifiedSince && $this->hasHeader('Last-Modified')) {
            $timeMatches = strtotime($this->getHeaderLine('Last-Modified')) === strtotime($modifiedSince);
        }
        if ($etagMatches === null && $timeMatches === null) {
            return false;
        }
        $notModified = $etagMatches !== false && $timeMatches !== false;
        if ($notModified) {
            $this->notModified();
        }

        return $notModified;
    }

    /**
     * Conversion de chaînes. Récupère le corps de la réponse sous forme de chaîne.
     * N'envoie *pas* d'en-têtes.
     * Si body est un appelable, une chaîne vide est renvoyée.
     */
    public function __toString(): string
    {
        $this->stream->rewind();

        return $this->stream->getContents();
    }

    /**
     * Créez une nouvelle réponse avec un jeu de cookies.
     *
     * ### Exemple
     *
     * ```
     * // ajouter un objet cookie
     * $response = $response->withCookie(new Cookie('remember_me', 1));
     */
    public function withCookie(CookieInterface $cookie): static
    {
        $new           = clone $this;
        $new->_cookies = $new->_cookies->add($cookie);

        return $new;
    }

    /**
     * Créez une nouvelle réponse avec un jeu de cookies expiré.
     *
     * ### Exemple
     *
     * ```
     * // ajouter un objet cookie
     * $response = $response->withExpiredCookie(new Cookie('remember_me'));
     */
    public function withExpiredCookie(CookieInterface $cookie): static
    {
        $cookie = $cookie->withExpired();

        $new           = clone $this;
        $new->_cookies = $new->_cookies->add($cookie);

        return $new;
    }

    /**
     * Lire un seul cookie à partir de la réponse.
     *
     * Cette méthode fournit un accès en lecture aux cookies en attente. Ce sera
     * ne lit pas l'en-tête `Set-Cookie` s'il est défini.
     *
     * @param string $name Le nom du cookie que vous souhaitez lire.
     *
     * @return array|null Soit les données du cookie, soit null
     */
    public function getCookie(string $name): ?array
    {
        if (! $this->_cookies->has($name)) {
            return null;
        }

        return $this->_cookies->get($name)->toArray();
    }

    /**
     * Obtenez tous les cookies dans la réponse.
     *
     * Renvoie un tableau associatif de nom de cookie => données de cookie.
     */
    public function getCookies(): array
    {
        $out = [];
        /** @var array<\BlitzPHP\Session\Cookie\Cookie> $cookies */
        $cookies = $this->_cookies;

        foreach ($cookies as $cookie) {
            $out[$cookie->getName()] = $cookie->toArray();
        }

        return $out;
    }

    /**
     * Obtenez la CookieCollection à partir de la réponse
     */
    public function getCookieCollection(): CookieCollection
    {
        return $this->_cookies;
    }

    /**
     * Obtenez une nouvelle instance avec la collection de cookies fournie.
     */
    public function withCookieCollection(CookieCollection $cookieCollection): static
    {
        $new           = clone $this;
        $new->_cookies = $cookieCollection;

        return $new;
    }

    /**
     * Créez une nouvelle instance basée sur un fichier.
     *
     * Cette méthode augmentera à la fois le corps et un certain nombre d'en-têtes associés.
     *
     * Si `$_SERVER['HTTP_RANGE']` est défini, une tranche du fichier sera
     * retourné au lieu du fichier entier.
     *
     * ### Touches d'options
     *
     * - nom : autre nom de téléchargement
     * - download : si `true` définit l'en-tête de téléchargement et force le fichier à
     * être téléchargé plutôt qu'affiché en ligne.
     *
     * @param string               $path    Chemin d'accès absolu au fichier.
     * @param array<string, mixed> $options Options Voir ci-dessus.
     *
     * @throws LoadException
     */
    public function withFile(string $path, array $options = []): static
    {
        $file = $this->validateFile($path);
        $options += [
            'name'     => null,
            'download' => null,
        ];

        $extension = strtolower($file->getExtension());
        $mapped    = $this->getMimeType($extension);
        if ((! $extension || ! $mapped) && $options['download'] === null) {
            $options['download'] = true;
        }

        $new = clone $this;
        if ($mapped) {
            $new = $new->withType($extension);
        }

        $fileSize = $file->getSize();
        if ($options['download']) {
            $agent = (string) env('HTTP_USER_AGENT');

            if ($agent && preg_match('%Opera([/ ])([0-9].[0-9]{1,2})%', $agent)) {
                $contentType = 'application/octet-stream';
            } elseif ($agent && preg_match('/MSIE ([0-9].[0-9]{1,2})/', $agent)) {
                $contentType = 'application/force-download';
            }

            if (isset($contentType)) {
                $new = $new->withType($contentType);
            }
            $name = $options['name'] ?: $file->getFileName();
            $new  = $new->withDownload($name)
                ->withHeader('Content-Transfer-Encoding', 'binary');
        }

        $new       = $new->withHeader('Accept-Ranges', 'bytes');
        $httpRange = (string) env('HTTP_RANGE');
        if ($httpRange) {
            $new->_fileRange($file, $httpRange);
        } else {
            $new = $new->withHeader('Content-Length', (string) $fileSize);
        }
        $new->_file  = $file;
        $new->stream = new Stream(Utils::tryFopen($file->getPathname(), 'rb'));

        return $new;
    }

    /**
     * Méthode pratique pour définir une chaîne dans le corps de la réponse
     *
     * @param string|null $string La chaîne à envoyer
     */
    public function withStringBody(?string $string): static
    {
        $new = clone $this;

        return $new->withBody(Utils::streamFor($string));
    }

    /**
     * Valider qu'un chemin de fichier est un corps de réponse valide.
     *
     * @throws LoadException
     */
    protected function validateFile(string $path): SplFileInfo
    {
        if (str_contains($path, '../') || str_contains($path, '..\\')) {
            throw new LoadException('The requested file contains `..` and will not be read.');
        }
        if (! is_file($path)) {
            $path = APP_PATH . $path;
        }

        $file = new SplFileInfo($path);
        if (! $file->isFile() || ! $file->isReadable()) {
            if (on_dev()) {
                throw new LoadException(sprintf('The requested file %s was not found or not readable', $path));
            }

            throw new LoadException('The requested file was not found');
        }

        return $file;
    }

    /**
     * Obtenir le fichier actuel s'il en existe un.
     *
     * @return SplFileInfo|null Le fichier à utiliser dans la réponse ou null
     */
    public function getFile(): ?SplFileInfo
    {
        return $this->_file;
    }

    /**
     * Appliquez une plage de fichiers à un fichier et définissez le décalage de fin.
     *
     * Si une plage non valide est demandée, un code d'état 416 sera utilisé
     * dans la réponse.
     *
     * @param SplFileInfo $file      Le fichier sur lequel définir une plage.
     * @param string      $httpRange La plage à utiliser.
     */
    protected function _fileRange(SplFileInfo $file, string $httpRange): void
    {
        $fileSize = $file->getSize();
        $lastByte = $fileSize - 1;
        $start    = 0;
        $end      = $lastByte;

        preg_match('/^bytes\s*=\s*(\d+)?\s*-\s*(\d+)?$/', $httpRange, $matches);
        if ($matches) {
            $start = $matches[1];
            $end   = $matches[2] ?? '';
        }

        if ($start === '') {
            $start = $fileSize - (int) $end;
            $end   = $lastByte;
        }
        if ($end === '') {
            $end = $lastByte;
        }

        if ($start > $end || $end > $lastByte || $start > $lastByte) {
            $this->_setStatus(416);
            $this->_setHeader('Content-Range', 'bytes 0-' . $lastByte . '/' . $fileSize);

            return;
        }

        /** @psalm-suppress PossiblyInvalidOperand */
        $this->_setHeader('Content-Length', (string) ($end - $start + 1));
        $this->_setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $fileSize);
        $this->_setStatus(206);
        /**
         * @var int $start
         * @var int $end
         */
        $this->_fileRange = [$start, $end];
    }

    /**
     * Retourne un tableau qui peut être utilisé pour décrire l'état interne de cet objet.
     *
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return [
            'status'          => $this->_status,
            'contentType'     => $this->getType(),
            'headers'         => $this->headers,
            'file'            => $this->_file,
            'fileRange'       => $this->_fileRange,
            'cookies'         => $this->_cookies,
            'cacheDirectives' => $this->_cacheDirectives,
            'body'            => (string) $this->getBody(),
        ];
    }
}

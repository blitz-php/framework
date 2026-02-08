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

use finfo;

/**
 * Classe de gestion des types MIME
 *
 * Gère les opérations relatives aux types MIME et fournit des fonctionnalités
 * pour travailler avec les types MIME (Multipurpose Internet Mail Extensions)
 * dans l'application. Cette classe est responsable de la détection des types MIME
 * et de l'association des extensions de fichier avec leurs types MIME correspondants.
 */
class MimeType
{
    /**
     * Tableau des associations de types MIME
     *
     * Associe les extensions de fichier avec leur(s) type(s) MIME correspondant(s).
     * Chaque clé est une extension de fichier (sans le point) et la valeur est un tableau
     * d'un ou plusieurs types MIME valides pour cette extension.
     *
     * Types MIME communs inclus :
     * - Formats web (html, json, xml)
     * - Formats d'image (webp)
     * - Formats de flux (rss)
     * - Formats d'application (ai, bin, csv, etc.)
     *
     * Certaines extensions peuvent correspondre à plusieurs types MIME, le premier type
     * du tableau étant le type préféré/par défaut.
     *
     * @var array<string, list<string>>
     */
    protected static array $mimeTypes = [
        'html'    => ['text/html', '*/*'],
        'json'    => ['application/json'],
        'xml'     => ['application/xml', 'text/xml'],
        'xhtml'   => ['application/xhtml+xml', 'application/xhtml', 'text/xhtml'],
        'webp'    => ['image/webp'],
        'rss'     => ['application/rss+xml'],
        'ai'      => ['application/postscript'],
        'bcpio'   => ['application/x-bcpio'],
        'bin'     => ['application/octet-stream'],
        'ccad'    => ['application/clariscad'],
        'cdf'     => ['application/x-netcdf'],
        'class'   => ['application/octet-stream'],
        'cpio'    => ['application/x-cpio'],
        'cpt'     => ['application/mac-compactpro'],
        'csh'     => ['application/x-csh'],
        'csv'     => ['text/csv', 'application/vnd.ms-excel'],
        'dcr'     => ['application/x-director'],
        'dir'     => ['application/x-director'],
        'dms'     => ['application/octet-stream'],
        'doc'     => ['application/msword'],
        'docx'    => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'drw'     => ['application/drafting'],
        'dvi'     => ['application/x-dvi'],
        'dwg'     => ['application/acad'],
        'dxf'     => ['application/dxf'],
        'dxr'     => ['application/x-director'],
        'eot'     => ['application/vnd.ms-fontobject'],
        'eps'     => ['application/postscript'],
        'exe'     => ['application/octet-stream'],
        'ez'      => ['application/andrew-inset'],
        'flv'     => ['video/x-flv'],
        'gtar'    => ['application/x-gtar'],
        'gz'      => ['application/x-gzip'],
        'bz2'     => ['application/x-bzip'],
        '7z'      => ['application/x-7z-compressed'],
        'hal'     => ['application/hal+xml', 'application/vnd.hal+xml'],
        'haljson' => ['application/hal+json', 'application/vnd.hal+json'],
        'halxml'  => ['application/hal+xml', 'application/vnd.hal+xml'],
        'hdf'     => ['application/x-hdf'],
        'hqx'     => ['application/mac-binhex40'],
        'ico'     => ['image/x-icon'],
        'ips'     => ['application/x-ipscript'],
        'ipx'     => ['application/x-ipix'],
        'js'      => ['application/javascript'],
        'cjs'     => ['application/javascript'],
        'mjs'     => ['application/javascript'],
        'jsonapi' => ['application/vnd.api+json'],
        'latex'   => ['application/x-latex'],
        'jsonld'  => ['application/ld+json'],
        'kml'     => ['application/vnd.google-earth.kml+xml'],
        'kmz'     => ['application/vnd.google-earth.kmz'],
        'lha'     => ['application/octet-stream'],
        'lsp'     => ['application/x-lisp'],
        'lzh'     => ['application/octet-stream'],
        'man'     => ['application/x-troff-man'],
        'me'      => ['application/x-troff-me'],
        'mif'     => ['application/vnd.mif'],
        'ms'      => ['application/x-troff-ms'],
        'nc'      => ['application/x-netcdf'],
        'oda'     => ['application/oda'],
        'otf'     => ['font/otf'],
        'pdf'     => ['application/pdf'],
        'pgn'     => ['application/x-chess-pgn'],
        'pot'     => ['application/vnd.ms-powerpoint'],
        'pps'     => ['application/vnd.ms-powerpoint'],
        'ppt'     => ['application/vnd.ms-powerpoint'],
        'pptx'    => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'ppz'     => ['application/vnd.ms-powerpoint'],
        'pre'     => ['application/x-freelance'],
        'prt'     => ['application/pro_eng'],
        'ps'      => ['application/postscript'],
        'roff'    => ['application/x-troff'],
        'scm'     => ['application/x-lotusscreencam'],
        'set'     => ['application/set'],
        'sh'      => ['application/x-sh'],
        'shar'    => ['application/x-shar'],
        'sit'     => ['application/x-stuffit'],
        'skd'     => ['application/x-koan'],
        'skm'     => ['application/x-koan'],
        'skp'     => ['application/x-koan'],
        'skt'     => ['application/x-koan'],
        'smi'     => ['application/smil'],
        'smil'    => ['application/smil'],
        'sol'     => ['application/solids'],
        'spl'     => ['application/x-futuresplash'],
        'src'     => ['application/x-wais-source'],
        'step'    => ['application/STEP'],
        'stl'     => ['application/SLA'],
        'stp'     => ['application/STEP'],
        'sv4cpio' => ['application/x-sv4cpio'],
        'sv4crc'  => ['application/x-sv4crc'],
        'svg'     => ['image/svg+xml'],
        'svgz'    => ['image/svg+xml'],
        'swf'     => ['application/x-shockwave-flash'],
        't'       => ['application/x-troff'],
        'tar'     => ['application/x-tar'],
        'tcl'     => ['application/x-tcl'],
        'tex'     => ['application/x-tex'],
        'texi'    => ['application/x-texinfo'],
        'texinfo' => ['application/x-texinfo'],
        'tr'      => ['application/x-troff'],
        'tsp'     => ['application/dsptype'],
        'ttc'     => ['font/ttf'],
        'ttf'     => ['font/ttf'],
        'unv'     => ['application/i-deas'],
        'ustar'   => ['application/x-ustar'],
        'vcd'     => ['application/x-cdlink'],
        'vda'     => ['application/vda'],
        'xlc'     => ['application/vnd.ms-excel'],
        'xll'     => ['application/vnd.ms-excel'],
        'xlm'     => ['application/vnd.ms-excel'],
        'xls'     => ['application/vnd.ms-excel'],
        'xlsx'    => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'xlw'     => ['application/vnd.ms-excel'],
        'zip'     => ['application/zip'],
        'aif'     => ['audio/x-aiff'],
        'aifc'    => ['audio/x-aiff'],
        'aiff'    => ['audio/x-aiff'],
        'au'      => ['audio/basic'],
        'kar'     => ['audio/midi'],
        'mid'     => ['audio/midi'],
        'midi'    => ['audio/midi'],
        'mp2'     => ['audio/mpeg'],
        'mp3'     => ['audio/mpeg'],
        'mpga'    => ['audio/mpeg'],
        'ogg'     => ['audio/ogg'],
        'oga'     => ['audio/ogg'],
        'spx'     => ['audio/ogg'],
        'ra'      => ['audio/x-realaudio'],
        'ram'     => ['audio/x-pn-realaudio'],
        'rm'      => ['audio/x-pn-realaudio'],
        'rpm'     => ['audio/x-pn-realaudio-plugin'],
        'snd'     => ['audio/basic'],
        'tsi'     => ['audio/TSP-audio'],
        'wav'     => ['audio/x-wav'],
        'aac'     => ['audio/aac'],
        'asc'     => ['text/plain'],
        'c'       => ['text/plain'],
        'cc'      => ['text/plain'],
        'css'     => ['text/css'],
        'etx'     => ['text/x-setext'],
        'f'       => ['text/plain'],
        'f90'     => ['text/plain'],
        'h'       => ['text/plain'],
        'hh'      => ['text/plain'],
        'htm'     => ['text/html', '*/*'],
        'ics'     => ['text/calendar'],
        'm'       => ['text/plain'],
        'rtf'     => ['text/rtf'],
        'rtx'     => ['text/richtext'],
        'sgm'     => ['text/sgml'],
        'sgml'    => ['text/sgml'],
        'tsv'     => ['text/tab-separated-values'],
        'tpl'     => ['text/template'],
        'txt'     => ['text/plain'],
        'text'    => ['text/plain'],
        'avi'     => ['video/x-msvideo'],
        'fli'     => ['video/x-fli'],
        'mov'     => ['video/quicktime'],
        'movie'   => ['video/x-sgi-movie'],
        'mpe'     => ['video/mpeg'],
        'mpeg'    => ['video/mpeg'],
        'mpg'     => ['video/mpeg'],
        'qt'      => ['video/quicktime'],
        'viv'     => ['video/vnd.vivo'],
        'vivo'    => ['video/vnd.vivo'],
        'ogv'     => ['video/ogg'],
        'webm'    => ['video/webm'],
        'mp4'     => ['video/mp4'],
        'm4v'     => ['video/mp4'],
        'f4v'     => ['video/mp4'],
        'f4p'     => ['video/mp4'],
        'm4a'     => ['audio/mp4'],
        'f4a'     => ['audio/mp4'],
        'f4b'     => ['audio/mp4'],
        'gif'     => ['image/gif'],
        'ief'     => ['image/ief'],
        'jpg'     => ['image/jpeg'],
        'jpeg'    => ['image/jpeg'],
        'jpe'     => ['image/jpeg'],
        'pbm'     => ['image/x-portable-bitmap'],
        'pgm'     => ['image/x-portable-graymap'],
        'png'     => ['image/png'],
        'pnm'     => ['image/x-portable-anymap'],
        'ppm'     => ['image/x-portable-pixmap'],
        'ras'     => ['image/cmu-raster'],
        'rgb'     => ['image/x-rgb'],
        'tif'     => ['image/tiff'],
        'tiff'    => ['image/tiff'],
        'xbm'     => ['image/x-xbitmap'],
        'xpm'     => ['image/x-xpixmap'],
        'xwd'     => ['image/x-xwindowdump'],
        'psd'     => [
            'application/photoshop',
            'application/psd',
            'image/psd',
            'image/x-photoshop',
            'image/photoshop',
            'zz-application/zz-winassoc-psd',
        ],
        'ice'          => ['x-conference/x-cooltalk'],
        'iges'         => ['model/iges'],
        'igs'          => ['model/iges'],
        'mesh'         => ['model/mesh'],
        'msh'          => ['model/mesh'],
        'silo'         => ['model/mesh'],
        'vrml'         => ['model/vrml'],
        'wrl'          => ['model/vrml'],
        'mime'         => ['www/mime'],
        'pdb'          => ['chemical/x-pdb'],
        'xyz'          => ['chemical/x-pdb'],
        'javascript'   => ['application/javascript'],
        'form'         => ['application/x-www-form-urlencoded'],
        'file'         => ['multipart/form-data'],
        'xhtml-mobile' => ['application/vnd.wap.xhtml+xml'],
        'atom'         => ['application/atom+xml'],
        'amf'          => ['application/x-amf'],
        'wap'          => ['text/vnd.wap.wml', 'text/vnd.wap.wmlscript', 'image/vnd.wap.wbmp'],
        'wml'          => ['text/vnd.wap.wml'],
        'wmlscript'    => ['text/vnd.wap.wmlscript'],
        'wbmp'         => ['image/vnd.wap.wbmp'],
        'woff'         => ['application/x-font-woff'],
        'appcache'     => ['text/cache-manifest'],
        'manifest'     => ['text/cache-manifest'],
        'htc'          => ['text/x-component'],
        'rdf'          => ['application/xml'],
        'crx'          => ['application/x-chrome-extension'],
        'oex'          => ['application/x-opera-extension'],
        'xpi'          => ['application/x-xpinstall'],
        'safariextz'   => ['application/octet-stream'],
        'webapp'       => ['application/x-web-app-manifest+json'],
        'vcf'          => ['text/x-vcard'],
        'vtt'          => ['text/vtt'],
        'mkv'          => ['video/x-matroska'],
        'pkpass'       => ['application/vnd.apple.pkpass'],
        'ajax'         => ['text/html'],
        'bmp'          => ['image/bmp'],
    ];

    /**
     * Récupère les types MIME associés à une extension de fichier donnée
     *
     * @param string $ext Extension de fichier à rechercher
     *
     * @return array|null Tableau des types MIME si trouvé, null si aucun type MIME n'est associé à l'extension
     */
    public static function getMimeTypes(string $ext): ?array
    {
        return static::$mimeTypes[$ext] ?? null;
    }

    /**
     * Récupère le type MIME principal basé sur l'extension de fichier
     *
     * @param string      $ext     Extension de fichier
     * @param string|null $default Type MIME par défaut à retourner si l'extension n'est pas trouvée
     *
     * @return string|null Type MIME correspondant à l'extension de fichier, ou le type MIME par défaut si non trouvé
     */
    public static function getMimeType(string $ext, ?string $default = null): ?string
    {
        return isset(static::$mimeTypes[$ext]) ? static::$mimeTypes[$ext][0] : null;
    }

    /**
     * Ajoute de nouveaux types MIME pour une extension de fichier donnée
     *
     * Si l'extension de fichier existe déjà, les nouveaux types MIME seront fusionnés
     * avec les types existants
     *
     * @param string       $ext       Extension de fichier à associer avec les types MIME
     * @param array|string $mimeTypes Types MIME à associer avec l'extension de fichier
     */
    public static function addMimeTypes(string $ext, array|string $mimeTypes): void
    {
        if (isset(static::$mimeTypes[$ext])) {
            static::$mimeTypes[$ext] = array_merge(static::$mimeTypes[$ext], (array) $mimeTypes);

            return;
        }

        static::$mimeTypes[$ext] = (array) $mimeTypes;
    }

    /**
     * Définit les types MIME pour une extension de fichier donnée
     *
     * Cela écrasera tous les types MIME existants pour l'extension de fichier
     *
     * @param string       $ext       Extension de fichier
     * @param array|string $mimeTypes Types MIME à associer avec l'extension de fichier
     */
    public static function setMimeTypes(string $ext, array|string $mimeTypes): void
    {
        static::$mimeTypes[$ext] = (array) $mimeTypes;
    }

    /**
     * Récupère l'extension de fichier associée à un type MIME donné
     *
     * @param string $mimeType Type MIME pour lequel récupérer l'extension de fichier
     *
     * @return string|null Extension de fichier associée au type MIME, ou null si aucune association n'est trouvée
     */
    public static function getExtension(string $mimeType): ?string
    {
        foreach (static::$mimeTypes as $ext => $types) {
            if (in_array($mimeType, $types, true)) {
                return $ext;
            }
        }

        return null;
    }

    /**
     * Récupère le type MIME pour un chemin de fichier donné
     *
     * Si le type MIME n'est pas mappé à une extension, la méthode tentera de déterminer
     * le type MIME du fichier en utilisant l'extension fileinfo
     *
     * @param string $path    Chemin du fichier pour lequel récupérer le type MIME
     * @param string $default Type MIME par défaut à retourner si le type MIME ne peut être déterminé
     *
     * @return string Type MIME du fichier, ou le type MIME par défaut s'il ne peut être déterminé
     */
    public static function getMimeTypeForFile(string $path, string $default = 'application/octet-stream'): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (isset(static::$mimeTypes[$ext])) {
            return static::$mimeTypes[$ext][0];
        }

        $finfo    = new finfo(FILEINFO_MIME);
        $mimeType = $finfo->file($path);

        return $mimeType === false ? $default : $mimeType;
    }
}

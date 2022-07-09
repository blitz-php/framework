<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace BlitzPHP\Annotations;

use BlitzPHP\Annotations\Http\AjaxOnlyAnnotation;
use BlitzPHP\Annotations\Http\RequestMappingAnnotation;
use BlitzPHP\Annotations\Validation\RangeAnnotation;
use BlitzPHP\Annotations\Validation\RequiredAnnotation;
use mindplay\annotations\AnnotationManager;

abstract class AnnotationPackager
{
    public static function register(AnnotationManager $annotationManager)
    {
        foreach (self::$unParsedAnnotations as $annotation) {
            $annotationManager->registry[$annotation] = false;
        }

        $annotationManager->registry['ajaxOnly']       = AjaxOnlyAnnotation::class;
        $annotationManager->registry['range']          = RangeAnnotation::class;
        $annotationManager->registry['requestMapping'] = RequestMappingAnnotation::class;
        $annotationManager->registry['required']       = RequiredAnnotation::class;
    }

    /**
     * @var array
     */
    private static $unParsedAnnotations = [
        'codeCoverageIgnore'                    ,
        'codeCoverageIgnoreEnd'                 ,
        'codeCoverageIgnoreStart'               ,
        'phpstan-ignore-line'                   ,
        'phpstan-ignore-next-line'              ,
        'psalm-allow-private-mutation'          ,
        'psalm-assert'                          ,
        'psalm-assert-if-false'                 ,
        'psalm-assert-if-true'                  ,
        'psalm-consistent-constructor'          ,
        'psalm-consistent-templates'            ,
        'psalm-external-mutation-free'          ,
        'psalm-if-this-is'                      ,
        'psalm-ignore-falsable-return'          ,
        'psalm-ignore-nullable-return'          ,
        'psalm-ignore-var'                      ,
        'psalm-internal'                        ,
        'psalm-import-type'                     ,
        'psalm-immutable'                       ,
        'psalm-mutation-free'                   ,
        'psalm-param-out'                       ,
        'psalm-pure'                            ,
        'psalm-readonly'                        ,
        'psalm-readonly-allow-private-mutation' ,
        'psalm-require-extends'                 ,
        'psalm-require-implements'              ,
        'psalm-return'                          ,
        'psalm-seal-properties'                 ,
        'psalm-suppress'                        ,
        'psalm-this-out'                        ,
        'psalm-trace'                           ,
        'psalm-type'                            ,
    ];
}

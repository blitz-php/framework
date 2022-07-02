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
        $annotationManager->registry['ajaxOnly']       = AjaxOnlyAnnotation::class;
        $annotationManager->registry['range']          = RangeAnnotation::class;
        $annotationManager->registry['requestMapping'] = RequestMappingAnnotation::class;
        $annotationManager->registry['required']       = RequiredAnnotation::class;
    }
}

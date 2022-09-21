# Blitz PHP / Annotations

[![Latest Version](https://img.shields.io/packagist/v/blitz-php/annotations.svg?style=flat-square)](https://packagist.org/packages/blitz-php/annotations)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Quality Score](https://img.shields.io/scrutinizer/g/blitz-php/annotations.svg?style=flat-square)](https://scrutinizer-ci.com/g/blitz-php/annotations)

[![Coding Standards](https://github.com/blitz-php/annotations/actions/workflows/test-annotationss.yml/badge.svg)](https://github.com/blitz-php/annotations/actions/workflows/test-annotationss.yml)
[![PHPStan Static Analysis](https://github.com/blitz-php/annotations/actions/workflows/test-phpstan.yml/badge.svg)](https://github.com/blitz-php/annotations/actions/workflows/test-phpstan.yml)
[![PHPStan level](https://img.shields.io/badge/PHPStan-max%20level-brightgreen)](phpstan.neon.dist)
[![Coverage Status](https://coveralls.io/repos/github/blitz-php/annotations/badge.svg?branch=develop)](https://coveralls.io/github/blitz-php/annotations?branch=develop)
[![Total Downloads](http://poser.pugx.org/blitz-php/annotations/downloads)](https://packagist.org/packages/blitz-php/annotations)

**blitz-php/annotations** est un lecteur d'annotations et d'attributs pour [PHP] 7.4+. Bien qu'étant principalement créé pour le framework [BlitzPHP][BlitzPHP], cette bibliothèque est conçu de façon à pouvoir s'intégrée aisement dans tout type de projet PHP. Basée sur [mindplay/annotations](mindplay), elle fournit un lecteur simple, rapide et léger des annotations pour votre projet.

## 📦 Installation & utilisation Basique

Ce projet requiert [PHP] 7.4+. La méthode d'installation recommandée est via [Composer]. Exécutez simplement :

```bash
$ composer require blitz*_*php/annotations
```

Disons que vous travaillez sur quelques projets et que vous avez besoin d'un support d'annotations pour chacun. Avec cette bibliothèque, nous facilitons votre travail, tout ce dont vous avez besoin est une classe annotée et la classe `BlitzPHP\Annotations\AnnotationReader` pour trouver des annotations ou des attributs.

**Pour en savoir plus sur l'utilisation de cette bibliothèque, essayez de parcourir le répertoire `tests` et découvrez comment intégrer cette bibliothèque dans votre projet.**

## Lecture des annotations

Considérez la classe suivante avec quelques annotations docblock :

```php
<?php
/**
 * @RequestMapping(["post", "get"])
 * @AjaxOnly
 */
class FooController
{
    /**
     * @required
     */
    protected $repository;

    /**
     * @RequestMapping(["get"])
     */
    public function index(){}
}
```

Utilisons la classe `BlitzPHP\Annotations\AnnotationReader` pour lire les annotations des classes,
propriétés et méthodes. Ainsi:

```php
use BlitzPHP\Annotations\AnnotationReader;

$annotations = AnnotationReader::fromClass('FooController');
/*
[
    BlitzPHP\Annotations\Http\RequestMappingAnnotation::class,
    BlitzPHP\Annotations\Http\AjaxOnlyAnnotation::class,
]
*/
```

The same applies to class properties...

```php
use BlitzPHP\Annotations\AnnotationReader;

$annotations = AnnotationReader::fromProperty('FooController', 'repository');
/*
[
    BlitzPHP\Annotations\Validation\RequiredAnnotation::class,
]
*/
```

methodes...

```php
use BlitzPHP\Annotations\AnnotationReader;

$annotations = AnnotationReader::fromMethod('FooController', 'index');
/*
[
    BlitzPHP\Annotations\Http\RequestMappingAnnotation::class,
]
*/
```

## 📓 Documentation

Cette bibliothèque fournie juste des annotations prête à l'emploi compatibles avec les annotations [mindplay/annotations](mindplay).
Parcourez les tests pour voir les exemples d'utilisations des annotations mises à disposition par Blitz.

## ⏫ Mise à jour

Des informations sur la mise à niveau vers des versions plus récentes de cette bibliothèque peuvent être trouvées dans [UPGRADE].

## 🏷️ Journal des modifications

[SemVer](http://semver.org/) est suivi de près. Les versions mineures et les correctifs ne doivent pas introduire de modifications majeures dans la base de code ; Voir [CHANGELOG] pour plus d'informations sur ce qui a changé récemment.

Toutes les classes ou méthodes marquées `@internal` ne sont pas destinées à être utilisées en dehors de cette bibliothèque et sont sujettes à des modifications avec rupture à tout moment, veuillez donc éviter de les utiliser.

## 🛠️ Maintenance & Assistance

Lorsqu'une nouvelle version **majeure** est publiée (`1.0`, `2.0`, etc.), la précédente (`0.19.x`) recevra des corrections de bogues pendant _au moins_ 3 mois et des mises à jour de sécurité pendant 6 mois après cela nouvelle version sort.

(Cette politique peut changer à l'avenir et des exceptions peuvent être faites au cas par cas.)

## 👷‍♀️ Contribuer

Pour signaler une faille de sécurité, veuillez utiliser [Blitz Security](https://security.blitz-php.com). Nous coordonnerons le correctif et validerons éventuellement la solution dans ce projet.

Les contributions à cette bibliothèque sont **bienvenues**, en particulier celles qui :

- Améliorer la convivialité ou la flexibilité sans compromettre notre capacité à adhérer à ???.
- Optimiser les performances
- Résoudre les problèmes liés au respect de ???.
- ???.

Veuillez consulter [CONTRIBUTING] pour plus de détails.

## 🧪 Test
```bash
$ composer test
```

Cela permettra aux tests blitz-php/annotations de fonctionner avec la version PHP 7.4 ou supérieure.

## 👥 Crédits et remerciements
- [Dimitri Sitchet Tomkeu][@dimtrovich]
- [Tous les contributeurs][]

## 📄 Licence

**blitz-php/annotations** est sous licence MIT. Voir le fichier [`LICENSE`](LICENSE) pour plus de détails.

## 🏛️ Gouvernance

Ce projet est principalement maintenu par [Dimitri Sitchet Tomkeu][@dimtrovich]. Les membres de l'équipe de [Blitz PHP Lap][] peuvent occasionnellement participer à certaines de ces tâches.
## 🏛️ Governance

This project is primarily maintained by [Divine Niiquaye Ibok][dimtrovich]. Members of the [Biurad Lap][] Leadership Team may occasionally assist with some of these duties.

## 🗺️ Qui l'utilise ?

Vous êtes libre d'utiliser ce package comme vous le souhaitez. Découvrez les autres choses intéressantes que les gens font avec `blitz-php/annotations` : <https://packagist.org/packages/blitz-php/annotations/dependents>

[PHP]: https://php.net
[Composer]: https://getcomposer.org
[BlitzPHP]: https://github.com/blitz-php/framework
[docs]: https://docs.biurad.com/php-annotations
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: ./.github/CONTRIBUTING.md
[Tous les contributeurs]: https://github.com/blitz-php/annotations/contributors
[Blitz PHP Lap]: https://github.com/orgs/blitz-php/people
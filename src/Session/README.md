# Blitz PHP / Session

[![Latest Version](https://img.shields.io/packagist/v/blitz-php/session.svg?style=flat-square)](https://packagist.org/packages/blitz-php/session)
[![Software License](https://img.shields.io/badge/License-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Quality Score](https://img.shields.io/scrutinizer/g/blitz-php/session.svg?style=flat-square)](https://scrutinizer-ci.com/g/blitz-php/session)
[![Build Status](https://scrutinizer-ci.com/g/blitz-php/session/badges/build.png?b=main)](https://scrutinizer-ci.com/g/blitz-php/session/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/blitz-php/session/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level%204-brightgreen)](phpstan.neon.dist)
[![Total Downloads](http://poser.pugx.org/blitz-php/session/downloads)](https://packagist.org/packages/blitz-php/session)

**blitz-php/session** fournit un localisateur de service session pour s'interfacer avec plusieurs backends de mise en session à l'aide d'une interface simple à utiliser.
Compatible avec le [PSR-16][psr_16] (`psr/simple-session`), Elle prend en charge plusieurs système de mise en session à l'instar de
- session par fichier
- APC
- Memsession
- Redis
- Winsession
- Xsession

## 📦 Installation

Ce projet requiert [PHP] 7.3+. La méthode d'installation recommandée est via [Composer]. Exécutez simplement :

```bash
$ composer require blitz-php/session
```

## Utilisation

```php
<?php
use BlitzPHP\session\session;

$session = new session([
	'handler' => 'redis',
	'fallback_handler' => 'file'
]);


// Set session key
$session->set($key, $value)

// Get session key
$value = $session->get($key)
```

## 📓 Documentation

Bien qu'étant totalement autonome et peut être intégré dans n'importe quel projet, cette bibliothèque a été conçu pour le framework [BlitzPHP]. De ce fait, vous trouverez toute la documentation nécessaire sur la [documentation officielle de BlitzPHP](docs).

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

Cela permettra aux tests blitz-php/session de fonctionner avec la version PHP 7.3 ou supérieure.

## 👥 Crédits et remerciements
- [Dimitri Sitchet Tomkeu][@dimtrovich]
- [Tous les contributeurs][]

## 📄 Licence

**blitz-php/session** est sous licence MIT. Voir le fichier [`LICENSE`](LICENSE) pour plus de détails.

## 🏛️ Gouvernance

Ce projet est principalement maintenu par [Dimitri Sitchet Tomkeu][@dimtrovich]. Les membres de l'équipe de [Blitz PHP Lap][] peuvent occasionnellement participer à certaines de ces tâches.

## 🗺️ Qui l'utilise ?

Vous êtes libre d'utiliser ce package comme vous le souhaitez. Découvrez les autres choses intéressantes que les gens font avec `blitz-php/session` : <https://packagist.org/packages/blitz-php/session/dependents>

[@dimtrovich]: https://github.com/dimtrovich
[PHP]: https://php.net
[psr_16]: https://www.php-fig.org/psr/psr-16/
[Composer]: https://getcomposer.org
[BlitzPHP]: https://github.com/blitz-php/framework
[docs]:  https://github.com/blitz-php/framework
[UPGRADE]: UPGRADE-1.x.md
[CHANGELOG]: CHANGELOG-0.x.md
[CONTRIBUTING]: https://github.com/blitz-php/framework/blob/main/.github/CONTRIBUTING.md
[Tous les contributeurs]: https://github.com/blitz-php/session/contributors
[Blitz PHP Lap]: https://github.com/orgs/blitz-php/people

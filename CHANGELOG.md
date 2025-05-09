# Journal des modifications

Toutes les modifications notables apportées à `:package_name` seront documentées dans ce fichier.

## 0.12.1 - 2025-05-09

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Fixed Bugs

* fix(Parser): rendu de vue de fichier non-php by @dimtrovich in https://github.com/blitz-php/framework/pull/68
* fix(Session): par defaut, utiliser du gestionnaire `Array` lors des tests by @dimtrovich in https://github.com/blitz-php/framework/pull/70

#### Enhancements

* feat: possibilité pour un collector de definir son fichier de vue by @dimtrovich in https://github.com/blitz-php/framework/pull/67
* feat: ajout du middleware CSP by @dimtrovich in https://github.com/blitz-php/framework/pull/69

#### Others

* patch: suppression des espaces dans le nom des logs by @dimtrovich in https://github.com/blitz-php/framework/pull/71

**Full Changelog**: https://github.com/blitz-php/framework/compare/0.12.0...0.12.1

## 0.12.0 - 2025-03-20

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Fixed Bugs

* fix: bug du systeme de validation lorsque l'optimisation est activé by @dimtrovich in https://github.com/blitz-php/framework/pull/47
* fix: modification d'une clé commenté du fichier .env by @dimtrovich in https://github.com/blitz-php/framework/pull/53
* fix: concaténation de variable imbriqué dans le fichier .env by @dimtrovich in https://github.com/blitz-php/framework/pull/54
* fix: problème de décryptage d'une chaine déjà décodée en base64 by @dimtrovich in https://github.com/blitz-php/framework/pull/57
* fix url path by @dimtrovich in https://github.com/blitz-php/framework/pull/60

#### New Features

* feat: possibilité de definir plusieurs hotes pour une route by @dimtrovich in https://github.com/blitz-php/framework/pull/29
* feat: gestion des pages d'erreur en production by @dimtrovich in https://github.com/blitz-php/framework/pull/30
* feat: ajout de la commande `klinge optimize` by @dimtrovich in https://github.com/blitz-php/framework/pull/41
* feat: mise en place du service de hashing de mot de passe by @dimtrovich in https://github.com/blitz-php/framework/pull/51
* feat: ajout du middleware de protection CSRF by @dimtrovich in https://github.com/blitz-php/framework/pull/58

#### Enhancements

* feat: ajout de l'option `namespace` à la commande `publish` by @dimtrovich in https://github.com/blitz-php/framework/pull/34
* feat: possibilité de définir le type de reponse d'un ResourceController via une proprieté by @dimtrovich in https://github.com/blitz-php/framework/pull/45
* refactor: redesign de la commande `route:list` by @dimtrovich in https://github.com/blitz-php/framework/pull/50
* chore: refactorisation du gestionnaire d'exception by @dimtrovich in https://github.com/blitz-php/framework/pull/59
* patch: amélioration de la commande `translations:find` by @dimtrovich in https://github.com/blitz-php/framework/pull/39
* feat : paramètres opcache supplémentaires dans la vérification de php.ini by @dimtrovich in https://github.com/blitz-php/framework/pull/64

#### Others (Only for checking. Remove this category)

* patch: retrait des balises `script` by @dimtrovich in https://github.com/blitz-php/framework/pull/24
* refactor: suppression de la vérification inutile de is_countable() dans RouteCollection::getMethodParams() by @dimtrovich in https://github.com/blitz-php/framework/pull/25
* patch: definition de drapeau `use_supported_locales_only` de la RouteCollection depuis la config 'routing' by @dimtrovich in https://github.com/blitz-php/framework/pull/26
* Mise a jour des regles de codage by @dimtrovich in https://github.com/blitz-php/framework/pull/27
* patch: [hack] possibilite d'executer une commande CLI a partir du web by @dimtrovich in https://github.com/blitz-php/framework/pull/28
* fix: contrôle des doubles exécutions de la découverte automatique des Registrars by @dimtrovich in https://github.com/blitz-php/framework/pull/31
* feat: ajout de la commande phpini:check by @dimtrovich in https://github.com/blitz-php/framework/pull/32
* feat: ajout de la commande translations:find by @dimtrovich in https://github.com/blitz-php/framework/pull/33
* Fix: Bug lors du chargement des registrars by @dimtrovich in https://github.com/blitz-php/framework/pull/35
* patch: ajout de la methode `to` aux classes `Mailable` by @dimtrovich in https://github.com/blitz-php/framework/pull/37
* fix: correction du service de traduction pour prendre en charge la langue par défaut de l'app by @dimtrovich in https://github.com/blitz-php/framework/pull/38
* refactor: refactorisation des comandes by @dimtrovich in https://github.com/blitz-php/framework/pull/40
* chore: update de `Kint` vers la v6.0 by @dimtrovich in https://github.com/blitz-php/framework/pull/42
* fix: correction du formattage XML via XmlFormatter by @dimtrovich in https://github.com/blitz-php/framework/pull/44
* test: ajout des test des classes de Formattage by @dimtrovich in https://github.com/blitz-php/framework/pull/46
* fix: multiples corrections by @dimtrovich in https://github.com/blitz-php/framework/pull/49
* style: organisation des éléments du service by @dimtrovich in https://github.com/blitz-php/framework/pull/52
* fix: contraint whoops a utiliser le code de l'exception comme code HTTP by @dimtrovich in https://github.com/blitz-php/framework/pull/55
* patch: conversion automatique de la sortie binaire du chiffrement by @dimtrovich in https://github.com/blitz-php/framework/pull/56
* Fix styling by @dimtrovich in https://github.com/blitz-php/framework/pull/61
* test: skip failled test on scrutinizer by @dimtrovich in https://github.com/blitz-php/framework/pull/62
* test: fix test for scrutinizer by @dimtrovich in https://github.com/blitz-php/framework/pull/63
* chore: refactorisation de la debug bar by @dimtrovich in https://github.com/blitz-php/framework/pull/65
* fix test check phpini opcache by @dimtrovich in https://github.com/blitz-php/framework/pull/66

**Full Changelog**: https://github.com/blitz-php/framework/compare/0.11.3...0.12.0

## 0.11.2 - 2024-09-04

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

* style: application de rector pour un code plus propre by @dimtrovich in https://github.com/blitz-php/framework/pull/16
* feat: commande make:component by @dimtrovich in https://github.com/blitz-php/framework/pull/17
* test: Ajout de test pour les commandes klinge by @dimtrovich in https://github.com/blitz-php/framework/pull/18
* chore: Amélioration du système d'évènement by @dimtrovich in https://github.com/blitz-php/framework/pull/19

**Full Changelog**: https://github.com/blitz-php/framework/compare/0.11.1...0.11.2

## 0.11.1 - 2024-06-05

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Others (Only for checking. Remove this category)

* Devs by @dimtrovich in https://github.com/blitz-php/framework/pull/15

**Full Changelog**: https://github.com/blitz-php/framework/compare/0.11.0...0.11.1

## 0.11.0 - 2024-05-22

<!-- Release notes generated using configuration in .github/release.yml at main -->
### What's Changed

#### Others (Only for checking. Remove this category)

* feat: ajout de composants de vue by @dimtrovich in https://github.com/blitz-php/framework/pull/9
* sa: fix phpstan analysis by @dimtrovich in https://github.com/blitz-php/framework/pull/10
* amélioration des tests + refactor de quelques commandes by @dimtrovich in https://github.com/blitz-php/framework/pull/11
* fix: correction de quelques typs by @dimtrovich in https://github.com/blitz-php/framework/pull/12
* patch: mise a jour en fonction des interfaces du contracts by @dimtrovich in https://github.com/blitz-php/framework/pull/13
* Fix: analyse statique (phpstan) et code style by @dimtrovich in https://github.com/blitz-php/framework/pull/14

### New Contributors

* @dimtrovich made their first contribution in https://github.com/blitz-php/framework/pull/9

**Full Changelog**: https://github.com/blitz-php/framework/compare/0.10.0...0.11.0

## 0.10.0 - 2023-12-28

<!-- Release notes generated using configuration in .github/release.yml at main -->
**Full Changelog**: https://github.com/blitz-php/framework/compare/0.9.0...0.10.0

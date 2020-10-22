# Akeneo Utils - Click And Mortar

Akeneo Utils is a bundle to add some utils features to Akeneo project.

Made by :heart: by C&M

## Versions

| **Bundle version**  | **Akeneo version** |
| ------------- | ------------- |
| v1.2.*  | v4.0.*  |
| v1.1.*  | v3.2.*  |
| v1.0.*  | v2.1.*  |

## Installation

Add package with composer:
```bash
composer require clickandmortar/akeneo-utils-bundle "<version-wanted>.*"
```

Add bundle in your **`config/bundles.php`** file:
```php
return [
    ClickAndMortar\AkeneoUtilsBundle\ClickAndMortarAkeneoUtilsBundle::class => ['all' => true]
    ...
];
```

## Commands utils

* `candm:akeneo-utils:clear-archives`: To remove old archives directories and avoid large disk usage.
* `candm:akeneo-utils:list-unused-options`: To list unused attribute options for given family and attribute code
* `candm:installer:assets` : To install assets without Oro translation dump

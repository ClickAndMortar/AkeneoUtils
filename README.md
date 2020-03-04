# Akeneo Utils - Click And Mortar

Akeneo Utils is a bundle to add some utils features to Akeneo project.

Made by :heart: by C&M

## Versions

| **Bundle version**  | **Akeneo version** |
| ------------- | ------------- |
| v1.1.*  | v3.2.*  |
| v1.0.*  | v2.1.*  |

## Installation

Add package with composer:
```bash
composer require clickandmortar/akeneo-utils-bundle "^1.0"
```

Add bundle in your **`app/AppKernel.php`** file:
```php
$bundles = array(
            ...
            new ClickAndMortar\AkeneoUtilsBundle\AkeneoUtilsBundle(),
        );
```

## Commands utils

* `candm:akeneo-utils:clear-archives`: To remove old archives directories and avoid large disk usage.
* `candm:akeneo-utils:list-unused-options`: To list unused attribute options for given family and attribute code

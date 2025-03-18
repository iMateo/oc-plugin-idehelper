# IDE Helpers

This plugin adds [icstudio/ide-helpers](https://github.com/icstudio-ua/oc-idehelper-plugin) package to October 2.x and 3.x for better IDE support.

## Installation

* `git clone` into */plugins/icstudio/idehelper*
* `composer install`
* `php artisan ide-helper:generate`

## Configuration

No configuration is necessary, but for use of `php artisan ide-helper:models` you may need to edit `/config/config.php` to include the model files you wish to be scanned.

## TODO

* Auto clear-compiled/generate/optimize after update

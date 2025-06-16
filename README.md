# CLI JSON Lint

This small package adds a more advanced CLI interface over industry-standard [Seldaek's JSON Lint](https://github.com/Seldaek/jsonlint).
The CLI interface is compatible with Symfony's YAML lint commands and integrates nicely with workflows using both JSON
and YAML commands.

## Installation
### With Symfony
Install package with `composer req --dev owlcorp/cli-json-lint`. The command will be automatically available in your
application's console:
```
% composer req --dev owlcorp/cli-json-lint
% bin/console list lint | grep json
  lint:json          Lint JSON file(s) and report errors
% bin/console lint:json --help
```

If you're not using [Symfony Flex](https://symfony.com/doc/8.0/setup/flex.html), you need to add the following to your
`config/bundles.php`:
```php
<?php

return [
    //...
    OwlCorp\CliJsonLint\CliJsonLintBundle::class => ['dev' => true, 'test' => true],
];
```

### Without Symfony
Install package with `composer req --dev owlcorp/cli-json-lint`. The command will be available to use via
`vendor/bin/json-lint`:
application's console:
```
% composer req --dev owlcorp/cli-json-lint
% vendor/bin/json-lint --help
Description:
  Lint JSON file(s) and report errors

Usage:
  lint:json [options] [--] <source>...
# ...
```


## Usage
The command is available either via `bin/console lint:json` (if using Symfony) or `vendor/bin/json-lint`. Use `--help`
to get information about all options. Examples below work with or without Symfony.

**Cheatsheet:**
- Lint `./vendor` with subdirectories: `vendor/bin/json-lint config`
- Lint only files within `./`: `vendor/bin/json-lint --d 0 .`
- Show detailed error information: `vendor/bin/json-lint -v vendor`
- Show all files parsed: `vendor/bin/json-lint -vv vendor`
- You can also use wildcards [using glob syntax](https://www.php.net/manual/en/function.glob.php): `vendor/bin/json-lint *end*`


## Practical example
This package was created to unify linting all configs on production. This is usually achieved by adding a script to 
`composer.json` like so:

```json
{
    //...
    "scripts": {
        "sc": "bin/console",
        "lint": [
            "@composer validate --strict",
            "@sc lint:yaml config/",
            "@sc lint:yaml config_runtime/",
            "@sc lint:json config_runtime/"
        ]
  }
}
```

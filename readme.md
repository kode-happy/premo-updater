# Premo Updater

A package to easily implement automatic update checks with the [Premo WordPress plugin](https://kodehappy.com/premo) into your own plugin.


## Installation

```sh
$ composer require kodehappy/premo-updater
```

**NOTE**: It's *highly* recommended that you namespace the `PremoUpdater` class for your plugin using something like [PHP-Prefixer](https://php-prefixer.com) to avoid any conflict with other plugins. Alternatively you can simply copy the contents of [premo-updater.php](premo-updater.php) to your plugin and update the class name manually.


## Usage

```php
require_once('vendor/autoload.php');

new PremoUpdater(array(
  'package' => 'your-package-name',
  'url'     => 'https://yourwebsite.com/packages',
  'version' => YOUR_PLUGIN_VERSION,
  'key'     => YOUR_USERS_API_KEY
));
```


## License

MIT. See the [license.md file](license.md) for more info.
Pli
===

> Pli is a library and set of conventions to bootstrap the integration of the Symfony
[Console](https://github.com/symfony/Console), [DependencyInjection](https://github.com/symfony/DependencyInjection)
and [Config](https://github.com/symfony/Config) components.

The Symfony components are incredible powerful, but the price for this flexibility is quite a bit of bootstrapping code
to set the components up. Especially when integrating Console, DependencyInjection and Config a developer has to
copy and adapt a lot of code from the docs. **Pli** uses some assumptions (such as config files in Yaml format) to
reduce the amount of code required to bootstrap a simple console application.

Developed by [Florian Eckerstorfer](https://florian.ec) in Vienna, Europe.


Installation
------------

You can install Pli using [Composer](http://getcomposer.org).

```shell
$ composer require cocur/pli:@stable
```


Usage
-----

Bootstrapping a console application with Pli is a three-step process.

1. **Load configuration:** Load and parse one or more Yaml config files.
2. **Build container:** Create a container and invokes the *extension* to initialize it
3. **Create application:** Creates and application and adds all command tagged with `command` to it. If a command
implements `ContainerAwareInterface` the container is set on the command.

First we need our main file with the initialization of `Pli`. You also need a configuration and a extension class and
Pli is very similar to Symfony in this regard. However, the Pli-version of an extension has far less features.

```php
// console.php

use Cocur\Pli\Pli;

$pli = new Pli(__DIR__.'/config');
$config = $pli->loadConfiguration(new AcmeConfiguration(), ['config.yml']);
$container = $pli->buildContainer(new AcmeExtension(), $config);
$application = $pli->getApplication($container);
$application->run();
```

```
// src/AcmeConfiguration.php

<?php

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class BranConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('acme');

        return $treeBuilder;
    }
}
```

Pli also needs an extension that is used to build the container. You can set parameters, dynamically create
service definitions and so on. If you want to store your service configuration in a Yaml file you can use the
 `configDirectory` property to retrieve the path to the config directory.

```php
// src/AcmeExtension.php
<?php

use Cocur\Pli\Container\ExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BranExtension implements ExtensionInterface
{
    private $configDirectory;

    public function buildContainer(ContainerBuilder $container, array $config = [])
    {
        $loader = new YamlFileLoader($container, new FileLocator($this->configDirectory));
        $loader->load('services.yml');
    }

    public function setConfigDirectory($configDirectory)
    {
        $this->configDirectory = $configDirectory;
    }
}
```


Changelog
---------

*No public release yet*


License
-------
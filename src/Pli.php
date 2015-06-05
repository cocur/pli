<?php

/**
 * This file is part of cocur/pli.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cocur\Pli;

use Cocur\Pli\Container\ExtensionInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Pli
 *
 * @package   Pli
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2014-2015 Florian Eckerstorfer
 */
class Pli
{
    /**
     * @var string[]
     */
    private $configDirectories;

    /**
     * @param string|string[] $configDirectories
     */
    public function __construct($configDirectories)
    {
        if (is_string($configDirectories)) {
            $configDirectories = [$configDirectories];
        }
        $this->configDirectories = $configDirectories;
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param string[]               $configFiles
     *
     * @return array
     */
    public function loadConfiguration(ConfigurationInterface $configuration, array $configFiles = [])
    {
        $rawConfig = [];
        foreach ($configFiles as $configFile) {
            if (!file_exists($configFile)) {
                $configFile = $this->getConfigFilename($configFile);
            }
            if ($configFile) {
                $rawConfig[] = Yaml::parse(file_get_contents($configFile));
            }
        }

        return (new Processor())->processConfiguration($configuration, $rawConfig);
    }

    /**
     * @param ExtensionInterface|null $extension
     * @param array                   $config
     * @param array                   $parameters
     * @param CompilerPassInterface   $compilerPasses
     *
     * @return ContainerBuilder
     */
    public function buildContainer(
        ExtensionInterface $extension = null,
        array $config = [],
        array $parameters = [],
        array $compilerPasses = []
    ) {
        $container = new ContainerBuilder();
        if ($extension !== null) {
            $extension->setConfigDirectories($this->configDirectories);
            $extension->buildContainer($container, $config);
        }
        foreach ($parameters as $key => $value) {
            $container->setParameter($key, $value);
        }
        foreach ($compilerPasses as $compilerPass) {
            $container->addCompilerPass($compilerPass);
        }
        $container->compile();

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return Application
     */
    public function getApplication(ContainerBuilder $container)
    {
        return $this->addCommands(new Application(), $container);
    }

    /**
     * @param Application      $application
     * @param ContainerBuilder $container
     *
     * @return Application
     */
    protected function addCommands(Application $application, ContainerBuilder $container)
    {
        $commands = array_keys($container->findTaggedServiceIds('command'));
        foreach ($commands as $id) {
            /** @var \Symfony\Component\Console\Command\Command|ContainerAwareInterface $command */
            $command = $container->get($id);
            $application->add($command);
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        return $application;
    }

    /**
     * @param string $configFile
     *
     * @return null|string
     */
    protected function getConfigFilename($configFile)
    {
        foreach ($this->configDirectories as $configDirectory) {
            $configPathname = sprintf('%s/%s', $configDirectory, $configFile);
            if (file_exists($configPathname)) {
                return $configPathname;
            }
        }

        return null;
    }
}

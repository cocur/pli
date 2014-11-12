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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Pli
 *
 * @package   Pli
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2014 Florian Eckerstorfer
 */
class Pli
{
    /** @var string */
    private $configDirectory;

    public function __construct($configDirectory)
    {
        $this->configDirectory = $configDirectory;
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
            if (false === file_exists($configFile)) {
                $configFile = sprintf('%s/%s', $this->configDirectory, $configFile);
            }
            $rawConfig[] = Yaml::parse(file_get_contents($configFile));
        }

        return (new Processor())->processConfiguration($configuration, $rawConfig);
    }

    /**
     * @param ExtensionInterface|null $extension
     * @param array                   $config
     *
     * @return ContainerBuilder
     */
    public function buildContainer(ExtensionInterface $extension = null, array $config = [])
    {
        $container = new ContainerBuilder();
        if ($extension !== null) {
            $extension->setConfigDirectory($this->configDirectory);
            $extension->buildContainer($container, $config);
        }

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return Application
     */
    public function getApplication(ContainerBuilder $container)
    {
        $application = new Application();

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
}

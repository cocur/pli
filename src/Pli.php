<?php

namespace Cocur\Pli;

use Cocur\Pli\Container\ExtensionInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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
            $configFile = file_exists($configFile) ? $configFile : sprintf('%s/%s', $this->configDirectory, $configFile);
            $rawConfig[] = Yaml::parse(file_get_contents($configFile));
        }

        return (new Processor())->processConfiguration($configuration, $rawConfig);
    }

    /**
     * @param ExtensionInterface $extension
     * @param array              $config
     *
     * @return ContainerBuilder
     */
    public function buildContainer(ExtensionInterface $extension = null, array $config = [])
    {
        $container = new ContainerBuilder();
        if ($extension) {
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

        $commands = $container->findTaggedServiceIds('command');
        foreach ($commands as $command) {
            $application->add($command);
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        return $application;
    }
}

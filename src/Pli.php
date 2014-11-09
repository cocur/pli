<?php

namespace Pli;

use Cocur\Pli\ExtensionInterface;
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
            $rawConfig[] = Yaml::parse($configFile);
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
            $extension->buildContainer($container, $config);
        }

        return $container;
    }

    /**
     * @param string             $commandDirectory
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function getApplication($commandDirectory, ContainerInterface $container = null)
    {
        $application = new Application();

        $finder = (new Finder())->files()->in($commandDirectory)->name('*Command.php');
        /** @var SplFileInfo $commandFile */
        foreach ($finder as $commandFile) {
            $className = $commandFile->getBasename('.php');
            $command = new $className();
            $application->add($command);
            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($container);
            }
        }

        return $application;
    }
}

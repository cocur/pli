<?php

namespace Cocur\Pli\Container;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * ConfigurationInterface
 *
 * @package   Cocur\Pli
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2014-2015 Florian Eckerstorfer
 */
interface ExtensionInterface
{
    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     */
    public function buildContainer(ContainerBuilder $container, array $config = []);

    /**
     * @param string[] $configDirectories
     *
     * @return void
     */
    public function setConfigDirectories(array $configDirectories);
}

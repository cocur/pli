<?php

namespace Cocur\Pli;

use \Mockery as m;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

/**
 * PliTest
 *
 * @package   Cocur\Pli
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2014 Florian Eckerstorfer
 * @group     unit
 */
class PliTest extends \PHPUnit_Framework_TestCase
{
    /** @var \org\bovigo\vfs\vfsStreamDirectory */
    private $configDir;

    /** @var Pli */
    private $pli;

    public function setUp()
    {
        $this->configDir = vfsStream::setup('config');
        $this->pli = new Pli(vfsStream::url('config'));
    }

    /**
     * @test
     * @covers Cocur\Pli\Pli::loadConfiguration()
     */
    public function loadConfigurationShouldLoadConfiguration()
    {
        $rootNode = m::mock('Symfony\Component\Config\Definition\NodeInterface');
        $rootNode->shouldReceive('normalize')->once();
        $rootNode->shouldReceive('merge')->once();
        $rootNode->shouldReceive('finalize')->once()->andReturn(['foo' => 'bar']);

        $treeBuilder = m::mock('Symfony\Component\Config\Definition\Builder\TreeBuilder');
        $treeBuilder->shouldReceive('buildTree')->once()->andReturn($rootNode);

        /** @var \Symfony\Component\Config\Definition\ConfigurationInterface|\Mockery\MockInterface $configuration */
        $configuration = m::mock('Symfony\Component\Config\Definition\ConfigurationInterface');
        $configuration->shouldReceive('getConfigTreeBuilder')->once()->andReturn($treeBuilder);

        $configFile = new vfsStreamFile('config.yml');
        $configFile->setContent('foo: bar');

        $this->configDir->addChild($configFile);

        $config = $this->pli->loadConfiguration($configuration, ['config.yml']);

        $this->assertEquals('bar', $config['foo']);
    }

    /**
     * @test
     * @covers Cocur\Pli\Pli::__construct()
     * @covers Cocur\Pli\Pli::buildContainer()
     */
    public function buildContainerShouldCreateContainerAndInvokeExtension()
    {
        /** @var \Cocur\Pli\Container\ExtensionInterface|\Mockery\MockInterface $extension */
        $extension = m::mock('Cocur\Pli\Container\ExtensionInterface');
        $extension->shouldReceive('setConfigDirectory')->with(vfsStream::url('config'))->once();
        $extension->shouldReceive('buildContainer')->once();

        $container = $this->pli->buildContainer($extension, ['foo' => 'bar']);

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\ContainerBuilder', $container);
    }

    /**
     * @test
     * @covers Cocur\Pli\Pli::getApplication()
     */
    public function getApplicationShouldCreateApplicationAndAddCommands()
    {
        $command = m::mock('Symfony\Component\Console\Command\Command, Symfony\Component\DependencyInjection\ContainerAwareInterface');
        $command->shouldReceive('setApplication');
        $command->shouldReceive('isEnabled');

        /** @var \Symfony\Component\DependencyInjection\ContainerBuilder|\Mockery\MockInterface $container */
        $container = m::mock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $container->shouldReceive('findTaggedServiceIds')->with('command')->once()->andReturn(['cmd1' => null]);

        $container->shouldReceive('get')->with('cmd1')->once()->andReturn($command);
        $command->shouldReceive('setContainer')->with($container);

        $application = $this->pli->getApplication($container);

        $this->assertInstanceOf('Symfony\Component\Console\Application', $application);
    }
}

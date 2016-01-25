<?php

namespace Jackalope\Tools\Console;

use Jackalope\Tools\Console\Helper\DoctrineDbalHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class RunnerTest extends \PHPUnit_Framework_TestCase
{

    public function testDbalGeneratedForInitCommand()
    {
        $helpers = Runner::createHelperSetFromConfig(array('binary', 'jackalope:init:dbal'), array(
            'dbal' => array(
                'driver' => "pdo_sqlite",
                'host' => '',
                'dbname' => 'phpcr.sqlite',
                'user' => '',
                'password' => '',
                'path' => 'phpcr.sqlite',
            ),
            'phpcr' => array(
            ),
        ));

        $connection = $helpers->get('connection')->getConnection();
        $this->assertInstanceOf('Doctrine\DBAL\Connection', $connection);
    }

    public function testDbalObjectCopiedForInitCommand()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $helpers = Runner::createHelperSetFromConfig(array('binary', 'jackalope:init:dbal'), array(
            'dbal' => $connection,
            'phpcr' => array(),
        ));

        $used = $helpers->get('connection')->getConnection();
        $this->assertSame($connection, $used);
    }

    public function testHelpersCreatedForNormalCommand()
    {
        // Proves that we tried to use the constructed DB.
        $this->setExpectedException('PHPCR\RepositoryException', "no such table: phpcr_workspaces");
        $helpers = Runner::createHelperSetFromConfig(array('binary', 'arbitrary-command'), array(
            'dbal' => array(
                'driver' => "pdo_sqlite",
                'host' => '',
                'dbname' => 'phpcr.sqlite',
                'user' => '',
                'password' => '',
                'path' => 'phpcr.sqlite',
            ),
            'phpcr' => array(
                'username' => 'admin',
                'password' => 'admin',
                'workspace' => "default",
            ),
        ));
    }

    public function testHelpersGeneratedFromObjects()
    {
        $session = $this->getMockBuilder('\PHPCR\SessionInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $helpers = Runner::createHelperSetFromConfig(array('binary', 'arbitrary-command'), array(
            'dbal' => null,
            'phpcr' => $session,
        ));

        $used = $helpers->get('phpcr')->getSession();
        $this->assertSame($session, $used);
    }
}

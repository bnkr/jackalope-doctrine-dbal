<?php
namespace Jackalope\Tools\Console;

/**
 * Contains the inner implementation of a console program, not including the
 * loading of cli-config.php.  This makes it easier to integrate into
 * non-symfony style applications and can be used in the implementation of a
 * cli-config.php.
 *
 * @license http://www.apache.org/licenses Apache License Version 2.0, January 2004
 * @license http://opensource.org/licenses/MIT MIT License
 */
class Runner
{
    /**
     * Run commands using a symfony Console\Application.
     */
    static public function run($helperSet)
    {
        $helperSet = $helperSet ?: new \Symfony\Component\Console\Helper\HelperSet();

        $cli = new \Symfony\Component\Console\Application('Jackalope Command Line Interface', '0.1');
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        $cli->addCommands(array(
            new \PHPCR\Util\Console\Command\NodeDumpCommand(),
            new \PHPCR\Util\Console\Command\NodeMoveCommand(),
            new \PHPCR\Util\Console\Command\NodeRemoveCommand(),
            new \PHPCR\Util\Console\Command\NodeTouchCommand(),

            new \PHPCR\Util\Console\Command\NodeTypeListCommand(),
            new \PHPCR\Util\Console\Command\NodeTypeRegisterCommand(),

            new \PHPCR\Util\Console\Command\WorkspaceCreateCommand(),
            new \PHPCR\Util\Console\Command\WorkspaceDeleteCommand(),
            new \PHPCR\Util\Console\Command\WorkspaceExportCommand(),
            new \PHPCR\Util\Console\Command\WorkspaceImportCommand(),
            new \PHPCR\Util\Console\Command\WorkspaceListCommand(),
            new \PHPCR\Util\Console\Command\WorkspacePurgeCommand(),
            new \PHPCR\Util\Console\Command\WorkspaceQueryCommand(),

            new \Jackalope\Tools\Console\Command\InitDoctrineDbalCommand(),
        ));
        $cli->run();
    }

    /**
     * Create a helper set suitable to run jackalope and phpcr commands
     * factoring for some command-specific cases.
     *
     * This is the implementation of a simple cli-config.php.  The caller can
     * either pass initialised services for phpcr or dbal or configuration to
     * create the services here.
     */
    static public function createHelperSetFromConfig($argv, $params)
    {
        // only create a session if this is not about the server control command.
        if (isset($argv[1]) && ! in_array($argv[1], array('jackalope:init:dbal', 'list', 'help'))) {
            $session = self::createPhpcrSessionFromConfig($params);

            $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
                'dialog' => new \Symfony\Component\Console\Helper\DialogHelper(),
                'phpcr' => new \PHPCR\Util\Console\Helper\PhpcrHelper($session),
                'phpcr_console_dumper' => new \PHPCR\Util\Console\Helper\PhpcrConsoleDumperHelper(),
            ));
        } else if (isset($argv[1]) && $argv[1] == 'jackalope:init:dbal') {
            // special case: the init command needs the db connection, but a
            // session is impossible if the db is not yet initialized
            $dbConn = self::createDbalFromConfig($params);
            $helperSet = new \Symfony\Component\Console\Helper\HelperSet(array(
                'connection' => new \Jackalope\Tools\Console\Helper\DoctrineDbalHelper($dbConn)
            ));
        } else {
            $helperSet = null;
        }

        return $helperSet;
    }

    /**
     * Create an authenticated PHPCR session from some simple configuration.
     */
    static public function createPhpcrSessionFromConfig($params)
    {
        if (is_array($params['phpcr'])) {
            $workspace  = $params['phpcr']['workspace'];
            $user       = $params['phpcr']['username'];
            $pass       = $params['phpcr']['password'];

            $dbConn = self::createDbalFromConfig($params);

            $factory = new \Jackalope\RepositoryFactoryDoctrineDBAL();
            $repository = $factory->getRepository(array('jackalope.doctrine_dbal_connection' => $dbConn));
            $credentials = new \PHPCR\SimpleCredentials($user, $pass);
            $session = $repository->login($credentials, $workspace);
        } else {
            $session = $params['phpcr'];
        }

        return $session;
    }

    /**
     * Create a DBAL connection from configuration parameters.
     */
    static public function createDbalFromConfig($params)
    {
        if (is_array($params['dbal'])) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection(array(
                'driver'    => $params['dbal']['driver'],
                'host'      => $params['dbal']['host'],
                'user'      => $params['dbal']['user'],
                'password'  => $params['dbal']['password'],
                'dbname'    => $params['dbal']['dbname'],
                'path'      => $params['dbal']['path'],
            ));
        } else {
            $conn = $params['dbal'];
        }

        return $conn;
    }
}

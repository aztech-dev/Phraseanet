<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommand;
use Alchemy\Phrasea\Setup\SetupService;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ExecutableFinder;

class Install extends Command
{

    const WELCOME_MESSAGE = "<comment>
                                                      ,-._.-._.-._.-._.-.
                                                      `-.             ,-'
 .----------------------------------------------.       |             |
|                                                |      |             |
|  Hello !                                       |      |             |
|                                                |      |             |
|  You are on your way to install Phraseanet,    |     ,';\".________.-.
|  You will need access to 2 MySQL databases.    |     ;';_'         )]
|                                                |    ;             `-|
|                                                `.    `T-            |
 `----------------------------------------------._ \    |             |
                                                  `-;   |             |
                                                        |..________..-|
                                                       /\/ |________..|
                                                  ,'`./  >,(           |
                                                  \_.-|_/,-/   ii  |   |
                                                   `.\"' `-/  .-\"\"\"||    |
                                                    /`^\"-;   |    ||____|
                                                   /     /   `.__/  | ||
                                                        /           | ||
                                                                    | ||
</comment>";

    /**
     * @var ExecutableFinder
     */
    private $executableFinder;

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->executableFinder = new ExecutableFinder();

        $this
            ->setDescription("Installs Phraseanet")
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin e-mail address', null)
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', null)
            ->addOption('db-driver', null, InputOption::VALUE_OPTIONAL, 'Database driver name', 'mysql')
            ->addOption('db-dsn', null, InputOption::VALUE_OPTIONAL, 'Databox DB server DSN', null)
            ->addOption('ab-dsn', null, InputOption::VALUE_OPTIONAL, 'Application box DB server DSN', null)
            ->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'MySQL server host', 'localhost')
            ->addOption('db-port', null, InputOption::VALUE_OPTIONAL, 'MySQL server port', 3306)
            ->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'MySQL server user', 'phrasea')
            ->addOption('db-password', null, InputOption::VALUE_OPTIONAL, 'MySQL server password', null)
            ->addOption('db-template', null, InputOption::VALUE_OPTIONAL, 'Metadata structure language template (available are fr (french) and en (english))', null)
            ->addOption('databox', null, InputOption::VALUE_OPTIONAL, 'Database name for the DataBox', null)
            ->addOption('appbox', null, InputOption::VALUE_OPTIONAL, 'Database name for the ApplicationBox', null)
            ->addOption('data-path', null, InputOption::VALUE_OPTIONAL, 'Path to data repository', realpath(__DIR__ . '/../../../../../datas'))
            ->addOption('server-name', null, InputOption::VALUE_OPTIONAL, 'Server name')
            ->addOption('indexer', null, InputOption::VALUE_OPTIONAL, 'Path to Phraseanet Indexer', 'auto')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Answer yes to all questions');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        $output->writeln(self::WELCOME_MESSAGE);

        if (!$input->getOption('yes') && !$input->getOption('appbox') && !$input->getOption('ab-dsn')) {
            $continue = $dialog->askConfirmation($output, 'Do you have these two DB handy ? (N/y)', false);

            if (! $continue) {
                $output->writeln("See you later !");

                return 0;
            }
        }

        $appboxInstallCommand = $this->getAppboxInstallCommand($input, $output, $dialog);
        $databoxInstallCommand = $this->getDataboxInstallCommand($input, $output, $appboxInstallCommand, $dialog);
        $template = null;

        if ($databoxInstallCommand) {
            $template = $this->getDataboxTemplate($input, $output, $dialog);
        }

        list($email, $password) = $this->getCredentials($input, $output, $dialog);

        if (!$input->getOption('yes')) {
            $continue = $dialog->askConfirmation($output, "<question>Phraseanet is going to be installed, continue ? (N/y)</question>", false);

            if (!$continue) {
                $output->writeln("See you later !");

                return 0;
            }
        }

        $initializeEnvironmentCommand = new InitializeEnvironmentCommand(
            $email,
            $password,
            $template,
            $this->getDataPath($input, $output, $dialog),
            $this->getServerName($input, $output, $dialog),
            $this->detectBinaries()
        );

        /** @var SetupService $service */
        $service = $this->container['setup.service'];

        $installResult = $service->install($initializeEnvironmentCommand, $appboxInstallCommand, $databoxInstallCommand);

        if (! $installResult->isSuccessful()) {
            $output->writeln(sprintf("<error>Installation failed: %s</error>", $installResult->getReason()));

            return 1;
        }

        if (null !== $this->getApplication()) {
            $command = $this->getApplication()->find('crossdomain:generate');
            $command->run(new ArrayInput(array(
                'command' => 'crossdomain:generate'
            )), $output);
        }

        $output->writeln("<info>Install successful !</info>");
    }

    private function getAppboxInstallCommand(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $info = null;

        if (!$input->getOption('appbox') && ! $input->getOption('ab-dsn')) {
            $output->writeln("\n<info>--- Database credentials ---</info>\n");

            do {
                $hostname = $dialog->ask($output, "DB hostname (localhost) : ", 'localhost');
                $port = $dialog->ask($output, "DB port (3306) : ", 3306);
                $dbUser = $dialog->ask($output, "DB user : ");
                $dbPassword = $dialog->askHiddenResponse($output, "DB password (hidden) : ");
                $abName = $dialog->ask($output, "DB name (phraseanet) : ", 'phraseanet');

                $info = [
                    'host'     => $hostname,
                    'port'     => $port,
                    'user'     => $dbUser,
                    'password' => $dbPassword,
                    'dbname'   => $abName,
                ];
            } while (! $this->testConnection($output, 'Application-Box', $info));
        } else {
            $info = $this->parseConnectionOptions($input, 'appbox', 'ab-dsn');

            if (! $this->testConnection($output, 'Application-Box', $info)) {
                throw new RuntimeException('Invalid application box settings');
            }
        }

        return new InstallCommand($info['host'], $info['port'], $info['user'], $info['password'], $info['dbname'], $info);
    }

    private function getDataboxInstallCommand(
        InputInterface $input,
        OutputInterface $output,
        InstallCommand $applicationBoxInstallCommand,
        DialogHelper $dialog
    ) {
        $databoxInstallCommand = null;

        if (!$input->getOption('databox') && ! $input->getOption('db-dsn')) {
            do {
                $dbName = $dialog->ask($output, 'DataBox name, will not be created if empty : ', null);

                if ($dbName) {
                    $info = [
                        'host' => $applicationBoxInstallCommand->getDatabaseHost(),
                        'port' => $applicationBoxInstallCommand->getDatabasePort(),
                        'user' => $applicationBoxInstallCommand->getDatabaseUser(),
                        'password' => $applicationBoxInstallCommand->getDatabasePassword(),
                        'dbname' => $dbName,
                    ];
                } else {
                    $output->writeln("\n\tNo databox will be created\n");

                    return null;
                }
            } while (! $this->testConnection($output, 'Data-Box', $info));
        } else {
            $info = $this->parseConnectionOptions($input, 'databox', 'db-dsn');

            if (! $this->testConnection($output, 'Data-Box', $info)) {
                throw new RuntimeException('Invalid databox settings');
            }
        }

        return new InstallCommand($info['host'], $info['port'], $info['user'], $info['password'], $info['dbname'], $info);
    }

    private function getDataboxTemplate(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        if (!$input->getOption('databox') && ! $input->getOption('db-dsn')) {
            do {
                $template = $dialog->ask($output,
                    'Choose a language template for metadata structure, available are fr (french) and en (english) (en) : ',
                    'en');
            } while (!in_array($template, ['en', 'fr']));
        }
        else {
            $template = $input->getOption('db-template') ? : 'en';
        }

        $output->writeln("\n\tLanguage selected is <info>'$template'</info>\n");

        return $template;
    }

    private function getCredentials(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $email = $password = null;

        if (!$input->getOption('email') && !$input->getOption('password')) {
            $output->writeln("\n<info>--- Account Informations ---</info>\n");

            do {
                $email = $dialog->ask($output, 'Please provide a valid e-mail address : ');
            } while (!\Swift_Validate::email($email));

            do {
                $password = $dialog->askHiddenResponse($output, 'Please provide a password (hidden, 6 character min) : ');
            } while (strlen($password) < 6);

            $output->writeln("\n\t<info>Email / Password successfully set</info>\n");
        } elseif ($input->getOption('email') && $input->getOption('password')) {
            if (!\Swift_Validate::email($input->getOption('email'))) {
                throw new \RuntimeException('Invalid email addess');
            }
            $email = $input->getOption('email');
            $password = $input->getOption('password');
        } else {
            throw new \RuntimeException('You have to provide both email and password');
        }

        return [$email, $password];
    }

    private function getDataPath(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $dataPath = $input->getOption('data-path');

        if (!$input->getOption('yes')) {
            $continue = $dialog->askConfirmation($output, 'Would you like to change default data-path ? (N/y)', false);

            if ($continue) {
                do {
                    $dataPath = $dialog->ask($output, 'Please provide the data path : ', null);
                } while (!$dataPath || !is_writable($dataPath));
            }
        }

        if (!$dataPath || !is_writable($dataPath)) {
            throw new \RuntimeException(sprintf('Data path `%s` is not writable', $dataPath));
        }

        return $dataPath;
    }

    private function getServerName(InputInterface $input, OutputInterface $output, DialogHelper $dialog)
    {
        $serverName = $input->getOption('server-name');

        if (!$serverName && !$input->getOption('yes')) {
            do {
                $serverName = $dialog->ask($output, 'Please provide the server name : ', null);
            } while (!$serverName);
        }

        if (!$serverName) {
            throw new \RuntimeException('Server name is required');
        }

        return $serverName;
    }

    private function detectBinaries()
    {
        return [
            'php_binary'           => $this->executableFinder->find('php'),
            'pdf2swf_binary'       => $this->executableFinder->find('pdf2swf'),
            'swf_extract_binary'   => $this->executableFinder->find('swfextract'),
            'swf_render_binary'    => $this->executableFinder->find('swfrender'),
            'unoconv_binary'       => $this->executableFinder->find('unoconv'),
            'ffmpeg_binary'        => $this->executableFinder->find('ffmpeg', $this->executableFinder->find('avconv')),
            'ffprobe_binary'       => $this->executableFinder->find('ffprobe', $this->executableFinder->find('avprobe')),
            'mp4box_binary'        => $this->executableFinder->find('MP4Box'),
            'pdftotext_binary'     => $this->executableFinder->find('pdftotext'),
            'ghostscript_binary'   => $this->executableFinder->find('gs'),
        ];
    }

    /**
     * @param OutputInterface $output
     * @param $connectionType
     * @param $info
     * @return mixed
     */
    private function testConnection(OutputInterface $output, $connectionType, $info)
    {
        try {
            $abConn = $this->container['dbal.provider']($info);
            $abConn->connect();

            $output->writeln("\n\t<info>$connectionType : Connection successful !</info>\n");
        } catch (\Exception $e) {
            $output->writeln("\n\t<error>Invalid connection parameters</error>\n");

            return false;
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param $dbArgName
     * @return array
     */
    private function parseConnectionOptions(InputInterface $input, $dbArgName, $useDsnArg = false)
    {
        if (! $useDsnArg || ! $input->getOption($useDsnArg)) {
            $info = [
                'host' => $input->getOption('db-host'),
                'port' => $input->getOption('db-port'),
                'dbname' => $input->getOption($dbArgName),
                'user' => $input->getOption('db-user'),
                'password' => $input->getOption('db-password')
            ];
        } else {
            $dsn = $input->getOption($useDsnArg);

            list($driver, $dsnArgs) = explode(':', $dsn, 2);
            $dsnArgValues = explode(';', $dsnArgs);
            $info = [
                'user' => '',
                'password' => ''
            ];

            foreach ($dsnArgValues as $argValue) {
                list ($name, $value) = explode('=', $argValue, 2);

                $info[$name] = $value;
            }

            $info['driver'] = 'pdo_' . $driver;
            $info['host'] = '';
            $info['port'] = 0;
        }

        return $info;
    }
}

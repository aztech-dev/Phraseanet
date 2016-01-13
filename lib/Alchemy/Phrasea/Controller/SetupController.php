<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Setup\Command\InitializeEnvironmentCommand;
use Alchemy\Phrasea\Setup\Command\InstallCommand;
use Alchemy\Phrasea\Setup\RequirementCollectionInterface;
use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;
use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;
use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;
use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;
use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;
use Alchemy\Phrasea\Setup\SetupService;
use Doctrine\DBAL\Connection;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;

class SetupController extends Controller
{
    public function rootInstaller(Request $request)
    {
        $requirementsCollection = $this->getRequirementsCollection();

        return $this->render('/setup/index.html.twig', [
            'locale'                 => $this->app['locale'],
            'available_locales'      => Application::getAvailableLanguages(),
            'current_servername'     => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'requirementsCollection' => $requirementsCollection,
        ]);
    }

    /**
     * @return RequirementCollectionInterface[]
     */
    private function getRequirementsCollection()
    {
        return [
            new BinariesRequirements(),
            new FilesystemRequirements(),
            new LocalesRequirements(),
            new PhpRequirements(),
            new SystemRequirements(),
        ];
    }

    public function displayUpgradeInstructions()
    {
        return $this->render('/setup/upgrade-instructions.html.twig', [
            'locale'              => $this->app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
        ]);
    }

    public function getInstallForm(Request $request)
    {
        $warnings = [];

        $requirementsCollection = $this->getRequirementsCollection();
        foreach ($requirementsCollection as $requirements) {
            foreach ($requirements->getRequirements() as $requirement) {
                if (!$requirement->isFulfilled() && !$requirement->isOptional()) {
                    $warnings[] = $requirement->getTestMessage();
                }
            }
        }

        if ($request->getScheme() == 'http') {
            $warnings[] = $this->app->trans('It is not recommended to install Phraseanet without HTTPS support');
        }

        return $this->render('/setup/step2.html.twig', [
            'locale'              => $this->app['locale'],
            'available_locales'   => Application::getAvailableLanguages(),
            'available_templates' => ['en', 'fr'],
            'warnings'            => $warnings,
            'error'               => $request->query->get('error'),
            'current_servername'  => $request->getScheme() . '://' . $request->getHttpHost() . '/',
            'discovered_binaries' => \setup::discover_binaries(),
            'rootpath'            => realpath(__DIR__ . '/../../../../'),
        ]);
    }

    public function doInstall(Request $request)
    {
        set_time_limit(360);

        $servername = $request->getScheme() . '://' . $request->getHttpHost() . '/';

        $database_host = $request->request->get('hostname');
        $database_port = $request->request->get('port');
        $database_user = $request->request->get('user');
        $database_password = $request->request->get('db_password');

        $appbox_name = $request->request->get('ab_name');
        $databox_name = $request->request->get('db_name');

        $appboxCommand = new InstallCommand(
            $database_host,
            $database_port,
            $database_user,
            $database_password,
            $appbox_name
        );

        $databoxCommand = null;

        if ($databox_name) {
            $databoxCommand = new InstallCommand(
                $database_host,
                $database_port,
                $database_user,
                $database_password,
                $databox_name
            );
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $template = $request->request->get('db_template');
        $dataPath = $request->request->get('datapath_noweb');

        $binaryData = [
            'php_binary'         => $request->request->get('binary_php'),
            'swf_extract_binary' => $request->request->get('binary_swfextract'),
            'pdf2swf_binary'     => $request->request->get('binary_pdf2swf'),
            'swf_render_binary'  => $request->request->get('binary_swfrender'),
            'unoconv_binary'     => $request->request->get('binary_unoconv'),
            'ffmpeg_binary'      => $request->request->get('binary_ffmpeg'),
            'mp4box_binary'      => $request->request->get('binary_MP4Box'),
            'pdftotext_binary'   => $request->request->get('binary_xpdf'),
        ];

        $initializeEnvironmentCommand = new InitializeEnvironmentCommand(
            $email,
            $password,
            $template,
            $dataPath,
            $servername,
            $binaryData
        );

        /** @var SetupService $service */
        $service = $this->app['setup.service'];
        $result = $service->install($initializeEnvironmentCommand, $appboxCommand, $databoxCommand);

        if (! $result->isSuccessful()) {
            return $this->app->redirectPath('install_step2', [
                'error' => $this->app->trans($result->getReason(), $result->getReasonContext()),
            ]);
        }

        return $this->app->redirectPath('admin', [
            'section' => 'taskmanager',
            'notice'  => 'install_success',
        ]);
    }
}

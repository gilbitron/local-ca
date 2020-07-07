<?php

namespace LocalCA\Command;

use LocalCA\CommandLine;
use LocalCA\Exceptions\GenerateCertificateException;
use LocalCA\Exceptions\GenerateKeyException;
use LocalCA\Filesystem;
use LocalCA\OpenSSL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * @var OpenSSL
     */
    protected $openSSL;

    /**
     * @var CommandLine
     */
    protected $cli;

    protected static $defaultName = 'install';

    public function __construct(Filesystem $files, OpenSSL $openSSL, CommandLine $cli)
    {
        parent::__construct();

        $this->files = $files;
        $this->openSSL = $openSSL;
        $this->cli = $cli;
    }

    protected function configure()
    {
        $this->setDescription('Install Local CA as a local Certificate Authority');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keyPath = caPrivateKeyPath();
        $certPath = caCertificatePath();

        if (!$this->files->exists($keyPath)) {
            $output->writeln('Generating local CA private key...');

            $this->files->ensureDirExists(dirname($keyPath), user());

            try {
                $privateKey = $this->openSSL->generatePrivateKey();
            } catch (GenerateKeyException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }

            $this->files->putAsUser($keyPath, $privateKey);

            if (!$this->files->exists($keyPath)) {
                $output->writeln('<error>Failed to generate CA private key</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln('Local CA private key already exists. <comment>Skipping...</comment>');
        }

        if (!$this->files->exists($certPath)) {
            $output->writeln('Generating local CA certificate...');

            $privateKey = $this->files->get($keyPath);
            $this->files->ensureDirExists(dirname($certPath), user());

            try {
                $certificate = $this->openSSL->generateCertificate($privateKey);
            } catch (GenerateCertificateException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }

            $this->files->putAsUser($certPath, $certificate);

            if (!$this->files->exists($certPath)) {
                $output->writeln('<error>Failed to generate CA certificate</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln('Local CA certificate already exists. <comment>Skipping...</comment>');
        }

        $output->writeln('Adding local CA certificate to the trust store...');

        $result = $this->trustCertificate($certPath);
        if (!$result) {
            $output->writeln('<error>Failed to trust CA certificate</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Local CA successfully installed</info>');
        return Command::SUCCESS;
    }

    /**
     * Add the local CA certificate to the trust store.
     *
     * @param string $certPath
     * @return bool
     */
    private function trustCertificate($certPath)
    {
        $success = true;

        $this->cli->run("sudo security delete-certificate -c \"{$certPath}\" /Library/Keychains/System.keychain");

        $this->cli->run(
            "sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain \"{$certPath}\"",
            function ($exitCode, $output) use (&$success) {
                $success = false;
            }
        );

        return $success;
    }
}

<?php

namespace LocalCA\Command;

use LocalCA\CommandLine;
use LocalCA\Exceptions\GenerateCertificateException;
use LocalCA\Exceptions\GenerateKeyException;
use LocalCA\Filesystem;
use LocalCA\OpenSSL;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
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

    protected static $defaultName = 'new';

    public function __construct(Filesystem $files, OpenSSL $openSSL, CommandLine $cli)
    {
        parent::__construct();

        $this->files = $files;
        $this->openSSL = $openSSL;
        $this->cli = $cli;
    }

    protected function configure()
    {
        $this->setDescription('Generate a new certificate')
             ->addArgument('domain', InputArgument::REQUIRED, 'The domain of the site you are generating a certificate for.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('domain');
        $keyPath = caHomePath() . "/{$domain}/{$domain}.key";
        $certPath = caHomePath() . "/{$domain}/{$domain}.crt";
        $configPath = caHomePath() . "/{$domain}/{$domain}.conf";
        $caKeyPath = caPrivateKeyPath();
        $caCertPath = caCertificatePath();

        if (!$this->files->exists($keyPath)) {
            $output->writeln('Generating private key...');

            $this->files->ensureDirExists(dirname($keyPath), user());

            try {
                $privateKey = $this->openSSL->generatePrivateKey();
            } catch (GenerateKeyException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }

            $this->files->putAsUser($keyPath, $privateKey);

            if (!$this->files->exists($keyPath)) {
                $output->writeln('<error>Failed to generate private key</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln('Private key already exists. <comment>Skipping...</comment>');
        }

        if (!$this->files->exists($certPath)) {
            $output->writeln('Generating certificate...');

            $privateKey = $this->files->get($keyPath);
            $caPrivateKey = $this->files->get($caKeyPath);
            $caCertificate = $this->files->get($caCertPath);

            $this->files->ensureDirExists(dirname($certPath), user());
            $this->createCertificateConfig($configPath, $domain);

            try {
                $certificate = $this->openSSL->generateCertificate($privateKey, $domain, $caCertificate, $caPrivateKey, $configPath);
            } catch (GenerateCertificateException $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }

            $this->files->putAsUser($certPath, $certificate);

            if (!$this->files->exists($certPath)) {
                $output->writeln('<error>Failed to generate certificate</error>');
                return Command::FAILURE;
            }
        } else {
            $output->writeln('Certificate already exists. <comment>Skipping...</comment>');
        }

        $output->writeln('Adding certificate to the trust store...');

        $result = $this->trustCertificate($certPath);
        if (!$result) {
            $output->writeln('<error>Failed to trust certificate</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Private key and certificate successfully generated</info>');
        $output->writeln("<info>Private key: {$keyPath}</info>");
        $output->writeln("<info>Certificate: {$certPath}</info>");
        return Command::SUCCESS;
    }

    /**
     * Create the SSL config for the domain.
     *
     * @param string $configPath
     * @param string $domain
     * @return void
     */
    private function createCertificateConfig($configPath, $domain)
    {
        $this->files->putAsUser($configPath, "[req]
distinguished_name = req_distinguished_name
req_extensions = v3_req

[req_distinguished_name]
countryName = Country Name (2 letter code)
countryName_default = US
stateOrProvinceName = State or Province Name (full name)
stateOrProvinceName_default = MN
localityName = Locality Name (eg, city)
localityName_default = Minneapolis
organizationalUnitName	= Organizational Unit Name (eg, section)
organizationalUnitName_default	= Domain Control Validated
commonName = Internet Widgits Ltd
commonName_max	= 64

[ v3_req ]
# Extensions to add to a certificate request
basicConstraints = CA:FALSE
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alt_names

[alt_names]
DNS.1 = {$domain}
DNS.2 = *.{$domain}");
    }

    /**
     * Add the certificate to the trust store.
     *
     * @param string $certPath
     * @return bool
     */
    private function trustCertificate($certPath)
    {
        $success = true;

        $output = $this->cli->run(
            "sudo security add-trusted-cert -d -r trustAsRoot -k /Library/Keychains/System.keychain \"{$certPath}\"",
            function ($exitCode, $output) use (&$success) {
                $success = false;
            }
        );

        return $success;
    }
}
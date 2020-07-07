<?php

namespace LocalCA;

use LocalCA\Exceptions\GenerateCertificateException;
use LocalCA\Exceptions\GenerateKeyException;

class OpenSSL
{
    /**
     * Generate a private key.
     *
     * @throws GenerateKeyException
     * @return string
     */
    public function generatePrivateKey()
    {
        $key = openssl_pkey_new([
            'digest_alg'       => 'SHA256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (!$key) {
            throw new GenerateKeyException('Failed to generate private key');
        }

        $result = openssl_pkey_export($key, $privateKey);

        if (!$result) {
            throw new GenerateKeyException('Failed to export private key');
        }

        return $privateKey;
    }

    /**
     * Generate a certificate using a private key and optionally sign it.
     *
     * @throws GenerateCertificateException
     * @param string $privateKey
     * @param string|null $domain
     * @param string|null $signCertificate
     * @param string|null $signKey
     * @param string|null $configPath
     * @param integer $days
     * @return string
     */
    public function generateCertificate($privateKey, $domain = null, $signCertificate = null, $signKey = null, $configPath = null, $days = 365)
    {
        $dn = [
            'countryName'            => 'US',
            'stateOrProvinceName'    => 'NY',
            'localityName'           => 'New York',
            'organizationName'       => 'Local CA Self Signed Organization',
            'commonName'             => 'Local CA Self Signed CA',
            'organizationalUnitName' => 'Developers',
            'emailAddress'           => 'rootcertificate@local-ca.test',
        ];

        if ($domain) {
            $dn['commonName'] = $domain;
            $dn['emailAddress'] = "{$domain}@local-ca.test";
        }

        $csr = openssl_csr_new($dn, $privateKey, ['digest_alg' => 'sha256']);

        if (!$csr) {
            throw new GenerateCertificateException('Failed to geneate CSR');
        }

        if (!$signKey) {
            $signKey = $privateKey;
        }

        $config = ['digest_alg' => 'sha256'];

        if ($configPath) {
            $config = [
                'digest_alg'      => 'sha256',
                'config'          => $configPath,
                'x509_extensions' => 'v3_req',
            ];
        }

        $x509 = openssl_csr_sign($csr, $signCertificate, $signKey, $days, $config);

        if (!$x509) {
            throw new GenerateCertificateException('Failed to sign CSR');
        }

        $result = openssl_x509_export($x509, $certificate);

        if (!$result) {
            throw new GenerateCertificateException('Failed to export certificate');
        }

        return $certificate;
    }
}

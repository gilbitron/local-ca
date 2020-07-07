<?php

/**
 * Get the user.
 */
function user()
{
    if (!isset($_SERVER['SUDO_USER'])) {
        return $_SERVER['USER'];
    }

    return $_SERVER['SUDO_USER'];
}

/**
 * Get the path to the local-ca home directory.
 *
 * @return string
 */
function caHomePath()
{
    return LOCALCA_HOME_PATH;
}

/**
 * Get the path to the local CA private key.
 *
 * @return string
 */
function caPrivateKeyPath()
{
    return LOCALCA_HOME_PATH . DIRECTORY_SEPARATOR . 'local-ca.key';
}

/**
 * Get the path to the local CA certificate.
 *
 * @return string
 */
function caCertificatePath()
{
    return LOCALCA_HOME_PATH . DIRECTORY_SEPARATOR . 'local-ca.pem';
}

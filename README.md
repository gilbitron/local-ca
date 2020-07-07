# Local CA

Local CA is a simple command line tool to create locally trusted certificates for your development websites. Written in PHP.

## Requirements

* macOS
* PHP 7.2.5
* [OpenSSL extension](https://www.php.net/manual/en/book.openssl.php)

## Installation

Install Local CA globally using composer:

```
composer global require gilbitron/local-ca
```

Make sure to place Composer's system-wide vendor bin directory in your `$PATH` so the local-ca executable can be located by your system. In macOS this is `$HOME/.composer/vendor/bin`.

## Usage

First, you need to install Local CA as a locally trusted certificate authority:

```
local-ca install
```

This will generate a root primary key and certificate that will be used to sign the development certificates. All keys are generated using 2048 bit RSA encryption.

Next, generate a development certificate by using the `new` command and passing a domain:

```
local-ca new example.test
```

This will generate a development primary key and certificate, signed and trusted by the root, that you can use in your development sites. Certificates are valid for 365 days.

* [Instructions on setting up HTTPS in Nginx](http://nginx.org/en/docs/http/configuring_https_servers.html)
* [Instructions on setting up HTTPS in Apache](https://httpd.apache.org/docs/2.4/ssl/ssl_howto.html)

## Credits

Local CA was created by [Gilbert Pellegrom](https://gilbitron.me) from [Dev7studios](https://dev7studios.co). Released under the MIT license.

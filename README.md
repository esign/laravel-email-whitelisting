# Whitelist outgoing email

[![Latest Version on Packagist](https://img.shields.io/packagist/v/esign/laravel-email-whitelisting.svg?style=flat-square)](https://packagist.org/packages/esign/laravel-email-whitelisting)
[![Total Downloads](https://img.shields.io/packagist/dt/esign/laravel-email-whitelisting.svg?style=flat-square)](https://packagist.org/packages/esign/laravel-email-whitelisting)
![GitHub Actions](https://github.com/esign/laravel-email-whitelisting/actions/workflows/main.yml/badge.svg)

This package allows you to whitelist email addresses for outgoing emails.
This way you have control over what addresses should be allowed to receive mails.
This comes in handy when testing on development / staging environments.

## Installation

You can install the package via composer:

```bash
composer require esign/laravel-email-whitelisting
```

Next up, you can publish the configuration file:
```bash
php artisan vendor:publish --provider="Esign\EmailWhitelisting\EmailWhitelistingServiceProvider" --tag="config"
```

The config file will be published as `config/email-whitelisting.php` with the following contents:
```php
return [
    /**
     * This is used to disable or enable the use of this package.
     */
    'enabled' => env('EMAIL_WHITELISTING_ENABLED', false),

    /**
     * This is the driver responsible for providing whitelisted email addresses.
     * It should implement the EmailWhitelistingDriverContract interface.
     */
    'driver' => \Esign\EmailWhitelisting\Drivers\ConfigurationDriver::class,

    /**
     * Enabling this setting will cause all outgoing emails to be sent to the
     * configured email adresses, disregarding if they're present in To, Cc or Bcc.
     * When using the config driver these will be the addresses defined in the 'mail_addresses' config key.
     * When using the database driver these will be the addresses where 'redirect_email' is true.
     */
    'redirecting_enabled' => env('EMAIL_WHITELISTING_REDIRECTING_ENABLED', false),

    /**
     * When using the config driver you can define email addresses in this array.
     */
    'mail_addresses' => [
        // 'john@example.com'
    ],
];
```

## Usage
This package is disabled by default. To enable it you may set the `EMAIL_WHITELISTING_ENABLED` env variable to `true`.
It ships with both a `ConfigurationDriver` and `DatabaseDriver` out of the box.

### Config
You may define whitelisted email addresses for the config driver under the `mail_addresses` key.
### Database
In case you want to configure email whitelisting using the database this package comes with a database driver out of the box.
Make sure to publish the migration before making use of this driver:
```bash
php artisan vendor:publish --provider="Esign\EmailWhitelisting\EmailWhitelistingServiceProvider" --tag="migrations"
```

Whitelisted email addresses can be created in the following way:
```php
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;

WhitelistedEmailAddress::create(['email' => 'john@example.com']);
```

### Redirecting emails
In some cases you might want to redirect all outgoing mail to certain addresses.
This can be achieved by setting the env variable `EMAIL_WHITELISTING_REDIRECTING_ENABLED` to true.
When using the database driver you may specify to which email addresses outgoing mail will be redirected, by setting the `redirect_email` column value to true.
When using the config driver no extra configuration is required. Email addresses defined in the `mail_addresses` will be used.
### Wildcards
In case you need to cover lots of email addresses, this package supports using wildcards.
By using an `*` you're able to cover a full domain, e.g. `*@esign.eu`.

### Notes
* Notifications sent through the `mail` channel will be whitelisted as well.
* When there are no matching whitelisted email addresses found, the email will be cancelled.
* This package will append the original receivers to the subject of the outgoing mail. e.g. `My cool mail subject (To: john@example.com) (Cc: jane@example.com).

### Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

# Whitelist outgoing email

[![Latest Version on Packagist](https://img.shields.io/packagist/v/esign/laravel-email-whitelisting.svg?style=flat-square)](https://packagist.org/packages/esign/laravel-email-whitelisting)
[![Total Downloads](https://img.shields.io/packagist/dt/esign/laravel-email-whitelisting.svg?style=flat-square)](https://packagist.org/packages/esign/laravel-email-whitelisting)
![GitHub Actions](https://github.com/esign/laravel-email-whitelisting/actions/workflows/main.yml/badge.svg)

This package allows you to whitelist email address for outgoing emails on your test or staging environment. 
This way you can safely test your outgoing emails without worrying about sending test emails to external users.  

## Installation

You can install the package via composer:

```bash
composer require esign/laravel-email-whitelisting
```

This package comes with a migration to store your whitelisted email addresses. 
You can publish this migration using:
```bash
php artisan vendor:publish --provider="Esign\EmailWhitelisting\EmailWhitelistingServiceProvider" --tag="migrations"
```

In your .env file you may use the below config to use the package the way you want.

* `WHITELIST_MAILS` Is a boolean used to determine if the whitelist package should be used. 
When set to false there will be no email whitelisting or email redirects.

* The default setting for this package is whitelisting emails. 
To redirect all emails to the configured email addresses set the `REDIRECT_MAILS` to true.

## Usage

### Whitelist
For whitelisting email addresses this package will use the configured email addresses in the `whitelist_email_addresses` table.
The whitelisting will automatically apply when `WHITELIST_MAILS` is set to true and `REDIRECT_MAILS` is set to false.

### Redirect
If you choose to redirect the emails you need to set `REDIRECT_MAILS=true` in your .env file.
Next you'll need to set the `redirect_email` boolean to true on all the email addresses that you want to redirect the emails to in the `whitelist_email_addresses` table.

## Notifications
This package can also whitelist or redirect notifications that are sent through the mail channel.
This works the same way as normal emails.

## Notes
* When there are no emails to send a mail to due to the email not containing any "to"
email addresses in the email whitelisting config the email will not be send. This will not throw an error.

### Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

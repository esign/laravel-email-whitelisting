<?php

return [
    /**
     * This is used to disable or enable the use of this package.
     */
    'enabled' => env('EMAIL_WHITELISTING_ENABLED', false),

    /**
     * You can change the driver option to config or database.
     * The config driver will use email addresses in the mail_addresses array in this file.
     * The database driver will use mail addresses in the whitelist_email_addresses table in your database
     * OPTIONS: config | database
     */
    'driver' => env('WHITELIST_MAIL_DRIVER', 'config'),

    /**
     * Set this option to true to redirect all mails to the configured addresses.
     */
    'redirect_mails' => env('REDIRECT_MAILS', false),

    'mail_addresses' => [

    ],
];

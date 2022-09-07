<?php

return [
    /**
     * This is used to disable or enable the use of this package.
     */
    'enabled' => env('EMAIL_WHITELISTING_ENABLED', false),

    /**
     * This is the driver responsible for providing whitelisted email addresses.
     * OPTIONS: config | database
     */
    'driver' => env('EMAIL_WHITELISTING_DRIVER', 'config'),

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

<?php

return [
    /**
     * This is used to disable or enable the use of this package.
     * When set to false email addresses will not be whitelisted.
     * Emails will also not be redirected.
     */
    'whitelist_mails' => env('WHITELIST_MAILS', true),

    /**
     * Set this option to true to redirect all mails to the configured addresses.
     */
    'redirect_mails' => env('REDIRECT_MAILS', false),
];
<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\EmailWhitelistingServiceProvider;
use Illuminate\Mail\SentMessage;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\Mime\Address;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EmailWhitelistingServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $WhitelistEmailAddressesMigration = include __DIR__ . '/../database/migrations/create_whitelist_email_addresses_table.php.stub';

        // this migration is only for tests
        $userMigration = include __DIR__ . '/Stubs/Migrations/create_users_table.php.stub';

        $WhitelistEmailAddressesMigration->up();
        $userMigration->up();
    }

    /**
     * @param SentMessage $mail
     * @param string $type To|Cc|Bcc
     * @return array
     */
    public function getAddresses(SentMessage $mail, string $type = 'To'): array
    {
        $recipients = $mail->getSymfonySentMessage()->getOriginalMessage()->{'get' . $type}();

        return $this->addressesToString($recipients);
    }

    protected function addressesToString(array $addresses): array
    {
        return collect($addresses)->map(function (Address $item) {
            return $item->getAddress();
        })->toArray();
    }
}

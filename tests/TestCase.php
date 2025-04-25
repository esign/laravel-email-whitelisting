<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Drivers\DatabaseDriver;
use Esign\EmailWhitelisting\EmailWhitelistingServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\Mime\Address;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [EmailWhitelistingServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        Config::set('email-whitelisting.enabled', true);
        Config::set('email-whitelisting.warn', false);
        $app->bind(EmailWhitelistingDriverContract::class, DatabaseDriver::class);
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

    protected function setUpDatabase(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        $whitelistEmailAddressesMigration = include __DIR__ . '/../database/migrations/create_whitelist_email_addresses_table.php.stub';
        $whitelistEmailAddressesMigration->up();
    }
}

<?php

namespace Esign\EmailWhitelisting\Tests\Email;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Drivers\ConfigurationDriver;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Support\Mail\TestMail;
use Esign\EmailWhitelisting\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('email-whitelisting.redirecting_enabled', true);
    }

    /** @test */
    public function it_can_redirect_emails_to_an_email_address()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail, 'To');

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    /** @test */
    public function it_can_redirect_emails_to_multiple_email_address()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);
        WhitelistedEmailAddress::create(['email' => 'test2@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail, 'To');

        $this->assertEquals(['test@esign.eu', 'test2@esign.eu'], $recipients);
    }

    /** @test */
    public function it_removes_cc_in_redirect_mails()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->cc('example@example.com')->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEmpty($ccRecipients);
    }

    /** @test */
    public function it_removes_bcc_in_redirect_mails()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->bcc('example@example.com')->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEmpty($bccRecipients);
    }

    /** @test */
    public function it_can_use_the_config_driver()
    {
        $this->app->bind(EmailWhitelistingDriverContract::class, ConfigurationDriver::class);
        Config::set('email-whitelisting.mail_addresses', ['test@esign.eu']);

        $mail = Mail::to(['agf@esign.eu', 'example@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail, 'To');

        $this->assertEquals(['test@esign.eu'], $recipients);
    }
}

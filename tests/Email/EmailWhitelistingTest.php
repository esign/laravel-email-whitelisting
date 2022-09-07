<?php

namespace Esign\EmailWhitelisting\Tests\Email;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Drivers\ConfigurationDriver;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Support\Mail\TestMail;
use Esign\EmailWhitelisting\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

class EmailWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('email-whitelisting.redirecting_enabled', false);
    }

    /** @test */
    public function it_can_whitelist_email_addresses()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_cc()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->cc(['cc@esign.eu', 'cc2@esign.eu'])->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEquals(['cc@esign.eu'], $ccRecipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_bcc()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->bcc(['bcc@esign.eu', 'bcc2@esign.eu'])->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEquals(['bcc@esign.eu'], $bccRecipients);
    }

    /** @test */
    public function it_can_disable_email_whitelisting()
    {
        Config::set('email-whitelisting.enabled', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu'], $recipients);
    }

    /** @test */
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given()
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@test.eu', 'test2@test.eu'])->send(new TestMail());

        $this->assertNull($mail);
    }

    /** @test */
    public function it_can_whitelist_emails_in_queued_mails()
    {
        Event::fake();
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        Mail::to(['test@esign.eu', 'agf@esign.eu'])->queue(new TestMail());

        Event::assertDispatched(MessageSending::class);
    }

    /** @test */
    public function it_can_add_original_to_address_in_subject()
    {
        WhitelistedEmailAddress::create(['email' => 'testA@esign.eu']);

        $mail = Mail::to(['testA@esign.eu', 'testB@esign.eu'])
            ->cc('testC@esign.eu')
            ->bcc('testD@esign.eu')
            ->send(new TestMail());

        $this->assertEquals(
            'test (To: testA@esign.eu, testB@esign.eu) (Cc: testC@esign.eu) (Bcc: testD@esign.eu)',
            $mail->getSymfonySentMessage()->getOriginalMessage()->getSubject()
        );
    }

    /** @test */
    public function it_can_use_the_config_driver()
    {
        $this->app->bind(EmailWhitelistingDriverContract::class, ConfigurationDriver::class);
        Config::set('email-whitelisting.mail_addresses', ['test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    /** @test */
    public function it_can_use_wildcards()
    {
        WhitelistedEmailAddress::create(['email' => '*@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }

    /** @test */
    public function it_wont_add_duplicate_addresses_with_wild_cards()
    {
        WhitelistedEmailAddress::create(['email' => '*@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }

    /** @test */
    public function it_can_use_wildcards_with_the_config_driver()
    {
        $this->app->bind(EmailWhitelistingDriverContract::class, ConfigurationDriver::class);
        Config::set('email-whitelisting.mail_addresses', ['*@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }
}

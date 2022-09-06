<?php

namespace Esign\EmailWhitelisting\Tests\Email;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Stubs\Mail\TestMail;
use Esign\EmailWhitelisting\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

class EmailWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_whitelist_email_addresses()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_cc()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->cc(['cc@esign.eu', 'cc2@esign.eu'])->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEquals(['cc@esign.eu'], $ccRecipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_bcc()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->bcc(['bcc@esign.eu', 'bcc2@esign.eu'])->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEquals(['bcc@esign.eu'], $bccRecipients);
    }

    /** @test */
    public function it_can_disable_email_whitelisting()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', false);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu'], $recipients);
    }

    /** @test */
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@test.eu', 'test2@test.eu'])->send(new TestMail());

        $this->assertNull($mail);
    }

    /** @test */
    public function it_can_whitelist_emails_in_queued_mails()
    {
        Config::set('email-whitelisting.driver', 'database');
        Event::fake();
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        Mail::to(['test@esign.eu', 'agf@esign.eu'])->queue(new TestMail());

        Event::assertDispatched(MessageSending::class);
    }

    /** @test */
    public function it_can_add_original_to_address_in_subject()
    {
        Config::set('email-whitelisting.driver', 'database');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());

        $this->assertEquals('test (To: test@esign.eu, agf@esign.eu, )', $mail->getSymfonySentMessage()->getOriginalMessage()->getSubject());
    }

    /** @test */
    public function it_can_use_the_config_driver()
    {
        Config::set('email-whitelisting.driver', 'config');
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        Config::set('email-whitelisting.mail_addresses', ['test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }
}

<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\Mail\TestMail;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_redirect_emails_to_an_email_address()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail, 'To');

        $this->assertEquals(['test@esign.eu'] , $recipients);
    }

    /** @test */
    public function it_can_redirect_emails_to_multiple_email_address()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);
        WhitelistedEmailAddress::create(['email' => 'test2@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail, 'To');

        $this->assertEquals(['test@esign.eu', 'test2@esign.eu'] , $recipients);
    }

    /** @test */
    public function it_removes_cc_in_redirect_mails()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->cc('example@example.com')->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEmpty($ccRecipients);
    }

    /** @test */
    public function it_removes_bcc_in_redirect_mails()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $mail = Mail::to(['seppe@esign.eu', 'example@esign.eu', 'test2@example.com'])->bcc('example@example.com')->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEmpty($bccRecipients);
    }


}
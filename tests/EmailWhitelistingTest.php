<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\Mail\TestMail;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class EmailWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_whitelist_email_addresses()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'] , $recipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_cc()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->cc(['cc@esign.eu', 'cc2@esign.eu'])->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEquals(['cc@esign.eu'] , $ccRecipients);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_bcc()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->bcc(['bcc@esign.eu', 'bcc2@esign.eu'])->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEquals(['bcc@esign.eu'] , $bccRecipients);
    }

    /** @test */
    public function it_can_disable_email_whitelisting()
    {
        Config::set('email-whitelisting.whitelist_mails', false);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu'] , $recipients);
    }

    /** @test */
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@test.eu', 'test2@test.eu'])->send(new TestMail());

        $this->assertNull($mail);
    }

    /** @test */
    public function it_can_whitelist_emails_in_queued_mails()
    {
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        Queue::resolved(function (QueueManager $queueManager) {
            $queueManager->after(function (JobProcessed $event) {
                // get mailable from job processed
                $mailable = unserialize($event->job->payload()['data']['command'])->mailable;
                $this->assertContains(['name' => null, 'address' => 'test@esign.eu'] ,$mailable->to);
                $this->assertNotContains(['name' => null, 'address' => 'agf@esign.eu'] ,$mailable->to);

                $this->assertEquals(1, count($mailable->to));
            });
        });

        Mail::to(['test@esign.eu', 'agf@esign.eu'])->queue(new TestMail());
    }

}
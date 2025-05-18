<?php

namespace Esign\EmailWhitelisting\Tests\Email;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Drivers\ConfigurationDriver;
use Esign\EmailWhitelisting\Events\EmailAddressesSkipped;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Support\Mail\TestMail;
use Esign\EmailWhitelisting\Tests\TestCase;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

final class EmailWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('email-whitelisting.redirecting_enabled', false);
    }

    #[Test]
    public function it_can_whitelist_email_addresses(): void
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    #[Test]
    public function it_can_whitelist_email_addresses_in_cc(): void
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->cc(['cc@esign.eu', 'cc2@esign.eu'])->send(new TestMail());
        $ccRecipients = $this->getAddresses($mail, 'Cc');

        $this->assertEquals(['cc@esign.eu'], $ccRecipients);
    }

    #[Test]
    public function it_can_whitelist_email_addresses_in_bcc(): void
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        $mail = Mail::to(['test@esign.eu'])->bcc(['bcc@esign.eu', 'bcc2@esign.eu'])->send(new TestMail());
        $bccRecipients = $this->getAddresses($mail, 'Bcc');

        $this->assertEquals(['bcc@esign.eu'], $bccRecipients);
    }

    #[Test]
    public function it_can_disable_email_whitelisting(): void
    {
        Config::set('email-whitelisting.enabled', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu'], $recipients);
    }

    #[Test]
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given(): void
    {
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@test.eu', 'test2@test.eu'])->send(new TestMail());

        $this->assertNull($mail);
    }

    #[Test]
    public function it_can_whitelist_emails_in_queued_mails(): void
    {
        Event::fake();
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        Mail::to(['test@esign.eu', 'agf@esign.eu'])->queue(new TestMail());

        Event::assertDispatched(MessageSending::class);
    }

    #[Test]
    public function it_can_add_original_to_address_in_subject(): void
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

    #[Test]
    public function it_can_use_the_config_driver(): void
    {
        $this->app->bind(EmailWhitelistingDriverContract::class, ConfigurationDriver::class);
        Config::set('email-whitelisting.mail_addresses', ['test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu'], $recipients);
    }

    #[Test]
    public function it_can_use_wildcards(): void
    {
        WhitelistedEmailAddress::create(['email' => '*@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }

    #[Test]
    public function it_wont_add_duplicate_addresses_with_wild_cards(): void
    {
        WhitelistedEmailAddress::create(['email' => '*@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }

    #[Test]
    public function it_can_use_wildcards_with_the_config_driver(): void
    {
        $this->app->bind(EmailWhitelistingDriverContract::class, ConfigurationDriver::class);
        Config::set('email-whitelisting.mail_addresses', ['*@esign.eu']);

        $mail = Mail::to(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu', 'external@gmail.com'])->send(new TestMail());
        $recipients = $this->getAddresses($mail);

        $this->assertEquals(['test@esign.eu', 'agf@esign.eu', 'test2@esign.eu'], $recipients);
    }

    #[Test]
    public function it_triggers_an_event_when_email_addresses_are_skipped(): void
    {
        Event::fake([EmailAddressesSkipped::class]);
        WhitelistedEmailAddress::create(['email' => 'to@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        Mail::to(['to@esign.eu', 'to2@example.com'])
            ->cc(['cc@esign.eu', 'cc2@esign.eu'])
            ->bcc(['bcc@esign.eu', 'bcc2@esign.eu'])
            ->send(new TestMail());

        Event::assertDispatchedTimes(EmailAddressesSkipped::class, 3);
        Event::assertDispatched(EmailAddressesSkipped::class, function (EmailAddressesSkipped $event) {
            $assertSendingType = $event->sendingType === 'To';
            $assertSkippedEmailAddresses = collect(['to2@example.com'])
                ->every(fn ($email) => $event->skippedEmailAddresses->contains($email));
            $assertOriginalEmailAddresses = collect(['to@esign.eu', 'to2@example.com'])
                ->every(fn ($email) => $event->originalEmailAddresses->contains($email));

            return $assertSkippedEmailAddresses && $assertOriginalEmailAddresses && $assertSendingType;
        });
        Event::assertDispatched(EmailAddressesSkipped::class, function (EmailAddressesSkipped $event) {
            $assertSendingType = $event->sendingType === 'Cc';
            $assertSkippedEmailAddresses = collect(['cc2@esign.eu'])
                ->every(fn ($email) => $event->skippedEmailAddresses->contains($email));
            $assertOriginalEmailAddresses = collect(['cc@esign.eu', 'cc2@esign.eu'])
                ->every(fn ($email) => $event->originalEmailAddresses->contains($email));

            return $assertSkippedEmailAddresses && $assertOriginalEmailAddresses && $assertSendingType;
        });
        Event::assertDispatched(EmailAddressesSkipped::class, function (EmailAddressesSkipped $event) {
            $assertSendingType = $event->sendingType === 'Bcc';
            $assertSkippedEmailAddresses = collect(['bcc2@esign.eu'])
                ->every(fn ($email) => $event->skippedEmailAddresses->contains($email));
            $assertOriginalEmailAddresses = collect(['bcc@esign.eu', 'bcc2@esign.eu'])
                ->every(fn ($email) => $event->originalEmailAddresses->contains($email));

            return $assertSkippedEmailAddresses && $assertOriginalEmailAddresses && $assertSendingType;
        });
    }

    #[Test]
    public function it_wont_trigger_an_event_when_no_email_addresses_are_skipped(): void
    {
        Event::fake([EmailAddressesSkipped::class]);
        WhitelistedEmailAddress::create(['email' => 'to@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'cc@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'bcc@esign.eu']);

        Mail::to(['to@esign.eu'])
            ->cc(['cc@esign.eu'])
            ->bcc(['bcc@esign.eu'])
            ->send(new TestMail());

        Event::assertNotDispatched(EmailAddressesSkipped::class);
    }

    #[Test]
    public function it_wont_trigger_an_event_when_no_email_addresses_are_skipped_using_wildcards(): void
    {
        Event::fake([EmailAddressesSkipped::class]);
        WhitelistedEmailAddress::create(['email' => '*@esign.eu']);

        Mail::to(['to@esign.eu'])
            ->cc(['cc@esign.eu'])
            ->bcc(['bcc@esign.eu'])
            ->send(new TestMail());

        Event::assertNotDispatched(EmailAddressesSkipped::class);
    }

    #[Test]
    public function it_can_throw_an_exception_when_listening_for_events(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email addresses skipped');
        Event::listen(EmailAddressesSkipped::class, fn () => throw new Exception('Email addresses skipped'));

        Mail::to(['to@esign.eu'])->send(new TestMail());
    }
}

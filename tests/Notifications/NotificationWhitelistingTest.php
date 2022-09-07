<?php

namespace Esign\EmailWhitelisting\Tests\Notifications;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Support\Models\User;
use Esign\EmailWhitelisting\Tests\Support\Notifications\TestNotification;
use Esign\EmailWhitelisting\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class NotificationWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('email-whitelisting.redirecting_enabled', false);
    }

    /** @test */
    public function it_can_whitelist_email_address_in_a_notification()
    {
        Config::set('email-whitelisting.driver', 'database');
        Event::fake(MessageSent::class);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $user = User::create([
            'name' => 'test',
            'email' => 'test2@esign.eu',
        ]);

        $user->notify(new TestNotification());

        Event::assertNotDispatched(MessageSent::class);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_a_notification()
    {
        Config::set('email-whitelisting.driver', 'database');
        Event::fake(MessageSent::class);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'test2@esign.eu']);

        $userA = User::create([
            'name' => 'test1',
            'email' => 'test@esign.eu',
        ]);

        $userB = User::create([
            'name' => 'test2',
            'email' => 'test2@esign.eu',
        ]);

        $userC = User::create([
            'name' => 'test3',
            'email' => 'test3@esign.eu',
        ]);

        Notification::send([$userA, $userB, $userC], new TestNotification());

        Event::assertDispatchedTimes(MessageSent::class, 2);
    }

    /** @test */
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given()
    {
        Config::set('email-whitelisting.driver', 'database');
        Event::fake(MessageSent::class);

        $userA = User::create([
            'name' => 'test1',
            'email' => 'test@esign.eu',
        ]);

        $userB = User::create([
            'name' => 'test2',
            'email' => 'test2@esign.eu',
        ]);

        $userC = User::create([
            'name' => 'test3',
            'email' => 'test3@esign.eu',
        ]);

        Notification::send([$userA, $userB, $userC], new TestNotification());

        Event::assertNotDispatched(MessageSent::class);
    }

    /** @test */
    public function it_can_redirect_a_notification_to_another_user()
    {
        Event::fake(MessageSent::class);
        Config::set('email-whitelisting.driver', 'database');
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $userA = User::create([
            'name' => 'test1',
            'email' => 'test@esign.eu',
        ]);

        Notification::send([$userA], new TestNotification());

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) {
            return $event->message->getSubject() == 'test (To: test@esign.eu)';
        });
    }
}

<?php

namespace Esign\EmailWhitelisting\Tests\Notifications;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Tests\Stubs\Models\User;
use Esign\EmailWhitelisting\Tests\Stubs\Notifications\TestNotification;
use Esign\EmailWhitelisting\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

class NotificationWhitelistingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_whitelist_email_address_in_a_notification()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);

        $user = new User([
            'name' => 'test',
            'email' => 'test2@esign.eu'
        ]);

        $user->notify(new TestNotification());

        Event::assertNotDispatched(NotificationSent::class);
    }

    /** @test */
    public function it_can_whitelist_email_addresses_in_a_notification()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu']);
        WhitelistedEmailAddress::create(['email' => 'test2@esign.eu']);

        $userA = new User([
            'name' => 'test1',
            'email' => 'test@esign.eu'
        ]);

        $userB = new User([
            'name' => 'test2',
            'email' => 'test2@esign.eu'
        ]);

        $userC = new User([
            'name' => 'test3',
            'email' => 'test3@esign.eu'
        ]);

        Notification::send([$userA, $userB, $userC], new TestNotification());

        Event::assertDispatchedTimes(NotificationSent::class, 2);
    }

    /** @test */
    public function it_wont_throw_an_error_when_no_valid_email_addresses_are_given()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', false);

        $userA = new User([
            'name' => 'test1',
            'email' => 'test@esign.eu'
        ]);

        $userB = new User([
            'name' => 'test2',
            'email' => 'test2@esign.eu'
        ]);

        $userC = new User([
            'name' => 'test3',
            'email' => 'test3@esign.eu'
        ]);

        Notification::send([$userA, $userB, $userC], new TestNotification());

        Event::assertNotDispatched(NotificationSent::class);
    }

}
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

class NotificationRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_redirect_a_notification_to_another_user()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'test@esign.eu', 'redirect_email' => true]);

        $userToRedirectTo = User::create([
            'name' => 'test',
            'email' => 'test@esign.eu'
        ]);

        $user = User::create([
            'name' => 'test',
            'email' => 'test2@esign.eu'
        ]);

        $user->notify(new TestNotification());

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $event) {
            return $event->notifiable->email == 'test@esign.eu';
        });
    }

    /** @test */
    public function it_can_redirect_a_notification_to_multiple_users()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'redirect1@esign.eu', 'redirect_email' => true]);

        $userA = User::create([
            'name' => 'test1',
            'email' => 'test@esign.eu'
        ]);

        $userB = User::create([
            'name' => 'test2',
            'email' => 'test2@esign.eu'
        ]);

        $userC = User::create([
            'name' => 'test3',
            'email' => 'test3@esign.eu'
        ]);

        $userD = User::create([
            'name' => 'test4',
            'email' => 'seppe@esign.eu'
        ]);

        $userE = User::create([
            'name' => 'test5',
            'email' => 'redirect1@esign.eu'
        ]);

        Notification::send([$userA, $userB, $userC, $userD], new TestNotification());

        Event::assertDispatchedTimes(NotificationSent::class,4);

        Event::assertDispatched(NotificationSent::class, function (NotificationSent $event) {
            return $event->notifiable->email == 'redirect1@esign.eu';
        });
    }

    /** @test */
    public function it_can_redirect_multiple_notifications_to_multiple_users()
    {
        Event::fake(NotificationSent::class);
        Config::set('email-whitelisting.whitelist_mails', true);
        Config::set('email-whitelisting.redirect_mails', true);
        WhitelistedEmailAddress::create(['email' => 'redirect1@esign.eu', 'redirect_email' => true]);
        WhitelistedEmailAddress::create(['email' => 'redirect2@esign.eu', 'redirect_email' => true]);

        $userA = User::create([
            'name' => 'test1',
            'email' => 'test@esign.eu'
        ]);

        $userB = User::create([
            'name' => 'test2',
            'email' => 'test2@esign.eu'
        ]);

        $userC = User::create([
            'name' => 'test3',
            'email' => 'test3@esign.eu'
        ]);

        $userD = User::create([
            'name' => 'test4',
            'email' => 'seppe@esign.eu'
        ]);

        $userE = User::create([
            'name' => 'test5',
            'email' => 'redirect1@esign.eu'
        ]);

        $userF = User::create([
            'name' => 'test5',
            'email' => 'redirect2@esign.eu'
        ]);

        Notification::send([$userA, $userB, $userC, $userD], new TestNotification());

        Event::assertDispatchedTimes(NotificationSent::class,8);
    }

}
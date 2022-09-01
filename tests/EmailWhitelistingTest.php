<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\Mail\TestMail;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Address;

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
        $recipients = $this->addressesToString($mail->getEnvelope()->getRecipients());

        $this->assertEquals(['test@esign.eu'] , $recipients);
    }

    protected function addressesToString(array $addresses): array
    {
        return collect($addresses)->map(function (Address $item) {
            return $item->getAddress();
        })->toArray();
    }


}
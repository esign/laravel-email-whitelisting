<?php

namespace Esign\EmailWhitelisting\Tests\Stubs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use SerializesModels;
    use Queueable;

    public function build(): self
    {
        return $this
            ->from('test@esign.eu')
            ->html('test');
    }
}

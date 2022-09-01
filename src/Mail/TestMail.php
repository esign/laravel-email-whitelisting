<?php

namespace Esign\EmailWhitelisting\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use SerializesModels;

    public function build(): self
    {
        return $this
            ->from('test@esign.eu')
            ->html('test');
    }
}

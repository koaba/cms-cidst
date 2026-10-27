<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class ContactAlert extends Mailable
{
    public function build(): static
    {
        return $this->subject('Nouveau message de contact')
            ->view('emails.contact-alert')
            ->text('emails.contact-alert-text');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MassiveEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = 'noreply@clublia.com';
        $subject = $this->data['subject'];
        $name = 'Club LIA';
        //$recipients = explode(',', $this->data['email']);

        return $this->markdown('email.massive-email')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($this->data['email'])
            ->with([ 'message' =>  $this->data['message']]);
    }
}

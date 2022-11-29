<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendgridMail extends Mailable
{
    use Queueable, SerializesModels;


    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
    public function build()
    {
        $address = 'noreply@clublia.com';
        $subject = 'Bienvenido a Club Lia';
        $name = 'Club LIA';

        return $this->markdown('email.message-send')
            ->from($address, $name)
            ->subject($subject)
            ->with([ 'user_info' =>  $this->data]);
    }
}

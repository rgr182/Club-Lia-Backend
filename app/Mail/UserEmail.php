<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserEmail extends Mailable
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
        $subject = 'Registro a membresía Maestro';
        $name = 'Club LIA';

        return $this->markdown('email.email-user-teacher')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($this->data['userEmail'])
            ->with([ 'user_info' =>  $this->data]);
    }
}

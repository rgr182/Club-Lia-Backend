<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class RegisterMember extends Mailable
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
        $subject = 'Registro de Padres en Club Lia';
        $name = 'Club LIA';

        return $this->markdown('email.register-member')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($this->data['email'])
            ->with([ 'user_info' =>  $this->data]);
    }
}
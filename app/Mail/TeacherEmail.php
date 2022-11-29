<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TeacherEmail extends Mailable
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

        // $correos = ['ecarpio@educationmakeover.org', 'abecerra@educationmakeover.org'];
        $correos = ['ana.hernandezmay@gmail.com'];

        return $this->markdown('email.email-teacher')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($correos)
            ->with([ 'user_info' =>  $this->data]);
    }
}

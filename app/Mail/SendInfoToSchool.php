<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInfoToSchool extends Mailable
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
        $subject = 'Registro de escuela en Club Lia';
        $name = 'Club LIA';

        return $this->markdown('email.send-info-to-school')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($this->data['schoolMail'])
            ->with([ 'user_info' =>  $this->data]);
    }
}

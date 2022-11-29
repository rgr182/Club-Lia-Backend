<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportEmail extends Mailable
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
        $subject = 'Quiero apoyar en Club Lia';
        $name = 'Club LIA';

        return $this->markdown('email.support-email')
            ->from($address, $name)
            ->subject($subject)
            ->bcc('clublia123@gmail.com')
            ->with([ 'user_info' =>  $this->data]);
    }
}
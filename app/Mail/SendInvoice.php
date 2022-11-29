<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvoice extends Mailable
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
        $subject = 'Factura de registro a Club Lia';
        $name = 'Club LIA';

        return $this->markdown('email.send-invoice')
            ->from($address, $name)
            ->subject($subject)
            ->bcc($this->data['email'])
            ->with([ 'user_info' =>  $this->data]);
    }
}

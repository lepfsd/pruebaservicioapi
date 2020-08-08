<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
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
        return $this->view('mail')
            ->subject('Token for validate purchase')
            ->with([
                'token' => $this->data->token,
                'amount' => $this->data->amount,
                'description' => $this->data->description,
                'status' => $this->data->status,
                'name' => $this->data->name,
            ]);
    }
}

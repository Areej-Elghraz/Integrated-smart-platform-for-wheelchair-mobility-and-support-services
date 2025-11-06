<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code, $minutes, $maxTimes, $remainTimes, $name, $url;

    public function __construct($code, $seconds, $maxTimes, $remainTimes, $name, $url)
    {
        $this->code = $code;
        $this->minutes = $seconds;
        $this->maxTimes = $maxTimes;
        $this->remainTimes = $remainTimes;
        $this->name = $name;
        $this->url = $url;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Code Mail',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.code-mail',
            with: [
                'otp' => $this->code,
                'minutes' => $this->minutes,
                'maxTimes' => $this->maxTimes,
                'remainTimes' => $this->remainTimes,
                'name' => $this->name,
                'url' => $this->url,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

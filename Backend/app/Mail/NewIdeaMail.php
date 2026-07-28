<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Idea;

class NewIdeaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $idea;

    public function __construct(Idea $idea)
    {
        $this->idea = $idea;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Có một Ý tưởng mới vừa được nộp trong Phòng ban của bạn',
        );
    }

    public function content(): Content
    {
        // Trỏ tới file giao diện hiển thị email
        return new Content(
            view: 'emails.new_idea',
        );
    }
}

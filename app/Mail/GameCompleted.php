<?php

namespace App\Mail;

use App\Models\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GameCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Game $game) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mirek ułożył puzzle 🧩 czas bliknąć kasę!',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.game-completed',
        );
    }
}

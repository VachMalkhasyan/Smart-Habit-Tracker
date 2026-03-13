<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class MissedHabitReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User       $user,
        public Collection $missedHabits,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ You missed {$this->missedHabits->count()} habit(s) yesterday",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.missed-habit-reminder',
        );
    }
}

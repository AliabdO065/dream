<?php

namespace App\Modules\Forms;

use App\Models\Client;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Plain-text alert sent to a client's owner(s) for every new lead. Replying goes straight to the lead. */
class NewLeadMail extends Mailable
{
    public function __construct(public Client $client, public Submission $lead)
    {
    }

    public function envelope(): Envelope
    {
        $name = $this->flat($this->lead->payload['name'] ?? '');
        $email = $this->lead->payload['email'] ?? null;

        return new Envelope(
            subject: 'New lead: ' . ($name ?: 'website visitor') . ' — ' . $this->flat($this->client->name),
            replyTo: $email ? [new Address($email, $name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'forms::mail.lead', with: [
            'url' => route('manage.leads.show', [$this->client, $this->lead->id]),
        ]);
    }

    /** One line, no control characters: safe for a mail header. */
    private function flat(string $s): string
    {
        return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
    }
}

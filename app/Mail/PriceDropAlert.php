<?php

namespace App\Mail;

use App\Models\Hotel;
use App\Models\PriceAlert;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PriceDropAlert extends Mailable
{
    public function __construct(
        public PriceAlert $alert,
        public Hotel $hotel,
        public float $price,
        public string $sourceName,
        public string $sourceUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Price drop: '.$this->hotel->name.' is now ¥'.number_format($this->price),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.price-drop');
    }
}
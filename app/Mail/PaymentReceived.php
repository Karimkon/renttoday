<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Payment;

class PaymentReceived extends Mailable
{
    public $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        $pdfPath = storage_path('app/public/invoices/invoice_'.$this->payment->id.'.pdf');

        return $this->subject('Payment Receipt')
                    ->view('emails.payment_received')
                    ->attach($pdfPath);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;

class InvoicePaid extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $pdfPath = storage_path('app/public/invoices/invoice_'.$this->invoice->id.'.pdf');

        return $this->subject('Invoice Payment Confirmation')
                    ->view('emails.invoice_paid')
                    ->attach($pdfPath);
    }
}

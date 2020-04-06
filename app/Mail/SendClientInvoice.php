<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Company;
use PDF;

class SendClientInvoice extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
        $this->company = Company::first();
    }

    public function build()
    {
        $order = $this->order;
        $company = $this->company;
        $companyName = $this->company->name;
        $invoiceNumber = $this->order->invoice_number;

        $view = view('pdf.invoice', ['items'=>$order->invoiceItems,'order'=>$order, 'company'=>$company]);
        $pdf = PDF::loadHTML($view);

        return $this->subject("Invoice $invoiceNumber from $companyName OMS")
                    ->view('mail.client_invoice')
                    ->attachData($pdf->output(), 'invoice.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}

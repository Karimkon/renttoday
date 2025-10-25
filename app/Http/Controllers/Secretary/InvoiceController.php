<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Apartment;
use PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReceived;
use App\Mail\InvoicePaid;

class InvoiceController extends Controller
{
    // List invoices
    public function index() {
        $invoices = Invoice::with(['tenant','apartment'])->orderBy('created_at','desc')->get();
        return view('secretary.invoices.index', compact('invoices'));
    }

    // Show create form
    public function create() {
        $tenants = Tenant::all();
        return view('secretary.invoices.create', compact('tenants'));
    }

    // Store invoice
    public function store(Request $request) {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'apartment_id' => 'nullable|exists:apartments,id',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|date',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $request->tenant_id,
            'apartment_id' => $request->apartment_id,
            'amount' => $request->amount,
            'month' => $request->month,
            'status' => 'pending',
        ]);

        return redirect()->route('secretary.invoices.show', $invoice)->with('success','Invoice created');
    }

    // Show single invoice
    public function show(Invoice $invoice) {
                $invoices = Invoice::with(['tenant','apartment'])->orderBy('created_at','desc')->get();

        return view('secretary.invoices.show', compact('invoice', 'invoices'));
    }

    // Generate PDF
    public function pdf(Invoice $invoice) {
        $pdf = PDF::loadView('secretary.invoices.pdf', ['invoice' => $invoice]);
        return $pdf->stream('invoice_'.$invoice->id.'.pdf');
    }

    // Mark as Paid
    public function markPaid(Invoice $invoice) {
    // 1️⃣ Mark as paid
    $invoice->status = 'paid';
    $invoice->save();

    // 2️⃣ Generate PDF
    $pdf = PDF::loadView('secretary.invoices.pdf', ['invoice' => $invoice]);
    $pdfPath = storage_path('app/public/invoices/invoice_'.$invoice->id.'.pdf');
    $pdf->save($pdfPath); // save temporarily

    // 3️⃣ Send email
    Mail::to($invoice->tenant->email)->send(new InvoicePaid($invoice));

    return redirect()->back()->with('success','Invoice marked as paid & email sent');
}
    // Delete invoice
    public function destroy(Invoice $invoice) {
        $invoice->delete();
        return redirect()->route('secretary.invoices.index')->with('success', 'Invoice deleted successfully.');
    }
}

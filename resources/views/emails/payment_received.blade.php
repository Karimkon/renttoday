<p>Dear {{ $payment->tenant->name }},</p>
<p>We have received your payment of UGX {{ number_format($payment->amount) }} for apartment {{ $payment->apartment?->number ?? 'Unassigned' }}.</p>
<p>Attached is your official receipt.</p>
<p>Thank you!</p>
<p>Best regards,<br>The Management</p>
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant;

class TenantWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $password;

    public function __construct(Tenant $tenant, $password)
    {
        $this->tenant = $tenant;
        $this->password = $password;
    }

    public function build()
    {
        return $this->subject("Welcome to These Apartments")
                    ->view('emails.tenant_welcome');
    }
}

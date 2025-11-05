<?php
// app/Console/Commands/ApplyLateFees.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialReportService;
use App\Services\RentReminderService;

class ApplyLateFees extends Command
{
    protected $signature = 'fees:apply-late';
    protected $description = 'Apply late fees for unpaid rent after 5th of the month';

    protected $financialService;
    protected $reminderService;

    public function __construct(FinancialReportService $financialService, RentReminderService $reminderService)
    {
        parent::__construct();
        $this->financialService = $financialService;
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('Checking for late payments...');
        
        $feesApplied = $this->financialService->checkAndApplyLateFees();
        
        if ($feesApplied > 0) {
            $this->info("Applied late fees to {$feesApplied} apartments.");
            
            // Send late fee notifications
            $this->sendLateFeeNotifications();
        } else {
            $this->info('No late fees to apply.');
        }

        return Command::SUCCESS;
    }

    private function sendLateFeeNotifications()
    {
        // Get tenants with newly applied late fees
        $lateFees = \App\Models\LatePaymentFee::whereDate('created_at', today())
            ->with(['tenant', 'apartment'])
            ->get();

        foreach ($lateFees as $lateFee) {
            $this->reminderService->sendLateFeeNotification($lateFee->tenant, $lateFee->amount);
        }
    }
}
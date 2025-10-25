<?php
// app/Console/Commands/ApplyLateFees.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialReportService;

class ApplyLateFees extends Command
{
    protected $signature = 'fees:apply-late';
    protected $description = 'Apply late fees for unpaid rent after 5th of the month';

    protected $financialService;

    public function __construct(FinancialReportService $financialService)
    {
        parent::__construct();
        $this->financialService = $financialService;
    }

    public function handle()
    {
        $this->info('Checking for late payments...');
        
        $feesApplied = $this->financialService->checkAndApplyLateFees();
        
        if ($feesApplied > 0) {
            $this->info("Applied late fees to {$feesApplied} apartments.");
        } else {
            $this->info('No late fees to apply.');
        }

        return Command::SUCCESS;
    }
}
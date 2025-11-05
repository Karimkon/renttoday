<?php
// app/Console/Commands/SendRentReminders.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RentReminderService;

class SendRentReminders extends Command
{
    protected $signature = 'reminders:send-rent';
    protected $description = 'Send daily rent payment reminders to tenants';

    protected $reminderService;

    public function __construct(RentReminderService $reminderService)
    {
        parent::__construct();
        $this->reminderService = $reminderService;
    }

    public function handle()
    {
        $this->info('Starting rent reminder process...');
        
        $result = $this->reminderService->sendDailyRentReminders();
        
        $this->info("Rent reminders sent: {$result['sent']}");
        $this->warn("Failed to send: {$result['failed']}");
        $this->info("Total tenants with due rent: {$result['total']}");
        
        // Log summary
        \Log::info('Daily rent reminders completed', $result);

        return Command::SUCCESS;
    }
}
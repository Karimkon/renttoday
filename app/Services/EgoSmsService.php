<?php
// app/Services/EgoSmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EgoSmsService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $senderId;

    public function __construct()
    {
        // Always use live URL
        $this->baseUrl = 'https://www.egosms.co/api/v1/json/';
            
        $this->username = config('services.egosms.username');
        $this->password = config('services.egosms.password');
        $this->senderId = config('services.egosms.sender_id', 'PHILWIL');
    }

    /**
     * Send SMS - FIXED: Remove urlencode from message
     */
    public function sendSms($number, $message, $priority = '0')
    {
        $formattedNumber = $this->formatPhoneNumber($number);
        
        // IMPORTANT: Remove urlencode() from message
        $data = [
            'method' => 'SendSms',
            'userdata' => [
                'username' => $this->username,
                'password' => $this->password
            ],
            'msgdata' => [
                [
                    'number' => $formattedNumber,
                    'message' => $message, // Changed: removed urlencode()
                    'senderid' => $this->senderId,
                    'priority' => $priority
                ]
            ]
        ];

        return $this->makeRequest($data);
    }

    /**
     * Send multiple SMS messages
     */
    public function sendBulkSms($messages)
    {
        $msgdata = [];
        
        foreach ($messages as $message) {
            $msgdata[] = [
                'number' => $this->formatPhoneNumber($message['number']),
                'message' => $message['message'], // Changed: removed urlencode()
                'senderid' => $this->senderId,
                'priority' => $message['priority'] ?? '0'
            ];
        }

        $data = [
            'method' => 'SendSms',
            'userdata' => [
                'username' => $this->username,
                'password' => $this->password
            ],
            'msgdata' => $msgdata
        ];

        return $this->makeRequest($data);
    }

    /**
     * Make HTTP request to EgoSMS API
     */
    private function makeRequest($data)
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, $data);

            $result = $response->json();

            // Log the response
            Log::info('EgoSMS API Response', [
                'data_sent' => $this->maskLogData($data),
                'response' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('EgoSMS API Error', [
                'error' => $e->getMessage(),
                'data' => $this->maskLogData($data)
            ]);

            return [
                'Status' => 'Failed',
                'Message' => $e->getMessage()
            ];
        }
    }

    /**
     * Mask sensitive data for logging
     */
    private function maskLogData($data)
    {
        $masked = $data;
        
        // Mask password in logs
        if (isset($masked['userdata']['password'])) {
            $masked['userdata']['password'] = '***';
        }
        
        // Mask phone numbers in logs
        if (isset($masked['msgdata'])) {
            foreach ($masked['msgdata'] as &$msg) {
                if (isset($msg['number'])) {
                    $msg['number'] = $this->maskPhoneNumber($msg['number']);
                }
            }
        }
        
        return $masked;
    }

    /**
     * Format phone number to EgoSMS format (256...)
     */
    private function formatPhoneNumber($number)
    {
        $number = preg_replace('/\D/', '', $number);
        
        // If number starts with 0, replace with 256
        if (substr($number, 0, 1) === '0') {
            $number = '256' . substr($number, 1);
        }
        
        // If number starts with +256, remove the +
        if (substr($number, 0, 4) === '+256') {
            $number = substr($number, 1);
        }
        
        // Ensure it's exactly 12 digits (256XXXXXXXXX)
        if (strlen($number) === 9) {
            $number = '256' . $number;
        }

        return $number;
    }

    /**
     * Check if SMS was sent successfully
     */
    public function isSuccess($response)
    {
        return isset($response['Status']) && $response['Status'] === 'OK';
    }

    /**
     * Mask phone number for logs (privacy protection)
     */
    private function maskPhoneNumber($phone)
    {
        if (empty($phone) || strlen($phone) < 6) {
            return '***';
        }
        
        // Show only first 3 and last 3 digits: 256707208914 → 256***914
        $prefix = substr($phone, 0, 3);
        $suffix = substr($phone, -3);
        
        return $prefix . '***' . $suffix;
    }
}
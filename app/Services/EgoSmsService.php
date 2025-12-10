<?php
// app/Services/EgoSmsService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
     * Send SMS
     */
    public function sendSms($number, $message, $priority = '0')
    {
        $formattedNumber = $this->formatPhoneNumber($number);
        
        if (!$formattedNumber) {
            Log::error('Invalid phone number format', ['number' => $number]);
            return ['Status' => 'Failed', 'Message' => 'Invalid phone number format'];
        }
        
        // Truncate message to 160 characters if longer
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }
        
        $data = [
            'method' => 'SendSms',
            'userdata' => [
                'username' => $this->username,
                'password' => $this->password
            ],
            'msgdata' => [
                [
                    'number' => $formattedNumber,
                    'message' => $message,
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
            $formattedNumber = $this->formatPhoneNumber($message['number']);
            
            if (!$formattedNumber) {
                Log::warning('Skipping invalid phone number', ['number' => $message['number']]);
                continue;
            }
            
            // Truncate message
            $smsMessage = $message['message'];
            if (strlen($smsMessage) > 160) {
                $smsMessage = substr($smsMessage, 0, 157) . '...';
            }
            
            $msgdata[] = [
                'number' => $formattedNumber,
                'message' => $smsMessage,
                'senderid' => $this->senderId,
                'priority' => $message['priority'] ?? '0'
            ];
        }

        if (empty($msgdata)) {
            return ['Status' => 'Failed', 'Message' => 'No valid messages to send'];
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
            // Check cache for recent failures to avoid spamming API
            $cacheKey = 'egosms_last_failure';
            if (Cache::has($cacheKey)) {
                $lastFailure = Cache::get($cacheKey);
                if (now()->diffInMinutes($lastFailure) < 5) {
                    Log::warning('EgoSMS API recently failed, skipping request');
                    return ['Status' => 'Failed', 'Message' => 'API temporarily unavailable'];
                }
            }
            
            $response = Http::timeout(30)
                ->retry(3, 1000) // Retry 3 times with 1 second delay
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($this->baseUrl, $data);

            if (!$response->successful()) {
                Cache::put($cacheKey, now(), 5); // Cache failure for 5 minutes
                Log::error('EgoSMS API HTTP error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'Status' => 'Failed',
                    'Message' => 'HTTP error: ' . $response->status()
                ];
            }

            $result = $response->json();

            // Log the response (mask sensitive data)
            Log::info('EgoSMS API Response', [
                'status' => $result['Status'] ?? 'Unknown',
                'message' => $result['Message'] ?? 'No message',
                'data_sent' => $this->maskLogData($data)
            ]);

            // Clear failure cache on success
            Cache::forget($cacheKey);

            return $result;

        } catch (\Exception $e) {
            Cache::put($cacheKey, now(), 5);
            Log::error('EgoSMS API Exception', [
                'error' => $e->getMessage(),
                'data' => $this->maskLogData($data)
            ]);

            return [
                'Status' => 'Failed',
                'Message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number to EgoSMS format (256...)
     */
    private function formatPhoneNumber($number)
    {
        if (empty($number)) {
            return false;
        }
        
        $number = preg_replace('/\D/', '', $number);
        
        // Remove leading 0 or +256
        if (substr($number, 0, 1) === '0') {
            $number = substr($number, 1);
        }
        
        if (substr($number, 0, 4) === '2560') {
            $number = '256' . substr($number, 4);
        }
        
        if (substr($number, 0, 4) === '+256') {
            $number = substr($number, 1);
        }
        
        // Ensure it's 256 followed by 9 digits
        if (strlen($number) === 9 && substr($number, 0, 1) !== '0') {
            $number = '256' . $number;
        }
        
        // Validate final format
        if (!preg_match('/^256[1-9][0-9]{8}$/', $number)) {
            return false;
        }
        
        return $number;
    }

    /**
     * Check if SMS was sent successfully
     */
    public function isSuccess($response)
    {
        return isset($response['Status']) && 
               strtoupper($response['Status']) === 'OK' &&
               (!isset($response['Error']) || $response['Error'] == 0);
    }

    /**
     * Get remaining SMS balance
     */
    public function getBalance()
    {
        $data = [
            'method' => 'GetBalance',
            'userdata' => [
                'username' => $this->username,
                'password' => $this->password
            ]
        ];

        try {
            $response = Http::post($this->baseUrl, $data);
            $result = $response->json();
            
            if (isset($result['Balance'])) {
                return (float) $result['Balance'];
            }
            
            return 0;
        } catch (\Exception $e) {
            Log::error('Failed to get SMS balance', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Mask sensitive data for logging
     */
    private function maskLogData($data)
    {
        $masked = $data;
        
        // Mask password
        if (isset($masked['userdata']['password'])) {
            $masked['userdata']['password'] = '******';
        }
        
        // Mask phone numbers
        if (isset($masked['msgdata'])) {
            foreach ($masked['msgdata'] as &$msg) {
                if (isset($msg['number'])) {
                    $msg['number'] = $this->maskPhoneNumber($msg['number']);
                }
                // Truncate long messages in logs
                if (isset($msg['message']) && strlen($msg['message']) > 50) {
                    $msg['message'] = substr($msg['message'], 0, 50) . '...';
                }
            }
        }
        
        return $masked;
    }

    /**
     * Mask phone number for logs
     */
    private function maskPhoneNumber($phone)
    {
        if (empty($phone) || strlen($phone) < 6) {
            return '***';
        }
        
        return substr($phone, 0, 3) . '***' . substr($phone, -3);
    }
}
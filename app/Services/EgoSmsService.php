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

public function sendSms($number, $message, $priority = '0')
{
    // Remove test mode and uncomment real code:
    $formattedNumber = $this->formatPhoneNumber($number);
    
    $data = [
        'method' => 'SendSms',
        'userdata' => [
            'username' => $this->username,
            'password' => $this->password
        ],
        'msgdata' => [
            [
                'number' => $formattedNumber,
                'message' => urlencode($message),
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
                'message' => urlencode($message['message']),
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
                'data_sent' => $data,
                'response' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('EgoSMS API Error', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'Status' => 'Failed',
                'Message' => $e->getMessage()
            ];
        }
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
 * Check if SMS was sent successfully - TEST MODE
 */
    public function isSuccess($response)
    {
        return isset($response['Status']) && $response['Status'] === 'OK';
    }
}
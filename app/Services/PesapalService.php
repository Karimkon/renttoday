<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PesapalService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;

    public function __construct()
    {
        $this->baseUrl = config('pesapal.base_url');
        $this->consumerKey = config('pesapal.consumer_key');
        $this->consumerSecret = config('pesapal.consumer_secret');
    }

    /**
     * Get access token from Pesapal
     */
    public function getAccessToken()
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/api/Auth/RequestToken', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['token'] ?? null;
            }

            Log::error('Pesapal Auth Failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Pesapal Auth Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Submit order to Pesapal
     */
   public function submitOrder(array $orderData)
{
    $token = $this->getAccessToken();
    if (!$token) {
        throw new \Exception('Failed to get Pesapal access token');
    }

    $response = Http::withToken($token)
        ->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])
        ->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $orderData);

    \Log::info('Pesapal submitOrder raw body:', ['body' => $response->body()]);

    if ($response->successful()) {
        return $response->json();
    }

    \Log::error('Pesapal Order Failed: ' . $response->body());
    return $response->json();
}



    /**
     * Get transaction status
     */
    public function getTransactionStatus($orderTrackingId)
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            throw new \Exception('Failed to get Pesapal access token');
        }

        $response = Http::withToken($token)->get($this->baseUrl . '/api/Transactions/GetTransactionStatus', [
            'orderTrackingId' => $orderTrackingId
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Pesapal Status Check Failed: ' . $response->body());
        return null;
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payment'); 
    }

    public function process(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'amount' => 'required|integer|min:10000',
            'payment_type' => 'required|string|in:bank_transfer,gopay',
        ]);

        $serverKey = config('services.midtrans.server_key'); 

        $orderId = 'ORDER-' . Str::uuid();

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->amount,
            ],
            'customer_details' => [
                'first_name' => $request->name,
                'email' => $request->email,
            ],
        ];

        if ($request->payment_type === 'bank_transfer') {
            $payload['payment_type'] = 'bank_transfer';
            $payload['bank_transfer'] = [
                'bank' => 'bca',
            ];
        } elseif ($request->payment_type === 'gopay') {
            $payload['payment_type'] = 'gopay';
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post('https://api.sandbox.midtrans.com/v2/charge', $payload);

        if ($response->failed()) {
            Log::error('Midtrans charge failed', ['response' => $response->json()]);
            return response()->json([
                'status' => 'error',
                'status_message' => $response->json()['status_message'] ?? 'Payment failed',
                'data' => $response->json(),
            ], 500);
        }

        $result = $response->json();

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Carbon;

use App\Http\Requests\StoreTransactionRequest;
use App\Models\Vcard;
use App\Models\Transaction;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\StoreTransactionTAESRequest;
use App\Http\Requests\UpdateTransactionRequest;
use Illuminate\Support\Facades\Http;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Transaction::class, 'transaction');
    }

    public function index()
    {
        return TransactionResource::collection(Transaction::all());
    }

    public function show(Transaction $transaction)
    {
        return new TransactionResource($transaction);
    }

    public function store(StoreTransactionRequest $request)
    {
        $dataToSave = $request->validated();

        try {
            $transaction = new Transaction();

            if (Gate::allows('admin')) {
                if ($dataToSave['payment_type'] == 'VCARD') {
                    throw new \Exception('Admins cannot make a transaction with payment type VCARD');
                }
                // ADMIN CREDIT TRANSACTION

                // Request debit transaction on the ext. API
                $extApiEndpoint = 'https://dad-202324-payments-api.vercel.app/api/debit';
                $requestData = [
                    'type' => $dataToSave['payment_type'],
                    'reference' => $dataToSave['payment_reference'],
                    'value' => $dataToSave['value']
                ];
                $response = Http::post($extApiEndpoint, $requestData);
                $responseData = $response->json();
                if ($responseData['status'] != 'valid') {
                    throw new \Exception('External API error: ' . $responseData['message']);
                }

                // Add credit transaction to the vcard
                $vcard = Vcard::where('phone_number', $dataToSave['vcard'])->first();
                $transaction->vcard = $vcard->phone_number;
                $transaction->date = Carbon::now()->toDateString();
                $transaction->datetime = Carbon::now();
                $transaction->type = 'C';
                $transaction->value = $dataToSave['value'];
                $transaction->old_balance = $vcard->balance;
                $transaction->new_balance = $vcard->balance + $dataToSave['value'];
                $transaction->payment_type = $dataToSave['payment_type'];
                $transaction->payment_reference = $dataToSave['payment_reference'];
                $transaction->custom_options = $dataToSave['custom_options'] ?? null;
                $transaction->custom_data = $dataToSave['custom_data'] ?? null;
                $transaction->save();
                $vcard->balance = $transaction->new_balance;
                $vcard->save();
            } else {
                // VCARD DEBIT TRANSACTION

                if ($dataToSave['payment_type'] != 'VCARD') {
                    // Request credit transaction on the ext. API
                    $extApiEndpoint = 'https://dad-202324-payments-api.vercel.app/api/credit';
                    $requestData = [
                        'type' => $dataToSave['payment_type'],
                        'reference' => $dataToSave['payment_reference'],
                        'value' => $dataToSave['value']
                    ];
                    $response = Http::post($extApiEndpoint, $requestData);
                    $responseData = $response->json();
                    if ($responseData['status'] != 'valid') {
                        throw new \Exception('External API error: ' . $responseData['message']);
                    }
                }
                // Add debit transaction to the vcard
                $vcard = Vcard::where('phone_number', $dataToSave['vcard'])->first();
                if ($dataToSave['payment_reference'] == $vcard->phone_number) {
                    throw new \Exception('Cannot make a transaction to yourself');
                }
                $transaction->vcard = $vcard->phone_number;
                $transaction->date = Carbon::now()->toDateString();
                $transaction->datetime = Carbon::now();
                $transaction->type = 'D';
                if ($dataToSave['value'] > $vcard->balance) {
                    throw new \Exception('Value must be inferior or equal to the vcard balance');
                }
                if ($dataToSave['value'] > $vcard->max_debit) {
                    throw new \Exception('Value must be inferior or equal to the vcard max debit');
                }
                $transaction->value = $dataToSave['value'];
                $transaction->old_balance = $vcard->balance;
                $transaction->new_balance = $vcard->balance - $dataToSave['value'];
                $transaction->payment_type = $dataToSave['payment_type'];
                $transaction->payment_reference = $dataToSave['payment_reference'];
                $transaction->custom_options = $dataToSave['custom_options'] ?? null;
                $transaction->custom_data = $dataToSave['custom_data'] ?? null;

                if ($dataToSave['payment_type'] == 'VCARD') {
                    // Create credit transaction on the pair_vcard
                    $pair_vcard = Vcard::where('phone_number', $dataToSave['payment_reference'])->first();
                    $pair_transaction = new Transaction();
                    $pair_transaction->vcard = $pair_vcard->phone_number;
                    $pair_transaction->date = Carbon::now()->toDateString();
                    $pair_transaction->datetime = Carbon::now();
                    $pair_transaction->type = 'C';
                    $pair_transaction->value = $dataToSave['value'];
                    $pair_transaction->old_balance = $pair_vcard->balance;
                    $pair_transaction->new_balance = $pair_vcard->balance + $dataToSave['value'];
                    $pair_transaction->payment_type = 'VCARD';
                    $pair_transaction->payment_reference = $vcard->phone_number;
                    $pair_transaction->pair_transaction = $transaction->id;
                    $pair_transaction->pair_vcard = $vcard->phone_number;
                    $pair_transaction->save();
                    $pair_vcard->balance = $pair_transaction->new_balance;
                    $pair_vcard->save();

                    $transaction->pair_transaction = $pair_transaction->id;
                    $transaction->pair_vcard = $pair_vcard->phone_number;
                }

                $transaction->save();
                $vcard->balance = $transaction->new_balance;
                $vcard->save();
            }

            return new TransactionResource($transaction);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction)
    {
        $dataToSave = $request->validated();
        $transaction->category_id = $dataToSave['category_id'] ?? $transaction->category_id;
        $transaction->date = $dataToSave['date'] ?? $transaction->date;

        $transaction->save();
        return new TransactionResource($transaction);
    }

    public function destroy(Transaction $transaction)
    {
        if (!$transaction->vcard->trashed()) {
            return response()->json(['message' => 'Cannot delete a transaction from a non-deleted vcard'], 400);
        }
        $transaction->delete();  
        return new TransactionResource($transaction);
    }


























    //-------TAES--------functions
    public function getTransactionsTAES(String $phone_number)
    {
        if (VCard::where('phone_number', $phone_number)->first() == "") {
            return response()->json(['message' => 'VCard not found'], 404);
        }
        $transactions = Transaction::where('vcard', $phone_number)->orderBy('id', 'desc')->get();

        TransactionResource::$format = 'TAES';
        return TransactionResource::collection($transactions);
    }

    public function storeTransactionTAES(StoreTransactionTAESRequest $request)
    {
        $dataToSave = $request->validated();

        if ($dataToSave['phone_number'] != $dataToSave['phone_number']) {
            return response()->json(['message' => "Phone Number from request don't match parameters"], 400);
        }

        $VCardSender = Vcard::where('phone_number', $dataToSave['phone_number'])->first();
        if (!isset($VCardSender)) {
            return response()->json(['message' => 'Not found phone_number of sender'], 404);
        }

        if ($VCardSender->custom_data == null) {
            $VCardSender->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234"
            );
            $VCardSender->save();
            //Refresh the $VCard because it breaks when tries to json_decode
            $VCardSender = Vcard::where('phone_number', $dataToSave['phone_number'])->first();
        }

        $VCardReceiver = Vcard::where('phone_number', $dataToSave['payment_reference'])->first();
        if (!isset($VCardReceiver)) {
            return response()->json(['message' => 'Not found phone_number of receiver'], 404);
        }

        if ($VCardSender->balance < $dataToSave['value']) {
            return response()->json(['message' => "Insufficient vcard balance"], 406);
        }

        if ($VCardSender->balance - json_decode($VCardSender->custom_data, true)['piggy_bank'] < $dataToSave['value']) {
            return response()->json(['message' => "Take some money out of the piggy bank"], 406);
        }

        if ($VCardReceiver->custom_data == null) {
            $VCardReceiver->custom_data = array(
                "piggy_bank" => "0",
                "pin" => "1234"
            );
            $VCardReceiver->save();
            //Refresh the $VCard because it breaks when tries to json_decode
            $VCardReceiver = Vcard::where('phone_number', $dataToSave['payment_reference'])->first();
        }

        //Transação do que envia o dinheiro
        $transaction = new Transaction();
        $transaction->vcard = $dataToSave['phone_number'];
        $transaction->date = date("Y-m-d");
        $transaction->datetime = date("Y-m-d") . " " . date("H:i:s");
        $transaction->type = 'D';
        $transaction->value = $dataToSave['value'];
        $transaction->old_balance = $VCardSender->balance;
        $transaction->new_balance = $VCardSender->balance -= $dataToSave['value'];
        $transaction->payment_type = 'VCARD';
        $transaction->payment_reference = $dataToSave['payment_reference'];
        //$transaction->pair_transaction = 2;
        $transaction->pair_vcard = $dataToSave['payment_reference'];
        if (isset($dataToSave['description'])) {
            $transaction->description = $dataToSave['description'];
        }

        //
        $transactionReceiver = new Transaction();
        $transactionReceiver->vcard = $dataToSave['payment_reference'];
        $transactionReceiver->date = date("Y-m-d");
        $transactionReceiver->datetime = date("Y-m-d") . " " . date("H:i:s");
        $transactionReceiver->type = 'C';
        $transactionReceiver->value = $dataToSave['value'];
        $transactionReceiver->old_balance = $VCardReceiver->balance;
        $transactionReceiver->new_balance = $VCardReceiver->balance += $dataToSave['value'];
        $transactionReceiver->payment_type = 'VCARD';
        $transactionReceiver->payment_reference = $dataToSave['phone_number'];
        $transactionReceiver->pair_vcard = $dataToSave['phone_number'];
        if (isset($dataToSave['description'])) {
            $transactionReceiver->description = $dataToSave['description'];
        }

        //guarda os dados na base de dados
        $transaction->save();
        $transactionReceiver->save();

        $VCardReceiver->save();
        $VCardSender->save();

        $VCardSender = Vcard::where('phone_number', $dataToSave['phone_number'])->first();

        return response()->json([
            'message' => 'Transaction successful',
            'phone_number' => $transaction->vcard,
            'old_balance' => $transaction->old_balance,
            'new_balance' => $transaction->new_balance,
            'piggy_bank_balance' => json_decode($VCardSender->custom_data, true)['piggy_bank'],
            'description' => $transaction->description
        ], 200);
    }
}

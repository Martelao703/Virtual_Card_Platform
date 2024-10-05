<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Vcard;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    public function getAmountSpentByCategory(Vcard $vcard)
    {
        $categories = $vcard->categories()->where('vcard', $vcard->phone_number)->get();
        $amountSpentByCategory = [];
        $transactions = $vcard->transactions()->where('vcard', $vcard->phone_number)->get();

        // Check if categories array is not empty
        if ($transactions->isNotEmpty()) {
            foreach ($categories as $category) {
                $amount = $vcard->transactions()->where('category_id', $category->id)->where('type', 'D')->sum('value');

                if ($amount > 0) {
                    $amountSpentByCategory['labels'][] = $category->name;
                    $amountSpentByCategory['data'][] = $amount;
                }
            }
        } else {
            $amountSpentByCategory['labels'][] = 'No categories';
            $amountSpentByCategory['data'][] = 0;
        }


        return $amountSpentByCategory;
    }


    public function getAmountSpentByMonth(Vcard $vcard)
    {
        $currentYear = now()->year;

        $amountSpentByMonth = Transaction::where('vcard', $vcard->phone_number)->where('type','D')->whereYear('datetime', $currentYear)
            ->select(
                DB::raw('MONTH(datetime) as month'),
                DB::raw('SUM(value) as amount')
            )->groupBy(DB::raw('MONTH(datetime)'))->get();

        $labels = [];
        $data = [];

        foreach ($amountSpentByMonth as $entry) {
            // Assuming you want month names, you can format the month using Carbon
            $monthName = Carbon::create()->month($entry->month)->format('F');

            $labels[] = $monthName;
            $data[] = $entry->amount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function lastTransactions(Vcard $vcard)
    {
        $transactions = $vcard->transactions()
            ->with('pairVCard') // Eager load the pairVCard relationship
            ->orderBy('datetime', 'desc')
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'value' => $transaction->value,
                    'type' => $transaction->type,
                    'payment_type' => $transaction->payment_type,
                    'vcard_name' => $transaction->pairVCard ? $transaction->pairVCard->name : $transaction->payment_reference,
                    'date' => $transaction->datetime, // Format the date as needed
                ];
            });

        return $transactions;
    }


    //current count of active vCards, sum of current vCard
    //balances, count or sum of all transactions in a specific time frame, total transactions by type of
    //payment, etc.

    public function currentActiveVcards()
    {
        $currentActiveVcards = Vcard::where('deleted_at', null)->count();
        return $currentActiveVcards;
    }

    public function sumOfCurrentVcardBalances()
    {
        $sumOfCurrentVcardBalances = Vcard::where('deleted_at', null)->sum('balance');
        return $sumOfCurrentVcardBalances;
    }

    public function totalTransactionsByPaymentType()
    {
        $totalTransactionsByPaymentType = Transaction::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->get();
        return $totalTransactionsByPaymentType;
    }

    public function sumOfTransactionsByDate(Request $request)
    {
        $query = Transaction::where('type', 'D');

        $filterByDateStart = $request->date_start ?? '';
        $filterByDateFinish = $request->date_finish ?? '';

        if ($filterByDateStart != "") {
            $query->where('date', '>=', $filterByDateStart);
        }

        if ($filterByDateFinish != "") {
            $query->where('date', '<=', $filterByDateFinish);
        }

        $sum = $query->sum('value');

        return response()->json(['sum' => $sum]);
    }
}

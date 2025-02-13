<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class QuotationController extends Controller
{
    public function getQuotation(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'age' => 'required|string',
            'currency_id' => 'required|in:EUR,GBP,USD',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Extract request parameters
        $ages = explode(',', $request->input('age'));
        $currencyId = $request->input('currency_id');
        $startDate = Carbon::createFromFormat('Y-m-d', $request->input('start_date'));
        $endDate = Carbon::createFromFormat('Y-m-d', $request->input('end_date'));

        // Calculate trip length
        $tripLength = $startDate->diffInDays($endDate) + 1;

        // Fixed rate per day
        $fixedRate = 3;

        // Age load table
        $ageLoadTable = [
            [18, 30, 0.6],
            [31, 40, 0.7],
            [41, 50, 0.8],
            [51, 60, 0.9],
            [61, 70, 1.0]
        ];

        // Calculate total price
        $total = 0;
        foreach ($ages as $age) {
            $age = (int)$age;
            $ageLoad = $this->getAgeLoad($age, $ageLoadTable);
            if ($ageLoad === null) {
                return response()->json(['error' => "Invalid age: $age"], 422);
            }
            $total += $fixedRate * $ageLoad * $tripLength;
        }

        // Create response
        $response = [
            'total' => number_format($total, 2),
            'currency_id' => $currencyId,
            'quotation_id' => rand(1, 1000) // For example, generating a random ID
        ];

        return response()->json($response);
    }

    private function getAgeLoad($age, $ageLoadTable)
    {
        foreach ($ageLoadTable as $range) {
            if ($age >= $range[0] && $age <= $range[1]) {
                return $range[2];
            }
        }
        return null;
    }
}

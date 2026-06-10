<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Models\Visit;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(private ReceiptService $receiptService) {}

    public function preview(Visit $visit)
    {
        $html = $this->receiptService->generateHtml($visit->load(['patient', 'doctor.user', 'services.service']));
        return response($html)->header('Content-Type', 'text/html');
    }

    public function print(Request $request, Visit $visit)
    {
        $request->validate([
            'printer_ip' => 'required|ip',
            'printer_port' => 'nullable|integer',
        ]);

        $receipt = $this->receiptService->createReceipt($visit);
        $success = $this->receiptService->printReceipt(
            $visit,
            $request->printer_ip,
            $request->printer_port ?? 9100
        );

        return response()->json([
            'success' => $success,
            'receipt_number' => $receipt->receipt_number,
        ]);
    }
}

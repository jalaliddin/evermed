<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Visit;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class ReceiptService
{
    public function createReceipt(Visit $visit): Receipt
    {
        $existing = $visit->receipt;
        if ($existing) return $existing;

        $number = 'RCP-' . now()->format('Y') . '-' . str_pad($visit->id, 6, '0', STR_PAD_LEFT);
        return Receipt::create([
            'visit_id' => $visit->id,
            'receipt_number' => $number,
            'amount' => $visit->paid_amount,
            'printed_at' => now(),
        ]);
    }

    public function generateHtml(Visit $visit): string
    {
        $visit->loadMissing(['patient', 'doctor.user', 'services.service', 'inventory.item']);

        $clinicName = tenant('name') ?? 'EverMED CRM';
        $receipt = $this->createReceipt($visit);

        $servicesHtml = '';
        foreach ($visit->services as $vs) {
            $servicesHtml .= "<tr>
                <td>{$vs->service->name}</td>
                <td style='text-align:center'>{$vs->quantity}</td>
                <td style='text-align:right'>" . number_format($vs->total, 0, '.', ' ') . "</td>
            </tr>";
        }

        $inventoryHtml = '';
        if ($visit->inventory && $visit->inventory->isNotEmpty()) {
            $inventoryHtml .= "<tr><td colspan='3'><div class='divider'></div></td></tr>";
            $inventoryHtml .= "<tr><td colspan='3' style='font-size:10px;color:#666;padding-top:4px'>SARFLANGAN MATERIAL:</td></tr>";
            foreach ($visit->inventory as $inv) {
                $invTotal = $inv->total_price > 0
                    ? number_format($inv->total_price, 0, '.', ' ')
                    : $inv->item->unit;
                $inventoryHtml .= "<tr style='color:#555'>
                    <td>{$inv->item->name} ×{$inv->quantity_used}</td>
                    <td></td>
                    <td style='text-align:right'>{$invTotal}</td>
                </tr>";
            }
        }

        $discount = $visit->discount > 0 ? "<tr><td colspan='2'>Chegirma:</td><td style='text-align:right'>-" . number_format($visit->discount, 0, '.', ' ') . "</td></tr>" : '';

        return "<!DOCTYPE html>
<html>
<head>
<meta charset='UTF-8'>
<style>
body { font-family: monospace; max-width: 300px; margin: 0 auto; padding: 10px; }
.center { text-align: center; }
.divider { border-top: 1px dashed #000; margin: 8px 0; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
.total { font-weight: bold; font-size: 14px; }
@media print { body { margin: 0; padding: 0; } }
</style>
</head>
<body>
<div class='center'>
<h3 style='margin:0'>{$clinicName}</h3>
<small>EverMED CRM</small>
</div>
<div class='divider'></div>
<p style='font-size:11px;margin:4px 0'>Chek #: {$receipt->receipt_number}</p>
<p style='font-size:11px;margin:4px 0'>Sana: " . now()->format('d.m.Y H:i') . "</p>
<div class='divider'></div>
<p style='font-size:11px;margin:4px 0'>Bemor: {$visit->patient->full_name}</p>
<p style='font-size:11px;margin:4px 0'>Shifokor: Dr. {$visit->doctor->user->name}</p>
<div class='divider'></div>
<table>
<tr><th style='text-align:left'>Xizmat</th><th>Soni</th><th style='text-align:right'>Jami</th></tr>
{$servicesHtml}
{$inventoryHtml}
<tr><td colspan='3'><div class='divider'></div></td></tr>
<tr><td colspan='2'>Jami:</td><td style='text-align:right'>" . number_format($visit->total_amount, 0, '.', ' ') . "</td></tr>
{$discount}
<tr class='total'><td colspan='2'>TO'LOV:</td><td style='text-align:right'>" . number_format($visit->paid_amount, 0, '.', ' ') . "</td></tr>
<tr><td colspan='2'>Usul:</td><td style='text-align:right'>" . ucfirst($visit->payment_method) . "</td></tr>
</table>
<div class='divider'></div>
<div class='center'><p>Sog'lom bo'ling!</p></div>
</body>
</html>";
    }

    public function printReceipt(
        Visit $visit,
        string $printerType = 'network',
        string $printerHost = '',
        int $printerPort = 9100,
        string $printerPath = '/dev/usb/lp0'
    ): bool {
        try {
            $visit->loadMissing(['patient', 'doctor.user', 'services.service', 'inventory.item']);

            $connector = match ($printerType) {
                'usb'   => new FilePrintConnector($printerPath),
                default => new NetworkPrintConnector($printerHost, $printerPort),
            };
            $printer = new Printer($connector);

            $clinicName = tenant('name') ?? 'EverMED CRM';
            $receipt = $this->createReceipt($visit);

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(1, 1);
            $printer->text($clinicName . "\n");
            $printer->text("================================\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Chek #: {$receipt->receipt_number}\n");
            $printer->text("Sana: " . now()->format('d.m.Y H:i') . "\n");
            $printer->text("--------------------------------\n");
            $printer->text("Bemor: {$visit->patient->full_name}\n");
            $printer->text("Shifokor: Dr. {$visit->doctor->user->name}\n");
            $printer->text("--------------------------------\n");
            $printer->text("XIZMATLAR:\n");

            foreach ($visit->services as $vs) {
                $line = $vs->service->name;
                $price = number_format($vs->total, 0);
                $printer->text(str_pad($line, 20) . str_pad($price, 10, ' ', STR_PAD_LEFT) . "\n");
            }

            if ($visit->inventory && $visit->inventory->count() > 0) {
                $printer->text("--------------------------------\n");
                $printer->text("SARFLANGAN MATERIAL:\n");
                foreach ($visit->inventory as $inv) {
                    $name  = mb_substr($inv->item->name, 0, 18);
                    $price = $inv->total_price > 0
                        ? number_format((float) $inv->total_price, 0)
                        : $inv->quantity_used . ' ' . $inv->item->unit;
                    $printer->text(str_pad($name, 20) . str_pad($price, 10, ' ', STR_PAD_LEFT) . "\n");
                }
            }

            $printer->text("--------------------------------\n");
            $printer->text("Jami: " . str_pad(number_format($visit->total_amount, 0), 23, ' ', STR_PAD_LEFT) . "\n");
            if ($visit->discount > 0) {
                $printer->text("Chegirma: " . str_pad('-' . number_format($visit->discount, 0), 20, ' ', STR_PAD_LEFT) . "\n");
            }
            $printer->text("TO'LOV: " . str_pad(number_format($visit->paid_amount, 0), 21, ' ', STR_PAD_LEFT) . "\n");
            $printer->text("Usul: " . ucfirst($visit->payment_method) . "\n");
            $printer->text("================================\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Sog'lom bo'ling!\n");
            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return true;
        } catch (\Exception $e) {
            Log::error('Printer error: ' . $e->getMessage());
            return false;
        }
    }
}

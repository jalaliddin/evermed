<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private string $type,
        private array $data
    ) {}

    public function array(): array
    {
        return match($this->type) {
            'financial' => collect($this->data['daily'] ?? [])->map(fn($row) => [
                $row['date'] ?? '',
                $row['patients'] ?? 0,
                $row['revenue'] ?? 0,
                $row['discount'] ?? 0,
            ])->toArray(),
            'doctors' => collect($this->data['doctors'] ?? [])->map(fn($row) => [
                $row['doctor']['user']['name'] ?? '',
                $row['visits_count'] ?? 0,
                $row['revenue'] ?? 0,
                $row['avg_check'] ?? 0,
            ])->toArray(),
            'services' => collect($this->data['services'] ?? [])->map(fn($row) => [
                $row['name'] ?? '',
                $row['count'] ?? 0,
                $row['revenue'] ?? 0,
            ])->toArray(),
            default => [],
        };
    }

    public function headings(): array
    {
        return match($this->type) {
            'financial' => ['Sana', 'Bemorlar', 'Daromad', 'Chegirma'],
            'doctors' => ['Shifokor', 'Qabullar', 'Daromad', 'O\'rtacha chek'],
            'services' => ['Xizmat', 'Soni', 'Daromad'],
            default => [],
        };
    }

    public function title(): string
    {
        return match($this->type) {
            'financial' => 'Moliyaviy hisobot',
            'doctors' => 'Shifokorlar hisoboti',
            'services' => 'Xizmatlar hisoboti',
            default => 'Hisobot',
        };
    }
}

<?php

namespace App\Exports;

use App\Models\Refund;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RefundsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function collection(): Collection
    {
        return Refund::query()
            ->with([
                'order:id,order_number',
                'requestedBy:id,name',
                'processedBy:id,name',
            ])
            ->latest()
            ->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Order Number',
            'Requested By',
            'Reason',
            'Refund Amount',
            'Status',
            'Admin Remarks',
            'Processed By',
            'Processed At',
            'Created At',
        ];
    }

    /**
     * @param  Refund  $refund
     * @return list<mixed>
     */
    public function map($refund): array
    {
        return [
            $refund->id,
            $refund->order?->order_number,
            $refund->requestedBy?->name,
            $refund->reason,
            (float) $refund->refund_amount,
            $refund->status,
            $refund->admin_remarks,
            $refund->processedBy?->name,
            optional($refund->processed_at)->format('Y-m-d H:i'),
            optional($refund->created_at)->format('Y-m-d H:i'),
        ];
    }
}

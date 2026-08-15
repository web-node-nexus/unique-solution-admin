<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected ?Carbon $from = null,
        protected ?Carbon $to = null
    ) {
        $this->from ??= Carbon::now()->subDays(30)->startOfDay();
        $this->to ??= Carbon::now()->endOfDay();
    }

    public function collection(): Collection
    {
        return Order::query()
            ->with('user:id,name,email')
            ->whereBetween('created_at', [$this->from, $this->to])
            ->whereNotIn('order_status', ['cancelled'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Order Number',
            'Customer',
            'Email',
            'Subtotal',
            'Discount',
            'Tax',
            'Shipping',
            'Total',
            'Payment Status',
            'Order Status',
            'Date',
        ];
    }

    /**
     * @param  Order  $order
     * @return list<mixed>
     */
    public function map($order): array
    {
        return [
            $order->order_number,
            $order->user?->name,
            $order->user?->email,
            (float) $order->subtotal,
            (float) $order->discount,
            (float) $order->tax,
            (float) $order->shipping_charge,
            (float) $order->total_amount,
            $order->payment_status,
            $order->order_status,
            optional($order->created_at)->format('Y-m-d H:i'),
        ];
    }
}

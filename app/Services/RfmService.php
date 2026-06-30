<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RfmService
{
    /**
     * Jalankan analisis RFM untuk pelanggan satu tenant
     */
    public function analyze(int $tenantId): array
    {
        // Ambil semua order dari tenant yang statusnya valid (bukan UNPAID / CANCELLED)
        $orders = Order::where('tenant_id', $tenantId)
            ->whereNotIn('order_status', [Order::STATUS_UNPAID, Order::STATUS_CANCELLED])
            ->get()
            ->groupBy('customer_id');

        $champions = [];
        $loyal = [];
        $atRisk = [];
        $hibernating = [];
        $newCustomers = [];

        foreach ($orders as $customerId => $customerOrders) {
            $customer = Customer::find($customerId);
            if (!$customer || empty($customer->phone)) {
                continue;
            }

            // Hitung Recency (hari sejak order terakhir)
            $lastOrderDate = $customerOrders->max('order_date');
            $recencyDays = Carbon::parse($lastOrderDate)->diffInDays(now());

            // Hitung Frequency (jumlah order)
            $frequency = $customerOrders->count();

            // Hitung Monetary (total pembelanjaan)
            $monetary = (float) $customerOrders->sum('net_amount');

            // Klasifikasi Segmentasi Pelanggan
            if ($recencyDays <= 30 && $frequency >= 4) {
                $segment = 'Champions';
                $champions[] = $this->formatCustomerData($customer, $recencyDays, $frequency, $monetary, $segment);
            } elseif ($recencyDays <= 60 && $frequency >= 2) {
                $segment = 'Loyal';
                $loyal[] = $this->formatCustomerData($customer, $recencyDays, $frequency, $monetary, $segment);
            } elseif ($recencyDays > 60 && $frequency >= 3) {
                $segment = 'At Risk';
                $atRisk[] = $this->formatCustomerData($customer, $recencyDays, $frequency, $monetary, $segment);
            } elseif ($recencyDays > 90 && $frequency <= 2) {
                $segment = 'Hibernating';
                $hibernating[] = $this->formatCustomerData($customer, $recencyDays, $frequency, $monetary, $segment);
            } else {
                $segment = 'New Customers';
                $newCustomers[] = $this->formatCustomerData($customer, $recencyDays, $frequency, $monetary, $segment);
            }
        }

        return [
            'Champions' => collect($champions)->sortByDesc('monetary'),
            'Loyal' => collect($loyal)->sortByDesc('monetary'),
            'At Risk' => collect($atRisk)->sortByDesc('monetary'),
            'Hibernating' => collect($hibernating)->sortByDesc('monetary'),
            'New Customers' => collect($newCustomers)->sortByDesc('monetary'),
        ];
    }

    private function formatCustomerData(Customer $customer, int $recency, int $frequency, float $monetary, string $segment): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'recency' => $recency,
            'frequency' => $frequency,
            'monetary' => $monetary,
            'segment' => $segment,
        ];
    }
}

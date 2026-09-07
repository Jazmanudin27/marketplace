<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\Expense;
use App\Models\Income;
use App\Models\FundTransfer;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialReportController extends Controller
{
    public function profitLoss(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filter tanggal default: 30 hari terakhir
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        // ==========================================
        // 1. REVENUE (PENDAPATAN)
        // ==========================================
        
        // A. Online Sales (Marketplace)
        $onlineOrders = Order::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->whereNotIn('order_status', ['CANCELLED'])
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $onlineRevenue = (float) $onlineOrders->sum('net_amount'); // Pencairan Bersih
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $order) {
            $onlineHpp += $order->hpp_total;
        }

        // B. Offline Sales
        $offlineSales = OfflineSale::with('items.masterProduct')
            ->where('tenant_id', $tenantId)
            ->where('status', OfflineSale::STATUS_COMPLETED)
            ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $offlineRevenue = (float) $offlineSales->sum('grand_total');
        $offlineHpp = 0.0;
        foreach ($offlineSales as $sale) {
            $offlineHpp += $sale->hpp_total;
        }

        // C. Pemasukan Lain-Lain
        $otherIncomes = Income::where('tenant_id', $tenantId)
            ->whereBetween('income_date', [$dateFrom, $dateTo])
            ->get();

        $totalOtherIncome = (float) $otherIncomes->sum('amount');

        // Total Pendapatan & HPP
        $totalSalesRevenue = $onlineRevenue + $offlineRevenue;
        $totalHpp = $onlineHpp + $offlineHpp;
        $grossProfit = $totalSalesRevenue - $totalHpp;

        // ==========================================
        // 2. OPERATING EXPENSES (PENGELUARAN OPERASIONAL)
        // ==========================================
        $expenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->get();

        // Load dynamic finance categories
        $allExpenseCats = FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        if ($allExpenseCats->isEmpty()) {
            FinanceCategory::seedDefaultsForTenant($tenantId);
            $allExpenseCats = FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        }

        $defaultLabels = [
            'salary'               => 'Gaji Karyawan',
            'rent'                 => 'Sewa Tempat',
            'utilities'            => 'Utilitas & Operasional',
            'pembelian_supplier'   => 'Bayar Hutang Supplier',
            'other'                => 'Lain-lain',
        ];

        $expensesCategoryList = [];
        $groupedExpenses = $expenses->groupBy('category');

        foreach ($allExpenseCats as $cat) {
            $catAmount = (float) ($groupedExpenses[$cat->code] ?? collect())->sum('amount');
            $expensesCategoryList[$cat->code] = [
                'name'   => $cat->name,
                'amount' => $catAmount,
            ];
        }

        foreach ($groupedExpenses as $catCode => $group) {
            if (!isset($expensesCategoryList[$catCode])) {
                $catName = $defaultLabels[$catCode] ?? ucwords(str_replace('_', ' ', $catCode));
                $expensesCategoryList[$catCode] = [
                    'name'   => $catName,
                    'amount' => (float) $group->sum('amount'),
                ];
            }
        }

        // Backward-compatible array keyed by code
        $expensesByCategory = [];
        foreach ($expensesCategoryList as $code => $item) {
            $expensesByCategory[$code] = $item['amount'];
        }

        $totalExpenses = (float) $expenses->sum('amount');

        // ==========================================
        // 3. NET PROFIT (LABA BERSIH)
        // ==========================================
        $netProfit = $grossProfit + $totalOtherIncome - $totalExpenses;

        // Margin Persentase
        $netRevenueWithOther = $totalSalesRevenue + $totalOtherIncome;
        $profitMargin = $netRevenueWithOther > 0 ? round(($netProfit / $netRevenueWithOther) * 100, 2) : 0;

        // ==========================================
        // 4. CASH POOLS BALANCES (AKUMULASI CUMULATIVE)
        // ==========================================
        // Hitung saldo Kas Besar & Kas Kecil secara kumulatif (seluruh waktu)
        
        // Pemasukan & Pengeluaran
        $cumIncomesKasBesar = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_besar')->sum('amount');
        $cumIncomesKasKecil = (float) Income::where('tenant_id', $tenantId)->where('payment_destination', 'kas_kecil')->sum('amount');

        $cumExpensesKasBesar = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_besar')->sum('amount');
        $cumExpensesKasKecil = (float) Expense::where('tenant_id', $tenantId)->where('payment_source', 'kas_kecil')->sum('amount');

        // Transfer dana
        $cumTransfersToBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_besar')->sum('amount');
        $cumTransfersFromBesar = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_besar')->sum('amount');

        $cumTransfersToKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('destination', 'kas_kecil')->sum('amount');
        $cumTransfersFromKecil = (float) FundTransfer::where('tenant_id', $tenantId)->where('source', 'kas_kecil')->sum('amount');

        // Penjualan Online (masuk ke Kas Besar / Rekening Bank Utama)
        $cumOnlineSales = (float) Order::where('tenant_id', $tenantId)->where('order_status', 'COMPLETED')->sum('net_amount');

        // Penjualan Offline:
        // Cash (tunai) masuk ke Kas Kecil, non-cash (transfer, qris, kartu) masuk ke Kas Besar
        $cumOfflineSalesTunai = (float) OfflineSale::where('tenant_id', $tenantId)->where('status', OfflineSale::STATUS_COMPLETED)->where('payment_method', 'tunai')->sum('grand_total');
        $cumOfflineSalesNonTunai = (float) OfflineSale::where('tenant_id', $tenantId)->where('status', OfflineSale::STATUS_COMPLETED)->whereIn('payment_method', ['transfer', 'qris', 'kartu'])->sum('grand_total');

        // Saldo akhir
        $balanceKasBesar = $cumIncomesKasBesar + $cumOnlineSales + $cumOfflineSalesNonTunai + $cumTransfersToBesar - $cumExpensesKasBesar - $cumTransfersFromBesar;
        $balanceKasKecil = $cumIncomesKasKecil + $cumOfflineSalesTunai + $cumTransfersToKecil - $cumExpensesKasKecil - $cumTransfersFromKecil;

        return view('finance.profit_loss', compact(
            'dateFrom',
            'dateTo',
            'onlineRevenue',
            'onlineHpp',
            'offlineRevenue',
            'offlineHpp',
            'totalOtherIncome',
            'totalSalesRevenue',
            'totalHpp',
            'grossProfit',
            'expensesByCategory',
            'expensesCategoryList',
            'totalExpenses',
            'netProfit',
            'profitMargin',
            'balanceKasBesar',
            'balanceKasKecil',
            'otherIncomes',
            'expenses'
        ));
    }

    /**
     * Laporan Detail Mutasi Masuk dan Keluar Keuangan
     */
    public function mutationsReport(Request $request)
    {
        $data = $this->buildMutationsData($request);
        return view('finance.mutations', $data);
    }

    public function printMutationsReport(Request $request)
    {
        $data = $this->buildMutationsData($request);
        return view('finance.mutations_print', $data);
    }

    public function exportMutationsReport(Request $request)
    {
        $data = $this->buildMutationsData($request);
        $filename = 'mutasi_keuangan_' . $data['dateFrom'] . '_sd_' . $data['dateTo'] . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN DETAIL MUTASI MASUK & KELUAR KEUANGAN']);
            fputcsv($handle, ['Periode', $data['dateFrom'] . ' s/d ' . $data['dateTo']]);
            fputcsv($handle, ['Filter Akun', $data['selectedAccountLabel']]);
            fputcsv($handle, ['Filter Arah', $data['direction'] === 'all' ? 'Semua (Masuk & Keluar)' : ($data['direction'] === 'in' ? 'Uang Masuk' : 'Uang Keluar')]);
            fputcsv($handle, ['Saldo Awal (Rp)', $data['beginningBalance']]);
            fputcsv($handle, ['Total Uang Masuk (Rp)', $data['totalInflow']]);
            fputcsv($handle, ['Total Uang Keluar (Rp)', $data['totalOutflow']]);
            fputcsv($handle, ['Net Cashflow (Rp)', $data['netCashFlow']]);
            fputcsv($handle, ['Saldo Akhir (Rp)', $data['endingBalance']]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'No',
                'Tanggal',
                'No. Referensi',
                'Jenis Transaksi',
                'Akun Kas / Bank',
                'Kategori',
                'Keterangan',
                'Uang Masuk (Rp)',
                'Uang Keluar (Rp)',
                'Saldo Berjalan (Rp)',
            ]);

            $no = 1;
            foreach ($data['mutations'] as $row) {
                fputcsv($handle, [
                    $no++,
                    $row['date_formatted'],
                    $row['reference'],
                    $row['type_label'],
                    $row['account_label'],
                    $row['category_label'],
                    $row['description'],
                    $row['inflow'] > 0 ? $row['inflow'] : 0,
                    $row['outflow'] > 0 ? $row['outflow'] : 0,
                    $row['running_balance'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function buildMutationsData(Request $request): array
    {
        $tenantId = Auth::user()->tenant_id;

        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());
        $account  = $request->get('account', 'all');
        $direction = $request->get('direction', 'all');
        $sourceType = $request->get('source_type', 'all');
        $search   = trim($request->get('search', ''));

        // Load active bank accounts from master database
        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        $resolveAccountLabel = function ($val) use ($bankAccounts) {
            if (!$val || $val === 'all') {
                return 'Semua Akun Kas / Bank';
            }

            $bank = $bankAccounts->first(function ($b) use ($val) {
                return strcasecmp((string)$b->bank_name, (string)$val) === 0 || (string)$b->id === (string)$val;
            });

            if ($bank) {
                $lbl = $bank->bank_name;
                if ($bank->account_number) {
                    $lbl .= ' (' . $bank->account_number . ')';
                }
                if ($bank->account_name) {
                    $lbl .= ' - ' . $bank->account_name;
                }
                return $lbl;
            }

            if ($val === 'kas_kecil') return 'Kas Kecil';
            if ($val === 'kas_besar') return 'Kas Besar';

            return ucwords(str_replace('_', ' ', $val));
        };

        $selectedAccountLabel = $account === 'all' ? 'Semua Akun Kas / Bank' : $resolveAccountLabel($account);

        $matchedBank = $bankAccounts->first(function ($b) use ($account) {
            return strcasecmp((string)$b->bank_name, (string)$account) === 0 || (string)$b->id === (string)$account;
        });

        $accountFilterApply = function ($query, $column) use ($account, $matchedBank) {
            if ($account === 'all') {
                return;
            }
            $query->where(function ($q) use ($account, $matchedBank, $column) {
                $q->where($column, $account);
                if ($matchedBank) {
                    $q->orWhere($column, $matchedBank->bank_name)
                      ->orWhere($column, (string)$matchedBank->id);
                }
            });
        };

        $accountMatches = function ($recordVal) use ($account, $matchedBank) {
            if ($account === 'all') {
                return true;
            }
            if (strcasecmp((string)$recordVal, (string)$account) === 0) {
                return true;
            }
            if ($matchedBank) {
                if (strcasecmp((string)$recordVal, (string)$matchedBank->bank_name) === 0) {
                    return true;
                }
                if ((string)$recordVal === (string)$matchedBank->id) {
                    return true;
                }
            }
            return false;
        };

        // 1. Calculate Beginning Balance (Saldo Awal) before $dateFrom
        $beginningBalance = 0.0;

        // Incomes before $dateFrom
        $prevIncomesQuery = Income::where('tenant_id', $tenantId)->where('income_date', '<', $dateFrom);
        $accountFilterApply($prevIncomesQuery, 'payment_destination');
        $beginningBalance += (float) $prevIncomesQuery->sum('amount');

        // Expenses before $dateFrom
        $prevExpensesQuery = Expense::where('tenant_id', $tenantId)->where('expense_date', '<', $dateFrom);
        $accountFilterApply($prevExpensesQuery, 'payment_source');
        $beginningBalance -= (float) $prevExpensesQuery->sum('amount');

        // Fund Transfers before $dateFrom
        $prevTransfers = FundTransfer::where('tenant_id', $tenantId)->where('transfer_date', '<', $dateFrom)->get();
        foreach ($prevTransfers as $pt) {
            $amt = (float) $pt->amount;
            if ($account !== 'all') {
                if ($accountMatches($pt->destination)) {
                    $beginningBalance += $amt;
                }
                if ($accountMatches($pt->source)) {
                    $beginningBalance -= $amt;
                }
            }
        }

        // 2. Fetch Transactions within period
        $transactions = collect();

        // A. Incomes
        if ($sourceType === 'all' || $sourceType === 'income') {
            $incomesQuery = Income::where('tenant_id', $tenantId)
                ->whereBetween('income_date', [$dateFrom, $dateTo]);
            
            $accountFilterApply($incomesQuery, 'payment_destination');

            if ($search) {
                $incomesQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            foreach ($incomesQuery->get() as $inc) {
                $transactions->push([
                    'id'                       => $inc->id,
                    'model_type'               => 'income',
                    'raw_title'                => $inc->title,
                    'raw_category'             => $inc->category,
                    'raw_payment_destination'  => $inc->payment_destination,
                    'raw_amount'               => (float) $inc->amount,
                    'raw_income_date'          => \Carbon\Carbon::parse($inc->income_date)->format('Y-m-d'),
                    'raw_description'          => $inc->description ?? '',
                    'datetime'                 => \Carbon\Carbon::parse($inc->income_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'           => \Carbon\Carbon::parse($inc->income_date)->format('d/m/Y'),
                    'reference'                => 'INC-' . str_pad($inc->id, 5, '0', STR_PAD_LEFT),
                    'type'                     => 'income',
                    'type_label'               => 'Pemasukan Lain',
                    'type_badge'               => 'bg-success',
                    'category_label'           => $inc->category_label,
                    'account_label'            => $resolveAccountLabel($inc->payment_destination),
                    'description'              => $inc->title . ($inc->description ? ' (' . $inc->description . ')' : ''),
                    'inflow'                   => (float) $inc->amount,
                    'outflow'                  => 0.0,
                ]);
            }
        }

        // B. Expenses
        if ($sourceType === 'all' || $sourceType === 'expense') {
            $expensesQuery = Expense::with('employee')
                ->where('tenant_id', $tenantId)
                ->whereBetween('expense_date', [$dateFrom, $dateTo]);

            $accountFilterApply($expensesQuery, 'payment_source');

            if ($search) {
                $expensesQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            foreach ($expensesQuery->get() as $exp) {
                $desc = $exp->title;
                if ($exp->employee) {
                    $desc .= ' [PIC: ' . $exp->employee->name . ']';
                }
                if ($exp->description) {
                    $desc .= ' - ' . $exp->description;
                }
                $transactions->push([
                    'id'                 => $exp->id,
                    'model_type'         => 'expense',
                    'raw_title'          => $exp->title,
                    'raw_category'       => $exp->category,
                    'raw_payment_source' => $exp->payment_source,
                    'raw_amount'         => (float) $exp->amount,
                    'raw_expense_date'   => \Carbon\Carbon::parse($exp->expense_date)->format('Y-m-d'),
                    'raw_employee_id'    => $exp->employee_id ?? '',
                    'raw_description'    => $exp->description ?? '',
                    'datetime'           => \Carbon\Carbon::parse($exp->expense_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'     => \Carbon\Carbon::parse($exp->expense_date)->format('d/m/Y'),
                    'reference'          => 'EXP-' . str_pad($exp->id, 5, '0', STR_PAD_LEFT),
                    'type'               => 'expense',
                    'type_label'         => 'Pengeluaran',
                    'type_badge'         => 'bg-danger',
                    'category_label'     => $exp->category_label,
                    'account_label'      => $resolveAccountLabel($exp->payment_source),
                    'description'        => $desc,
                    'inflow'             => 0.0,
                    'outflow'            => (float) $exp->amount,
                ]);
            }
        }

        // C. Fund Transfers
        if ($sourceType === 'all' || $sourceType === 'transfer') {
            $transfersQuery = FundTransfer::where('tenant_id', $tenantId)
                ->whereBetween('transfer_date', [$dateFrom, $dateTo]);

            if ($account !== 'all') {
                $transfersQuery->where(function ($q) use ($accountFilterApply) {
                    $q->where(function ($sub) use ($accountFilterApply) {
                        $accountFilterApply($sub, 'source');
                    })->orWhere(function ($sub) use ($accountFilterApply) {
                        $accountFilterApply($sub, 'destination');
                    });
                });
            }

            if ($search) {
                $transfersQuery->where('description', 'like', "%{$search}%");
            }

            foreach ($transfersQuery->get() as $tr) {
                $srcLabel = $resolveAccountLabel($tr->source);
                $dstLabel = $resolveAccountLabel($tr->destination);
                $amt = (float) $tr->amount;

                $commonTransferData = [
                    'id'                 => $tr->id,
                    'model_type'         => 'transfer',
                    'raw_source'         => $tr->source,
                    'raw_destination'    => $tr->destination,
                    'raw_amount'         => (float) $tr->amount,
                    'raw_transfer_date'  => \Carbon\Carbon::parse($tr->transfer_date)->format('Y-m-d'),
                    'raw_description'    => $tr->description ?? '',
                    'datetime'           => \Carbon\Carbon::parse($tr->transfer_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'     => \Carbon\Carbon::parse($tr->transfer_date)->format('d/m/Y'),
                    'reference'          => 'TRF-' . str_pad($tr->id, 5, '0', STR_PAD_LEFT),
                ];

                if ($account === 'all') {
                    $transactions->push(array_merge($commonTransferData, [
                        'type'           => 'transfer',
                        'type_label'     => 'Transfer Antar Kas',
                        'type_badge'     => 'bg-warning text-dark',
                        'category_label' => 'Transfer Internal',
                        'account_label'  => $srcLabel . ' ➔ ' . $dstLabel,
                        'description'    => 'Pindah dana dari ' . $srcLabel . ' ke ' . $dstLabel . ($tr->description ? ' (' . $tr->description . ')' : ''),
                        'inflow'         => 0.0,
                        'outflow'        => 0.0,
                    ]));
                } else {
                    if ($accountMatches($tr->source)) {
                        $transactions->push(array_merge($commonTransferData, [
                            'type'           => 'transfer_out',
                            'type_label'     => 'Transfer Keluar',
                            'type_badge'     => 'bg-warning text-dark',
                            'category_label' => 'Transfer Kas',
                            'account_label'  => $srcLabel,
                            'description'    => 'Transfer keluar ke ' . $dstLabel . ($tr->description ? ' (' . $tr->description . ')' : ''),
                            'inflow'         => 0.0,
                            'outflow'        => $amt,
                        ]));
                    }
                    if ($accountMatches($tr->destination)) {
                        $transactions->push(array_merge($commonTransferData, [
                            'type'           => 'transfer_in',
                            'type_label'     => 'Transfer Masuk',
                            'type_badge'     => 'bg-info text-dark',
                            'category_label' => 'Transfer Kas',
                            'account_label'  => $dstLabel,
                            'description'    => 'Transfer masuk dari ' . $srcLabel . ($tr->description ? ' (' . $tr->description . ')' : ''),
                            'inflow'         => $amt,
                            'outflow'        => 0.0,
                        ]));
                    }
                }
            }
        }

        // Filter direction (in / out) if selected
        if ($direction === 'in') {
            $transactions = $transactions->filter(fn($t) => $t['inflow'] > 0);
        } elseif ($direction === 'out') {
            $transactions = $transactions->filter(fn($t) => $t['outflow'] > 0);
        }

        // Sort chronologically (ASC) to calculate running balance
        $sortedAsc = $transactions->sortBy('datetime')->values();

        $running = $beginningBalance;
        $totalInflow = 0.0;
        $totalOutflow = 0.0;

        $mutationsWithBalance = $sortedAsc->map(function ($item) use (&$running, &$totalInflow, &$totalOutflow) {
            $totalInflow += $item['inflow'];
            $totalOutflow += $item['outflow'];
            $running = $running + $item['inflow'] - $item['outflow'];
            $item['running_balance'] = $running;
            return $item;
        });

        $endingBalance = $running;
        $netCashFlow = $totalInflow - $totalOutflow;

        // Display in descending order (most recent transaction first)
        $mutations = $mutationsWithBalance->reverse()->values();

        $expenseCategories = FinanceCategory::where('tenant_id', $tenantId)
            ->expense()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $incomeCategories = FinanceCategory::where('tenant_id', $tenantId)
            ->income()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employees = \App\Models\Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return compact(
            'dateFrom',
            'dateTo',
            'account',
            'direction',
            'sourceType',
            'search',
            'bankAccounts',
            'selectedAccountLabel',
            'beginningBalance',
            'totalInflow',
            'totalOutflow',
            'netCashFlow',
            'endingBalance',
            'mutations',
            'expenseCategories',
            'incomeCategories',
            'employees'
        );
    }
}

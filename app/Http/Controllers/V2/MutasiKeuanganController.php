<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Income;
use App\Models\FundTransfer;
use App\Models\FinanceCategory;
use App\Models\BankAccount;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MutasiKeuanganController extends Controller
{
    /**
     * Display V2 Mutasi Keuangan index page.
     */
    public function index(Request $request)
    {
        $data = $this->buildMutationsData($request);
        return view('v2.mutasi_keuangan.index', $data);
    }

    /**
     * Print Laporan Mutasi Keuangan.
     */
    public function print(Request $request)
    {
        $data = $this->buildMutationsData($request);
        return view('finance.mutations_print', $data);
    }

    /**
     * Export Laporan Mutasi Keuangan to CSV.
     */
    public function export(Request $request)
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

    /**
     * Build financial mutations data payload identically to V1.
     */
    private function buildMutationsData(Request $request): array
    {
        $tenantId = Auth::user()->tenant_id;

        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());
        $account  = $request->get('account', 'all');
        $category = $request->get('category', 'all');
        $direction = $request->get('direction', 'all');
        $sourceType = $request->get('source_type', 'all');
        $search   = trim($request->get('search', ''));

        // Load active bank accounts from master database
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

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
        $prevIncomesQuery = Income::where('tenant_id', $tenantId)->whereDate('income_date', '<', $dateFrom);
        $accountFilterApply($prevIncomesQuery, 'payment_destination');
        $beginningBalance += (float) $prevIncomesQuery->sum('amount');

        // Expenses before $dateFrom
        $prevExpensesQuery = Expense::where('tenant_id', $tenantId)->whereDate('expense_date', '<', $dateFrom);
        $accountFilterApply($prevExpensesQuery, 'payment_source');
        $beginningBalance -= (float) $prevExpensesQuery->sum('amount');

        // Fund Transfers before $dateFrom
        $prevTransfers = FundTransfer::where('tenant_id', $tenantId)->whereDate('transfer_date', '<', $dateFrom)->get();
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
                ->whereDate('income_date', '>=', $dateFrom)
                ->whereDate('income_date', '<=', $dateTo);
            
            $accountFilterApply($incomesQuery, 'payment_destination');

            if ($category !== 'all') {
                $incomesQuery->where('category', $category);
            }

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
                    'raw_income_date'          => Carbon::parse($inc->income_date)->format('Y-m-d'),
                    'raw_description'          => $inc->description ?? '',
                    'datetime'                 => Carbon::parse($inc->income_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'           => Carbon::parse($inc->income_date)->format('d/m/Y'),
                    'reference'                => 'INC-' . str_pad($inc->id, 5, '0', STR_PAD_LEFT),
                    'type'                     => 'income',
                    'type_label'               => 'Pemasukan Lain',
                    'type_badge'               => 'bg-success text-white',
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
                ->whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo);

            $accountFilterApply($expensesQuery, 'payment_source');

            if ($category !== 'all') {
                $expensesQuery->where('category', $category);
            }

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
                    'raw_expense_date'   => Carbon::parse($exp->expense_date)->format('Y-m-d'),
                    'raw_employee_id'    => $exp->employee_id ?? '',
                    'raw_description'    => $exp->description ?? '',
                    'datetime'           => Carbon::parse($exp->expense_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'     => Carbon::parse($exp->expense_date)->format('d/m/Y'),
                    'reference'          => 'EXP-' . str_pad($exp->id, 5, '0', STR_PAD_LEFT),
                    'type'               => 'expense',
                    'type_label'         => 'Pengeluaran',
                    'type_badge'         => 'bg-danger text-white',
                    'category_label'     => $exp->category_label,
                    'account_label'      => $resolveAccountLabel($exp->payment_source),
                    'description'        => $desc,
                    'inflow'             => 0.0,
                    'outflow'            => (float) $exp->amount,
                ]);
            }
        }

        // C. Fund Transfers
        if (($sourceType === 'all' || $sourceType === 'transfer') && ($category === 'all' || $category === 'transfer')) {
            $transfersQuery = FundTransfer::where('tenant_id', $tenantId)
                ->whereDate('transfer_date', '>=', $dateFrom)
                ->whereDate('transfer_date', '<=', $dateTo);

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
                    'raw_transfer_date'  => Carbon::parse($tr->transfer_date)->format('Y-m-d'),
                    'raw_description'    => $tr->description ?? '',
                    'datetime'           => Carbon::parse($tr->transfer_date)->startOfDay()->toDateTimeString(),
                    'date_formatted'     => Carbon::parse($tr->transfer_date)->format('d/m/Y'),
                    'reference'          => 'TRF-' . str_pad($tr->id, 5, '0', STR_PAD_LEFT),
                ];

                if ($account === 'all') {
                    $transactions->push(array_merge($commonTransferData, [
                        'type'           => 'transfer',
                        'type_label'     => 'Transfer Internal',
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

        $allCategories = FinanceCategory::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return compact(
            'dateFrom',
            'dateTo',
            'account',
            'category',
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
            'allCategories',
            'employees'
        );
    }
}

<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\LatePenaltyRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LatePenaltyRuleController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $rules = LatePenaltyRule::where('tenant_id', $tenantId)
            ->orderBy('min_minutes')
            ->get();
        return view('hrd.late_penalties.index', compact('rules'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'min_minutes' => 'required|integer|min:1',
            'penalty_amount' => 'required|numeric|min:0',
        ]);

        $exists = LatePenaltyRule::where('tenant_id', $tenantId)
            ->where('min_minutes', $request->min_minutes)
            ->exists();

        if ($exists) {
            return back()->withErrors(['min_minutes' => 'Aturan untuk menit keterlambatan ini sudah ada.']);
        }

        LatePenaltyRule::create([
            'tenant_id' => $tenantId,
            'min_minutes' => $request->min_minutes,
            'penalty_amount' => $request->penalty_amount,
        ]);

        return back()->with('success', 'Aturan denda keterlambatan berhasil ditambahkan.');
    }

    public function update(Request $request, LatePenaltyRule $latePenalty)
    {
        abort_unless($latePenalty->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'min_minutes' => 'required|integer|min:1',
            'penalty_amount' => 'required|numeric|min:0',
        ]);

        $exists = LatePenaltyRule::where('tenant_id', Auth::user()->tenant_id)
            ->where('min_minutes', $request->min_minutes)
            ->where('id', '!=', $latePenalty->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['min_minutes' => 'Aturan untuk menit keterlambatan ini sudah ada.']);
        }

        $latePenalty->update([
            'min_minutes' => $request->min_minutes,
            'penalty_amount' => $request->penalty_amount,
        ]);

        return back()->with('success', 'Aturan denda keterlambatan berhasil diperbarui.');
    }

    public function destroy(LatePenaltyRule $latePenalty)
    {
        abort_unless($latePenalty->tenant_id === Auth::user()->tenant_id, 403);
        $latePenalty->delete();
        return back()->with('success', 'Aturan denda keterlambatan berhasil dihapus.');
    }
}

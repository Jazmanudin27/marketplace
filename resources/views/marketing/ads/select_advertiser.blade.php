@extends('layouts.app')

@section('title', 'Pilih Akun Iklan TikTok')
@section('page-title', 'Otorisasi TikTok Ads')

@section('content')
<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card border shadow-sm bg-white">
            <div class="card-header bg-primary bg-opacity-10 p-3 border-bottom text-center">
                <h6 class="m-0 fw-bold text-dark"><i class="bi bi-tiktok text-dark me-2"></i> Pilih Akun Iklan TikTok (Advertiser)</h6>
            </div>
            <div class="card-body p-4">
                <p class="text-muted small text-center mb-4">Silakan pilih Advertiser Account yang ingin Anda hubungkan dengan ERP ini untuk penarikan data performa secara otomatis.</p>
                
                <div class="d-flex flex-column gap-3">
                    @foreach($advertisers as $adv)
                        <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between">
                            <div>
                                <strong class="text-dark d-block">{{ $adv['name'] ?? $adv['advertiser_name'] ?? 'Akun Iklan' }}</strong>
                                <small class="text-muted font-monospace" style="font-size:0.75rem;">ID: {{ $adv['id'] ?? $adv['advertiser_id'] ?? '' }}</small>
                            </div>
                            <form action="{{ route('marketing.ads.tiktok.select') }}" method="POST">
                                @csrf
                                <input type="hidden" name="advertiser_id" value="{{ $adv['id'] ?? $adv['advertiser_id'] ?? '' }}">
                                <input type="hidden" name="advertiser_name" value="{{ $adv['name'] ?? $adv['advertiser_name'] ?? 'Akun Iklan' }}">
                                <input type="hidden" name="access_token" value="{{ $accessToken }}">
                                <button type="submit" class="btn btn-sm btn-primary rounded-3 px-3 fw-bold">
                                    Tautkan Akun
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

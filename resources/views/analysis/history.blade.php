@extends('layouts.app')

@section('content')
<main class="flex-1 w-full max-w-container-max mx-auto px-gutter py-stack-lg space-y-stack-lg flex flex-col">
<header class="relative bg-white border border-outline-variant rounded-xl overflow-hidden min-h-[160px] flex items-center px-gutter py-stack-md" data-alt="A clean, modern clinical laboratory setting bathed in soft, high-key white lighting. In the out-of-focus background, sophisticated medical equipment and subtle blue accents create a professional, sterile, yet approachable atmosphere. The overall composition relies on a palette of crisp whites, soft grays, and precise medical blue, perfectly aligning with a high-end, trustworthy healthcare technology aesthetic." style="background-image: linear-gradient(to right, rgba(255, 255, 255, 1) 40%, rgba(255, 255, 255, 0.8)), url('https://lh3.googleusercontent.com/aida-public/AB6AXuDbNAn31ZAIYxe2W2L9FPDcvEUfnexAcHXi_heW-Wa7Qff4hMQ62hXmS5olj1Fa3wzc9HGHdykrSVz79i1azIOTFU9G7MkdsCi1KtmQ4VPMoFTH1zzwVNdcE3SOdKyXAgHVJTB3Iwij5nnimYjz2kY0jBgxs0KFlIn7Fz4gpbhBnlO8dScrMr9omOWcsEtLVcbDihhXBBvJaSHTjj5X3rMfN-ApIpCEd3MGPUI5JKTf_nJFyMUz7b1GpOokMQwgMTEKT1sAoKuHySxd'); background-size: cover; background-position: center right;">
<div class="relative z-10 max-w-2xl">
<h1 class="text-headline-lg font-headline-lg text-on-surface mb-2">Analysis History</h1>
<p class="text-body-lg font-body-lg text-on-surface-variant">Review your past predictive models, track clinical data trends, and access detailed reports for ongoing health monitoring.</p>
</div>
</header>
<section class="grid grid-cols-1 md:grid-cols-3 gap-stack-md">
<div class="bg-surface border border-outline-variant rounded-xl p-stack-md flex flex-col justify-between shadow-sm">
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">analytics</span>
<span class="text-label-sm font-label-sm uppercase tracking-wider">Total Analyses</span>
</div>
<div class="text-headline-lg font-headline-lg text-primary mt-4">{{ $totalAnalyses }}</div>
</div>
<div class="bg-surface border border-outline-variant rounded-xl p-stack-md flex flex-col justify-between shadow-sm">
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">vital_signs</span>
<span class="text-label-sm font-label-sm uppercase tracking-wider">Average Risk Level</span>
</div>
<div class="mt-4 flex items-center gap-2">
<div class="w-3 h-3 rounded-full bg-secondary"></div>
<span class="text-headline-md font-headline-md text-on-surface">Low to Moderate</span>
</div>
</div>
<div class="bg-surface border border-outline-variant rounded-xl p-stack-md flex flex-col justify-between shadow-sm">
<div class="flex items-center gap-2 text-on-surface-variant">
<span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">trending_up</span>
<span class="text-label-sm font-label-sm uppercase tracking-wider">Recent Trend</span>
</div>
<div class="text-headline-md font-headline-md text-secondary mt-4 flex items-center gap-1">
<span class="material-symbols-outlined text-sm">arrow_downward</span>
                    Stable
                </div>
</div>
</section>
<section class="bg-surface border border-outline-variant rounded-xl overflow-hidden shadow-sm flex-1">
<div class="bg-surface-container-low px-gutter py-stack-sm border-b border-outline-variant flex justify-between items-center">
<h2 class="text-headline-sm font-headline-sm text-on-surface">Historical Records</h2>
<button class="text-primary text-label-sm font-label-sm flex items-center gap-1 hover:underline">
<span class="material-symbols-outlined text-[16px]">filter_list</span> Filter
                </button>
</div>
<div class="flex flex-col">
    @forelse ($histories as $history)
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between py-stack-md px-gutter border-b border-outline-variant hover:bg-surface-container-highest transition-colors gap-4">
            <div class="flex items-center gap-4 w-full md:w-1/4">
                @if($history->prediction == 1)
                    <div class="bg-error-container text-on-error-container w-10 h-10 rounded-lg flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">warning</span>
                    </div>
                @else
                    <div class="bg-surface-container text-on-surface-variant w-10 h-10 rounded-lg flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">fact_check</span>
                    </div>
                @endif
                <div>
                    <div class="text-label-md font-label-md text-on-surface">{{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y') }}</div>
                    <div class="text-label-sm font-label-sm text-on-surface-variant">Record: DP-{{ str_pad($history->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>
            <div class="flex flex-col w-full md:w-1/4">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Primary Metrics</span>
                <span class="text-body-md font-body-md text-on-surface">HbA1c: {{ $history->hba1c_level }}% | Glu: {{ $history->blood_glucose_level }}</span>
            </div>
            <div class="flex flex-col w-full md:w-1/4">
                <span class="text-label-sm font-label-sm text-on-surface-variant">Risk Assessment</span>
                @if($history->prediction == 1)
                    <span class="inline-flex items-center gap-2 text-label-md font-label-md text-error">
                        <span class="w-2 h-2 rounded-full bg-error"></span> High Risk ({{ number_format($history->probability, 1) }}%)
                    </span>
                @else
                    <span class="inline-flex items-center gap-2 text-label-md font-label-md text-secondary">
                        <span class="w-2 h-2 rounded-full bg-secondary"></span> Low Risk ({{ number_format($history->probability, 1) }}%)
                    </span>
                @endif
            </div>
            <div class="w-full md:w-auto flex justify-end">
                <a href="{{ route('analysis.detail', $history->id) }}" class="text-primary border border-primary px-4 py-2 rounded-lg text-label-sm font-label-sm hover:bg-primary-container hover:text-on-primary-container transition-colors inline-block text-center">View Details</a>
            </div>
        </div>
    @empty
        <div class="py-stack-lg px-gutter text-center text-on-surface-variant">
            No analysis records found. <a href="{{ route('analysis.form') }}" class="text-primary hover:underline">Start a new analysis</a>.
        </div>
    @endforelse
</div>
</section>
</main>
@endsection

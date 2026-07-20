@extends('layouts.app')

@section('content')
<main class="flex-1 w-full max-w-container-max mx-auto px-gutter py-stack-lg space-y-stack-lg flex flex-col">
    <header class="relative bg-white border border-outline-variant rounded-xl overflow-hidden min-h-[120px] flex items-center px-gutter py-stack-md">
        <div class="relative z-10 max-w-2xl">
            <div class="flex items-center gap-2 mb-2">
                <a href="{{ route('analysis.history') }}" class="text-on-surface-variant hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                </a>
                <h1 class="text-headline-md font-headline-md text-on-surface">Analysis Detail</h1>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                Record: DP-{{ str_pad($history->id, 4, '0', STR_PAD_LEFT) }} | Date: {{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y h:i A') }}
            </p>
        </div>
    </header>

    <section class="grid grid-cols-1 md:grid-cols-2 gap-stack-lg">
        <!-- Results Card -->
        <div class="bg-surface border border-outline-variant rounded-xl p-stack-lg flex flex-col shadow-sm">
            <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">assessment</span>
                <h2 class="text-headline-sm font-headline-sm text-on-surface">Prediction Result</h2>
            </div>
            
            <div class="flex-1 flex flex-col items-center justify-center text-center py-stack-lg">
                @if($history->prediction == 1)
                    <div class="w-24 h-24 rounded-full bg-error-container text-on-error-container flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-[48px]">warning</span>
                    </div>
                    <h3 class="text-display-sm font-display-sm text-error mb-2">High Risk</h3>
                    <p class="text-body-lg font-body-lg text-on-surface-variant">
                        Based on the provided metrics, our AI model indicates a high probability ({{ number_format($history->probability, 1) }}%) of diabetes. We strongly recommend consulting with a healthcare professional for a formal diagnosis.
                    </p>
                @else
                    <div class="w-24 h-24 rounded-full bg-surface-container-highest text-secondary flex items-center justify-center mb-6">
                        <span class="material-symbols-outlined text-[48px]">health_and_safety</span>
                    </div>
                    <h3 class="text-display-sm font-display-sm text-secondary mb-2">Low Risk</h3>
                    <p class="text-body-lg font-body-lg text-on-surface-variant">
                        Based on the provided metrics, our AI model indicates a low probability ({{ number_format($history->probability, 1) }}%) of diabetes. Maintain your healthy lifestyle and continue regular checkups.
                    </p>
                @endif
            </div>
        </div>

        <!-- Input Data Card -->
        <div class="bg-surface border border-outline-variant rounded-xl p-stack-lg flex flex-col shadow-sm">
            <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">medical_information</span>
                <h2 class="text-headline-sm font-headline-sm text-on-surface">Input Metrics</h2>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <!-- Age -->
                <div class="bg-surface-container-low p-4 rounded-lg">
                    <span class="text-label-sm font-label-sm text-on-surface-variant block mb-1">Age</span>
                    <span class="text-title-md font-title-md text-on-surface">
                        {{ $history->age }} Years
                    </span>
                </div>
                
                <!-- Hypertension -->
                <div class="bg-surface-container-low p-4 rounded-lg">
                    <span class="text-label-sm font-label-sm text-on-surface-variant block mb-1">Hypertension</span>
                    <span class="text-title-md font-title-md text-on-surface">
                        {{ $history->hypertension == 1 ? 'Yes' : 'No' }}
                    </span>
                </div>

                <!-- BMI -->
                <div class="bg-surface-container-low p-4 rounded-lg">
                    <span class="text-label-sm font-label-sm text-on-surface-variant block mb-1">BMI</span>
                    <span class="text-title-md font-title-md text-on-surface">
                        {{ $history->bmi }}
                    </span>
                </div>

                <!-- HbA1c -->
                <div class="bg-surface-container-low p-4 rounded-lg">
                    <span class="text-label-sm font-label-sm text-on-surface-variant block mb-1">HbA1c Level</span>
                    <span class="text-title-md font-title-md text-on-surface">
                        {{ $history->hba1c_level }}%
                    </span>
                </div>

                <!-- Blood Glucose -->
                <div class="bg-surface-container-low p-4 rounded-lg">
                    <span class="text-label-sm font-label-sm text-on-surface-variant block mb-1">Blood Glucose</span>
                    <span class="text-title-md font-title-md text-on-surface">
                        {{ $history->blood_glucose_level }} mg/dL
                    </span>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection

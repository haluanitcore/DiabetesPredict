@extends('layouts.app')

@section('content')
<main class="flex-grow flex items-center justify-center py-stack-lg px-gutter">
<div class="w-full max-w-2xl bg-surface-container-lowest border border-surface-variant rounded-xl shadow-sm p-stack-lg">
<div class="mb-stack-lg text-center">
<h1 class="text-headline-lg font-headline-lg text-primary mb-unit">Health Data Analysis</h1>
<p class="text-body-md font-body-md text-on-surface-variant">Please provide your recent health metrics for an accurate diabetes prediction. All data is processed securely.</p>
</div>
<!-- Progress Indicator -->
<div class="w-full bg-surface-container-high rounded-full h-2 mb-stack-lg">
<div class="bg-primary h-2 rounded-full w-1/3"></div>
</div>
<form method="POST" action="{{ route('analysis.form') }}" class="space-y-stack-md">
    @csrf

    @if (session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-4">
            {{ session('error') }}
        </div>
    @endif
    
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-4">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md">
        
        <!-- Age Input -->
        <div class="space-y-unit">
            <label class="block text-label-md font-label-md text-on-surface" for="age">Age (Years)</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="material-symbols-outlined text-outline-variant">calendar_month</span>
                </span>
                <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px]" id="age" name="age" value="{{ old('age') }}" placeholder="e.g., 45" type="number" step="0.1" required/>
            </div>
        </div>

        <!-- Hypertension Select -->
        <div class="space-y-unit">
            <label class="block text-label-md font-label-md text-on-surface" for="hypertension">Hypertension</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="material-symbols-outlined text-outline-variant">blood_pressure</span>
                </span>
                <select class="block w-full pl-12 pr-10 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px] appearance-none" id="hypertension" name="hypertension" required>
                    <option value="" disabled selected>Do you have hypertension?</option>
                    <option value="0" {{ old('hypertension') == '0' ? 'selected' : '' }}>No</option>
                    <option value="1" {{ old('hypertension') == '1' ? 'selected' : '' }}>Yes</option>
                </select>
            </div>
        </div>

        <!-- BMI Calculation Group -->
        <div class="col-span-1 md:col-span-2 border border-outline-variant/60 rounded-xl p-5 bg-surface-container-low/30 space-y-4">
            <h3 class="text-label-md font-semibold text-primary flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">calculate</span>
                BMI & Body Metrics
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Weight Input -->
                <div class="space-y-unit">
                    <label class="block text-label-md font-label-md text-on-surface" for="weight">Weight (kg)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="material-symbols-outlined text-outline-variant">weight</span>
                        </span>
                        <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px]" id="weight" name="weight" value="{{ old('weight') }}" placeholder="e.g., 70" step="0.1" type="number" required/>
                    </div>
                </div>

                <!-- Height Input -->
                <div class="space-y-unit">
                    <label class="block text-label-md font-label-md text-on-surface" for="height">Height (cm)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="material-symbols-outlined text-outline-variant">straighten</span>
                        </span>
                        <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px]" id="height" name="height" value="{{ old('height') }}" placeholder="e.g., 170" step="0.1" type="number" required/>
                    </div>
                </div>

                <!-- BMI Input -->
                <div class="space-y-unit">
                    <label class="block text-label-md font-label-md text-on-surface" for="bmi">BMI (Auto-calculated)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <span class="material-symbols-outlined text-outline-variant">calculate</span>
                        </span>
                        <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-high text-on-surface-variant min-h-[48px] cursor-not-allowed focus:outline-none" id="bmi" name="bmi" value="{{ old('bmi') }}" placeholder="Enter weight and height..." step="0.1" type="number" readonly required/>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- HbA1c Level Input -->
        <div class="space-y-unit">
            <label class="block text-label-md font-label-md text-on-surface" for="hba1c_level">HbA1c Level (%)</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="material-symbols-outlined text-outline-variant">science</span>
                </span>
                <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px]" id="hba1c_level" name="hba1c_level" value="{{ old('hba1c_level') }}" placeholder="e.g., 5.5" step="0.1" type="number" required/>
            </div>
        </div>

        <!-- Blood Glucose Input -->
        <div class="space-y-unit">
            <label class="block text-label-md font-label-md text-on-surface" for="blood_glucose_level">Blood Glucose Level (mg/dL)</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                    <span class="material-symbols-outlined text-outline-variant">bloodtype</span>
                </span>
                <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-lowest text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 min-h-[48px]" id="blood_glucose_level" name="blood_glucose_level" value="{{ old('blood_glucose_level') }}" placeholder="e.g., 140" type="number" required/>
            </div>
        </div>

    </div>
    
    <div class="pt-stack-md flex justify-end">
        <button class="bg-primary text-on-primary text-label-md font-label-md py-3 px-8 rounded-lg hover:opacity-90 transition-all shadow-sm flex items-center gap-2" type="submit">
            Predict Now
            <span class="material-symbols-outlined text-[20px]">equalizer</span>
        </button>
    </div>
</form>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weightInput = document.getElementById('weight');
    const heightInput = document.getElementById('height');
    const bmiInput = document.getElementById('bmi');

    function calculateBMI() {
        const weight = parseFloat(weightInput.value);
        const height = parseFloat(heightInput.value);

        if (weight && height && weight > 0 && height > 0) {
            const heightInMeters = height / 100;
            const bmi = weight / (heightInMeters * heightInMeters);
            bmiInput.value = bmi.toFixed(1);
        } else {
            bmiInput.value = '';
        }
    }

    weightInput.addEventListener('input', calculateBMI);
    heightInput.addEventListener('input', calculateBMI);
    
    // Run once on load in case values were old input (e.g. validation error)
    calculateBMI();
});
</script>
@endsection

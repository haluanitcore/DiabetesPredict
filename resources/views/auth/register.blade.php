@extends('layouts.app')

@section('content')
<main class="flex-grow flex items-center justify-center py-stack-lg px-gutter relative overflow-hidden">
<!-- Background Decorative Elements -->
<div class="absolute inset-0 z-0 pointer-events-none opacity-20">
<div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-fixed rounded-full blur-3xl"></div>
<div class="absolute -bottom-24 -right-24 w-96 h-96 bg-secondary-fixed rounded-full blur-3xl"></div>
</div>
<!-- Registration Card -->
<div class="z-10 w-full max-w-[480px] bg-surface-container-lowest border border-outline-variant rounded-xl shadow-sm p-8 md:p-10">
<div class="mb-stack-lg text-center">
<h1 class="text-headline-lg font-headline-lg text-primary mb-2">Create Account</h1>
<p class="text-body-md font-body-md text-on-surface-variant">Join DiaPredict for precise health insights.</p>
</div>
<form method="POST" action="{{ route('register') }}" class="flex flex-col gap-stack-md">
    @csrf
    
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-2">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Full Name -->
    <div class="flex flex-col gap-1">
        <label class="text-label-md font-label-md text-on-surface" for="full_name">Full Name</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]" data-icon="person">person</span>
            <input class="w-full pl-12 pr-4 py-3 bg-surface border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-outline-variant" id="full_name" name="name" value="{{ old('name') }}" placeholder="John Doe" type="text" required/>
        </div>
    </div>
    <!-- Email -->
    <div class="flex flex-col gap-1">
        <label class="text-label-md font-label-md text-on-surface" for="email">Email Address</label>
        <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]" data-icon="mail">mail</span>
            <input class="w-full pl-12 pr-4 py-3 bg-surface border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-outline-variant" id="email" name="email" value="{{ old('email') }}" placeholder="name@clinical.com" type="email" required/>
        </div>
    </div>
    <!-- Password Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-stack-md">
        <!-- Password -->
        <div class="flex flex-col gap-1">
            <label class="text-label-md font-label-md text-on-surface" for="password">Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]" data-icon="lock">lock</span>
                <input class="w-full pl-12 pr-4 py-3 bg-surface border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-outline-variant" id="password" name="password" placeholder="••••••••" type="password" required/>
            </div>
        </div>
        <!-- Confirm Password -->
        <div class="flex flex-col gap-1">
            <label class="text-label-md font-label-md text-on-surface" for="confirm_password">Confirm Password</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]" data-icon="key">key</span>
                <input class="w-full pl-12 pr-4 py-3 bg-surface border border-outline-variant rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all placeholder:text-outline-variant" id="confirm_password" name="password_confirmation" placeholder="••••••••" type="password" required/>
            </div>
        </div>
    </div>
    <!-- Terms and Conditions -->
    <div class="flex items-start gap-3 py-2">
        <input class="mt-1 w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary" id="terms" type="checkbox" required/>
        <label class="text-label-sm font-label-sm text-on-surface-variant" for="terms">
            I agree to the <a class="text-primary hover:underline" href="#">Terms and Conditions</a> and <a class="text-primary hover:underline" href="#">Privacy Policy</a> of DiaPredict.
        </label>
    </div>
    <!-- Action Button -->
    <button class="w-full bg-primary text-on-primary py-3 px-6 rounded-lg font-headline-sm text-headline-sm hover:opacity-90 active:scale-[0.98] transition-all flex items-center justify-center gap-2 mt-2" type="submit">
        Create Account
        <span class="material-symbols-outlined" data-icon="arrow_forward">arrow_forward</span>
    </button>
    <!-- Login Link -->
    <div class="text-center mt-stack-md">
        <p class="text-body-md font-body-md text-on-surface-variant">
            Already have an account? 
            <a class="text-primary font-bold hover:underline" href="{{ route('login') }}">Log In</a>
        </p>
    </div>
</form>
</div>
<!-- Right Side Graphic (Web Only) -->
<div class="hidden lg:flex flex-col ml-gutter max-w-sm gap-stack-md">
<div class="bg-white/40 backdrop-blur-md p-6 rounded-xl border border-white/60">
<span class="material-symbols-outlined text-secondary text-[40px] mb-4" data-icon="verified_user" style="font-variation-settings: 'FILL' 1;">verified_user</span>
<h3 class="text-headline-sm font-headline-sm text-primary mb-2">Secure &amp; Private</h3>
<p class="text-body-md font-body-md text-on-surface-variant">Your health data is encrypted using clinical-grade security protocols, ensuring absolute confidentiality.</p>
</div>
<img class="rounded-xl shadow-lg border border-outline-variant w-full h-48 object-cover" data-alt="A macro photograph of a high-tech laboratory environment with a soft, clean aesthetic. The scene features a clean white workbench with precision medical instruments and glowing blue digital displays in the background. The lighting is bright and clinical yet inviting, utilizing a palette of hospital whites and deep professional blues. The atmosphere is meticulous and focused on technological advancement." src="https://lh3.googleusercontent.com/aida-public/AB6AXuCEXkAOxmaL_Kn-1ScIBaM65lTXznm-VXMb_y0aaeRp-AkwzwZIX4hKHzvD_sdx1WKk0VpRzXvwOzVdG9nUy9FM6w0Ta1ffjrlACmrnGlK4omMDfodU7nWH7VB_QSka9-8w61f8_2PJ6bs0Hz-SrzukpB6J7sgnGlwh1eWSyBJquVX5Rmr9SbOKXpsUORXAA6XvSX61SiD6MDGDqntZKlcr6KC85L3xgiTrbjv7dZOFlnDEPiKs_zajN_y7ftlvy57_m2VPHxFy-WcO"/>
</div>
</main>
@endsection

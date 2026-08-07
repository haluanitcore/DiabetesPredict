<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>DiaPredict - Diabetes Risk Prediction</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background text-on-background font-body-md text-body-md antialiased min-h-screen flex flex-col relative">

<!-- Background Decorative Elements -->
<div class="fixed inset-0 z-[-1] pointer-events-none overflow-hidden bg-[#fcfdfe]">
    <!-- Grid Pattern -->
    <div class="absolute inset-0 bg-grid-pattern opacity-[0.07]"></div>
    
    <!-- Science Glows -->
    <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-primary/5 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-5%] w-[40%] h-[40%] bg-secondary/5 rounded-full blur-[100px]"></div>

    <!-- Floating Medical Icons (Watermark Style) -->
    <div class="absolute top-[60%] right-[8%] opacity-[0.02] rotate-[12deg] select-none animate-pulse-slow">
        <span class="material-symbols-outlined !text-[100px]">stethoscope</span>
    </div>
    <div class="absolute bottom-[20%] left-[10%] opacity-[0.02] rotate-[-5deg] select-none animate-pulse-slow">
        <span class="material-symbols-outlined !text-[150px]">medical_services</span>
    </div>
    <div class="absolute top-[40%] right-[25%] opacity-[0.02] select-none animate-pulse-slow">
        <span class="material-symbols-outlined !text-[80px]">pill</span>
    </div>
    <div class="absolute bottom-[10%] left-[40%] opacity-[0.02] select-none animate-pulse-slow">
        <span class="material-symbols-outlined !text-[90px]">biotech</span>
    </div>

    <!-- ECG Pulse Line (Very Subtle) -->
    <div class="absolute top-[35%] left-0 w-full opacity-[0.04] select-none">
        <svg width="100%" height="100" viewBox="0 0 1000 100" preserveAspectRatio="none" class="stroke-primary fill-none stroke-2">
            <path d="M0,50 L100,50 L110,40 L120,60 L130,50 L200,50 L210,10 L220,90 L230,50 L300,50 L310,45 L320,55 L330,50 L1000,50" />
        </svg>
    </div>
</div>

<!-- TopNavBar Component -->
<nav class="glass w-full top-0 border-b border-outline-variant/30 z-50 sticky">
    <div class="flex justify-between items-center w-full px-gutter max-w-container-max mx-auto h-16">
        <!-- Brand Logo -->
        <a href="{{ route('home') }}" class="flex items-center gap-2 hover:opacity-80 transition-opacity">
            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary" data-weight="fill" style="font-variation-settings: 'FILL' 1; font-size: 20px;">monitor_heart</span>
            </div>
            <span class="text-headline-md font-headline-md text-primary tracking-tight">DiaPredict</span>
        </a>
        <!-- Navigation Links -->
        <div class="hidden md:flex items-center gap-stack-md">
            <a class="text-on-surface-variant px-2 py-1 hover:text-primary transition-colors duration-200 text-label-md font-label-md {{ request()->routeIs('home') ? 'text-primary font-bold border-b-2 border-primary pb-1' : '' }}" href="{{ route('home') }}">Home</a>
            <a class="text-on-surface-variant px-2 py-1 hover:text-primary transition-colors duration-200 text-label-md font-label-md {{ request()->routeIs('analysis.form') ? 'text-primary font-bold border-b-2 border-primary pb-1' : '' }}" href="{{ route('analysis.form') }}">Analysis</a>
            <a class="text-on-surface-variant px-2 py-1 hover:text-primary transition-colors duration-200 text-label-md font-label-md {{ request()->routeIs('analysis.comparison') ? 'text-primary font-bold border-b-2 border-primary pb-1' : '' }}" href="{{ route('analysis.comparison') }}">Comparison</a>
            <a class="text-on-surface-variant px-2 py-1 hover:text-primary transition-colors duration-200 text-label-md font-label-md {{ request()->routeIs('analysis.history') ? 'text-primary font-bold border-b-2 border-primary pb-1' : '' }}" href="{{ route('analysis.history') }}">History</a>
        </div>
        <!-- Trailing Action -->
        <div class="flex items-center gap-4">
            @guest
                <a href="{{ route('login') }}" class="text-on-surface-variant font-label-md hover:text-primary transition-colors">Log In</a>
                <a href="{{ route('register') }}" class="bg-primary-container text-on-primary-container rounded-full px-5 py-2 text-label-md font-label-md hover:bg-primary hover:text-white transition-all duration-300">Sign Up</a>
            @else
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-outline-variant text-[20px]">person</span>
                        <span class="text-label-md font-label-md text-on-surface">{{ Auth::user()->name }}</span>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-error/10 text-error rounded-full px-4 py-2 text-label-sm font-label-sm hover:bg-error hover:text-white transition-all duration-300">Logout</button>
                    </form>
                </div>
            @endguest
        </div>
    </div>
</nav>

<!-- Main Content Canvas -->
@yield('content')

<!-- Footer Component -->
<footer class="bg-surface-container border-t border-outline-variant w-full mt-auto">
    <div class="flex flex-col md:flex-row justify-between items-center w-full px-gutter py-stack-lg max-w-container-max mx-auto gap-stack-md">
        <!-- Brand -->
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">monitor_heart</span>
            <span class="text-headline-sm font-headline-sm text-primary font-bold">DiaPredict</span>
        </div>
        <!-- Links -->
        <div class="flex flex-wrap justify-center gap-6">
            <a class="text-on-surface font-label-sm text-label-sm hover:text-primary transition-colors font-medium" href="{{ route('legal') }}#privacy-policy">Privacy Policy</a>
            <a class="text-on-surface font-label-sm text-label-sm hover:text-primary transition-colors font-medium" href="{{ route('legal') }}#terms-of-service">Terms of Service</a>
            <a class="text-on-surface font-label-sm text-label-sm hover:text-primary transition-colors font-medium" href="{{ route('legal') }}#medical-disclaimer">Medical Disclaimer</a>
            <a class="text-on-surface font-label-sm text-label-sm hover:text-primary transition-colors font-medium" href="{{ route('legal') }}#support">Support</a>
        </div>
        <!-- Copyright -->
        <div class="text-on-surface-variant text-body-md font-body-md text-center md:text-right">
            © 2026 <span class="font-semibold text-on-surface">DiaPredict Health</span>. All rights reserved.
        </div>
    </div>
</footer>

</body>
</html>

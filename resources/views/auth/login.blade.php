@extends('layouts.app')

@section('content')
<main class="flex-grow flex items-center justify-center px-margin-mobile pt-16">
<div class="grid lg:grid-cols-2 gap-gutter items-center max-w-container-max w-full mx-auto">
<div class="hidden lg:flex flex-col gap-stack-md pr-gutter">
<h1 class="text-headline-lg font-headline-lg text-on-surface">
                    Clinical Precision in Diabetes Prediction.
                </h1>
<p class="text-body-lg font-body-lg text-on-surface-variant max-w-md">
                    Access your personalized health insights and predictive analytics with our secure, patient-centric platform.
                </p>
<div class="mt-stack-lg relative rounded-xl overflow-hidden aspect-video border border-outline-variant shadow-sm">
<img class="w-full h-full object-cover" data-alt="A professional medical environment featuring a high-tech laboratory setting with soft, clean lighting. A doctor is interacting with a sophisticated digital health interface displaying clear data visualizations. The aesthetic is modern and clinical, utilizing a calm color palette of whites and medical blues to evoke a sense of security and expert reliability. High-key lighting creates a bright and airy atmosphere typical of a premium healthcare facility." src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6-cmO4gEsBioi27ZNzyFUbSUCToUEOrERiHkNTRSBiEZDI9qT2ZthPdCgZ-J82BmEn9RNPbsJVlAgUO5gQFnttQu0Ni5oYbM2JoG7q9c6BWhmBcQ-CIciwtOLDkzc8DsDhxY-4gCZbPac-I2C6VaFbG-LcibKSrNbrm9lPyam5AWMNm65tjRZUJzKLrkluDrHCQmEIoDzo_u5_6yu8Xj3uLpqSyMndJGxuZDnC5AivojGf3KaGZCQOC6SgZV8I3eu5vG__pZMBl0e"/>
</div>
</div>
<div class="w-full max-w-[440px] mx-auto lg:mx-0">
<div class="bg-surface-container-lowest p-stack-lg rounded-xl border border-outline-variant shadow-sm">
<div class="flex flex-col items-center mb-stack-lg">
<div class="w-12 h-12 bg-primary-container rounded-lg flex items-center justify-center mb-stack-sm">
<span class="material-symbols-outlined text-on-primary-container text-[28px]">health_metrics</span>
</div>
<h2 class="text-headline-md font-headline-md text-on-surface">Welcome Back</h2>
<p class="text-body-md font-body-md text-on-surface-variant">Sign in to your clinical dashboard</p>
</div>
<form method="POST" action="{{ route('login') }}" class="space-y-stack-md">
    @csrf
    
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded-lg text-sm mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="space-y-stack-sm">
        <label class="block text-label-md font-label-md text-on-surface" for="email">Email Address</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline-variant text-[20px]">mail</span>
            </div>
            <input class="block w-full pl-12 pr-3 py-3 border border-outline-variant rounded-lg bg-surface-container-low text-on-surface text-body-md focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" id="email" name="email" value="{{ old('email') }}" placeholder="doctor@hospital.com" type="email" required autofocus/>
        </div>
    </div>
    <div class="space-y-stack-sm">
        <div class="flex justify-between items-center">
            <label class="block text-label-md font-label-md text-on-surface" for="password">Password</label>
            <a class="text-label-sm font-label-sm text-primary hover:underline" href="#">Forgot Password?</a>
        </div>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="material-symbols-outlined text-outline-variant text-[20px]">lock</span>
            </div>
            <input class="block w-full pl-12 pr-10 py-3 border border-outline-variant rounded-lg bg-surface-container-low text-on-surface text-body-md focus:ring-2 focus:ring-primary focus:border-primary transition-all outline-none" id="password" name="password" placeholder="••••••••" type="password" required/>
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer">
                <span class="material-symbols-outlined text-outline-variant text-[20px] hover:text-on-surface">visibility</span>
            </div>
        </div>
    </div>
    <div class="flex items-center pt-stack-sm">
        <input class="h-4 w-4 text-primary focus:ring-primary border-outline-variant rounded" id="remember-me" name="remember" type="checkbox"/>
        <label class="ml-2 block text-label-sm font-label-sm text-on-surface-variant" for="remember-me">
            Remember this device for 30 days
        </label>
    </div>
    <button class="w-full bg-primary-container text-on-primary-container hover:bg-primary hover:text-white transition-all py-3.5 rounded-lg text-label-md font-label-md flex items-center justify-center gap-2 active:scale-[0.98]" type="submit">
        Login to Dashboard
    </button>
</form>
<div class="relative my-stack-lg">
<div class="absolute inset-0 flex items-center">
<div class="w-full border-t border-outline-variant"></div>
</div>
<div class="relative flex justify-center text-label-sm font-label-sm">
<span class="px-4 bg-surface-container-lowest text-on-surface-variant">OR CONTINUE WITH</span>
</div>
</div>
<div class="grid grid-cols-2 gap-stack-md">
<button class="flex items-center justify-center gap-2 py-2.5 border border-outline-variant rounded-lg text-label-md font-label-md hover:bg-surface-container-low transition-colors">
<img alt="Google" class="w-5 h-5" src="https://lh3.googleusercontent.com/aida-public/AB6AXuD7_LwjlCz1p302om5yV4moKcsCSJG5uiglXPkEpVEYwfqBGJBvJz3TqDyAbfSgA1ZFSt5lB5VyfUxINM_plGWD0_ed8dpW53kkbvXQAK5kZrYY-KOw4RCzEoy5dv4EUXeezb9gdsvjubE_UxVKXuBePXb-SVpvu_TtSVoryKVJiN8cD3J9SvCFD0QcYKuuVzgn-1CVNTVAR75MN8QIzvcneqO17uisTVyOTvjmBGmbD7aSJN9n1-X89_uttNZp6gc-C-gftADgPEBl"/>
                            Google
                        </button>
<button class="flex items-center justify-center gap-2 py-2.5 border border-outline-variant rounded-lg text-label-md font-label-md hover:bg-surface-container-low transition-colors">
<span class="material-symbols-outlined text-[20px]">fingerprint</span>
                            Biometric
                        </button>
</div>
<p class="mt-stack-lg text-center text-body-md font-body-md text-on-surface-variant">
                        New to DiaPredict? 
                        <a class="text-primary font-bold hover:underline" href="{{ route('register') }}">Sign Up</a>
</p>
</div>
</div>
</div>
</main>
@endsection

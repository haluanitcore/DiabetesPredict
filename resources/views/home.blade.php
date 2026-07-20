@extends('layouts.app')

@section('content')
<main class="flex-grow w-full max-w-container-max mx-auto px-gutter py-stack-lg flex flex-col gap-12 lg:gap-24">
<!-- Hero Section (Asymmetric) -->
<section class="grid grid-cols-1 lg:grid-cols-2 gap-stack-lg items-center pt-8 lg:pt-16">
<div class="flex flex-col gap-stack-md max-w-xl">
<div class="inline-flex items-center gap-2 bg-primary-fixed/20 text-primary px-3 py-1 rounded-full w-max mb-2">
<span class="material-symbols-outlined text-sm">verified</span>
<span class="text-label-sm font-label-sm">Clinical Precision Tools</span>
</div>
<h1 class="text-headline-lg font-headline-lg text-on-surface">Predictive Insights for Better Health Outcomes.</h1>
<p class="text-body-lg font-body-lg text-on-surface-variant">
                    DiaPredict utilizes advanced clinical data models to provide accurate early-warning indicators for diabetes risk. Empower yourself with professional-grade analysis designed for proactive health management.
                </p>
<div class="flex items-center gap-4 mt-4">
                    <a href="{{ route('analysis.form') }}" class="bg-primary text-on-primary rounded-lg px-6 py-3 text-label-md font-label-md hover:opacity-90 transition-opacity">Start Analysis</a>
                    <a href="#how-it-works" class="border border-outline text-primary rounded-lg px-6 py-3 text-label-md font-label-md hover:bg-surface-variant transition-colors">Learn How It Works</a>
                </div>
            </div>
            <div class="relative w-full aspect-[4/3] rounded-xl overflow-hidden border border-outline-variant shadow-sm bg-surface-container-lowest">
                <img alt="Medical professional analyzing data" class="object-cover w-full h-full" data-alt="A modern, well-lit clinical environment showcasing a healthcare professional reviewing predictive health data on a sleek digital tablet. The scene is illuminated by soft, bright natural light reflecting off clean white surfaces, establishing a trustworthy and meticulous atmosphere. The color palette emphasizes crisp whites, subtle grays, and accents of medical blue to match a clean, minimalist UI aesthetic. The overall mood is professional, calm, and focused on patient-centered technological advancement." src="https://lh3.googleusercontent.com/aida-public/AB6AXuB_JKeT1zlIg4Vd4toe1mK7X0pLI5ap-2LC2LypmOgGIP4wc0dewx4KN8XJfKBR8r4SZZido9S01o4dp87FFGPyzZFsTKI7Ajae1rhDbKwCpq6xM_5dq1WDj9OhKPhSHLqo-dbiQrU7r1_yxu2I28c5mAi4xmYfKhWGHDwl0e-f3XBuN0m8DCKUXullEqK-M5Z89LMR114dHK5iTsIcDPpdCL44ga741-cPfF9pnsHDnKqY3LNCgap7Q_MuvHDcnt5kczYa8JZauId8"/>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-12 border-y border-outline-variant scroll-mt-20">
            <div class="flex flex-col gap-8">
                <div class="text-center max-w-2xl mx-auto space-y-2">
                    <h2 class="text-headline-md font-headline-md text-on-surface">How DiaPredict Works</h2>
                    <p class="text-body-md font-body-md text-on-surface-variant text-balance">Our AI-driven system follows a rigorous three-step process to ensure accurate health risk assessments.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Step 1 -->
                    <div class="flex flex-col items-center text-center p-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px]">edit_note</span>
                        </div>
                        <h3 class="text-title-lg font-title-lg text-on-surface">1. Secure Data Input</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant">You provide 8 key medical metrics including glucose levels, BMI, and age through our secure, encrypted form.</p>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex flex-col items-center text-center p-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-secondary/10 text-secondary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px]">psychology</span>
                        </div>
                        <h3 class="text-title-lg font-title-lg text-on-surface">2. AI Inference</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant">Our Random Forest ML model, trained on thousands of clinical cases, analyzes the complex patterns within your health data.</p>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex flex-col items-center text-center p-6 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-tertiary/10 text-tertiary flex items-center justify-center">
                            <span class="material-symbols-outlined text-[32px]">clinical_notes</span>
                        </div>
                        <h3 class="text-title-lg font-title-lg text-on-surface">3. Immediate Results</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant">Get an instant probability score and risk level classification, complete with actionable insights for your next checkup.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Educational Section (Bento Grid) -->
        <section class="flex flex-col gap-stack-lg py-8">
            <div class="flex flex-col gap-2 max-w-2xl">
                <h2 class="text-headline-md font-headline-md text-on-surface">Understanding the Variables</h2>
                <p class="text-body-md font-body-md text-on-surface-variant">Early detection relies on recognizing the subtle interplay of various health markers. Educate yourself on the key areas our predictive model analyzes.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4 md:col-span-2 hover:border-primary/50 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 group">
                    <div class="w-12 h-12 rounded-full bg-secondary-fixed/20 flex items-center justify-center text-secondary group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined" data-weight="fill" style="font-variation-settings: 'FILL' 1;">vital_signs</span>
                    </div>
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface mb-2">Clinical Symptoms & Markers</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant leading-relaxed">
                            Our analysis evaluates historical markers including elevated fasting glucose, unexpected weight fluctuations, and persistent fatigue. Identifying these early patterns is crucial for establishing an accurate baseline risk profile before full onset.
                        </p>
                    </div>
                    <a class="text-secondary font-label-md text-label-md mt-auto inline-flex items-center gap-1 hover:gap-2 transition-all" href="#">Read Clinical Guidelines <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
                </div>
                <!-- Card 2 -->
                <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4 hover:border-primary/50 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 group">
                    <div class="w-12 h-12 rounded-full bg-tertiary-fixed/30 flex items-center justify-center text-tertiary group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined" data-weight="fill" style="font-variation-settings: 'FILL' 1;">shield_with_heart</span>
                    </div>
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface mb-2">Preventative Measures</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant leading-relaxed">
                            Proactive intervention through dietary adjustments and guided physical activity can significantly alter predicted trajectories.
                        </p>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4 hover:border-primary/50 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 group">
                    <div class="w-12 h-12 rounded-full bg-primary-fixed/30 flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined" data-weight="fill" style="font-variation-settings: 'FILL' 1;">search_insights</span>
                    </div>
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface mb-2">Importance of Early Detection</h3>
                        <p class="text-body-md font-body-md text-on-surface-variant leading-relaxed">
                            Catching pre-diabetic indicators early provides the widest window for successful management and outcome optimization.
                        </p>
                    </div>
                </div>
                <!-- Card 4 (Data driven) -->
                <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4 md:col-span-2 hover:border-primary/50 transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 group">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">Data-Driven Accuracy</h3>
                        <span class="material-symbols-outlined text-outline group-hover:rotate-12 transition-transform">analytics</span>
                    </div>
                    <p class="text-body-md font-body-md text-on-surface-variant leading-relaxed">
                        By inputting standard biometric data—such as age, BMI, and recent lab results—DiaPredict cross-references millions of clinical data points to generate a personalized risk assessment with high statistical reliability.
                    </p>
                </div>
            </div>
        </section>

        <!-- Final CTA -->
        <section class="bg-surface-container border border-outline-variant rounded-2xl p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-6 my-8">
            <div class="flex flex-col gap-2 max-w-lg">
                <h2 class="text-headline-md font-headline-md text-on-surface">Ready to assess your risk?</h2>
                <p class="text-body-md font-body-md text-on-surface-variant">The process takes less than 5 minutes and provides immediate, medically-informed insights.</p>
            </div>
            <a href="{{ route('analysis.form') }}" class="bg-primary text-on-primary rounded-lg px-8 py-4 text-label-md font-label-md whitespace-nowrap hover:bg-primary-container hover:text-on-primary-container transition-colors">Go to Analysis</a>
        </section>
</main>
@endsection

@extends('layouts.app')

@section('content')
<main class="flex-grow w-full max-w-container-max mx-auto px-gutter py-stack-lg flex flex-col md:flex-row gap-12">
    <!-- Sticky Sidebar Navigation -->
    <aside class="md:w-1/4 h-fit md:sticky md:top-24 space-y-4">
        <h2 class="text-headline-sm font-headline-sm text-on-surface mb-6">Legal Information</h2>
        <nav class="flex flex-col gap-2">
            <a href="#privacy-policy" class="text-label-md font-label-md text-on-surface-variant hover:text-primary hover:bg-primary/5 px-4 py-2 rounded-lg transition-all border-l-2 border-transparent hover:border-primary">Privacy Policy</a>
            <a href="#terms-of-service" class="text-label-md font-label-md text-on-surface-variant hover:text-primary hover:bg-primary/5 px-4 py-2 rounded-lg transition-all border-l-2 border-transparent hover:border-primary">Terms of Service</a>
            <a href="#medical-disclaimer" class="text-label-md font-label-md text-on-surface-variant hover:text-primary hover:bg-primary/5 px-4 py-2 rounded-lg transition-all border-l-2 border-transparent hover:border-primary">Medical Disclaimer</a>
            <a href="#support" class="text-label-md font-label-md text-on-surface-variant hover:text-primary hover:bg-primary/5 px-4 py-2 rounded-lg transition-all border-l-2 border-transparent hover:border-primary">Support</a>
        </nav>
    </aside>

    <!-- Content Sections -->
    <article class="md:w-3/4 space-y-16">
        <!-- Privacy Policy -->
        <section id="privacy-policy" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">shield</span>
                </div>
                <h1 class="text-headline-md font-headline-md text-on-surface">Privacy Policy</h1>
            </div>
            <div class="prose prose-slate max-w-none text-body-md font-body-md text-on-surface-variant space-y-4">
                <p>Your privacy is of paramount importance to DiaPredict. This policy outlines how we collect, use, and safeguard your clinical and personal data.</p>
                <h3 class="text-title-lg font-title-lg text-on-surface mt-6">1. Data Collection</h3>
                <p>We only collect the medical metrics necessary for our predictive models. This data is associated with your account to provide you with a history of your analyses.</p>
                <h3 class="text-title-lg font-title-lg text-on-surface mt-6">2. Data Usage</h3>
                <p>The health data provided is used exclusively to generate risk assessments through our AI models. We do not sell or share your individual health data with third-party advertisers.</p>
                <h3 class="text-title-lg font-title-lg text-on-surface mt-6">3. Data Security</h3>
                <p>We implement industry-standard encryption and security measures to protect your information from unauthorized access or disclosure.</p>
            </div>
        </section>

        <!-- Terms of Service -->
        <section id="terms-of-service" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">gavel</span>
                </div>
                <h2 class="text-headline-md font-headline-md text-on-surface">Terms of Service</h2>
            </div>
            <div class="prose prose-slate max-w-none text-body-md font-body-md text-on-surface-variant space-y-4">
                <p>By using DiaPredict, you agree to comply with and be bound by the following terms and conditions of use.</p>
                <h3 class="text-title-lg font-title-lg text-on-surface mt-6">1. Acceptance of Terms</h3>
                <p>The services provided by DiaPredict are subject to these Terms of Service. We reserve the right to update these terms at any time without notice.</p>
                <h3 class="text-title-lg font-title-lg text-on-surface mt-6">2. User Conduct</h3>
                <p>Users are responsible for maintaining the confidentiality of their account information and for all activities that occur under their account.</p>
            </div>
        </section>

        <!-- Medical Disclaimer -->
        <section id="medical-disclaimer" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center text-error">
                    <span class="material-symbols-outlined">warning</span>
                </div>
                <h2 class="text-headline-md font-headline-md text-on-surface">Medical Disclaimer</h2>
            </div>
            <div class="bg-error-container/30 border border-error/20 p-6 rounded-xl space-y-4">
                <p class="text-body-md font-body-md text-on-error-container font-semibold">
                    DiaPredict is a predictive tool for informational purposes only and is NOT a substitute for professional medical advice, diagnosis, or treatment.
                </p>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Our AI models provide risk assessments based on statistical patterns in clinical data. These assessments are not clinical diagnoses. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition.
                </p>
                <p class="text-body-md font-body-md text-on-surface-variant italic">
                    Never disregard professional medical advice or delay in seeking it because of something you have read on this application.
                </p>
            </div>
        </section>

        <!-- Support -->
        <section id="support" class="scroll-mt-24">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-tertiary/10 flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined">contact_support</span>
                </div>
                <h2 class="text-headline-md font-headline-md text-on-surface">Support</h2>
            </div>
            <div class="bg-surface-container border border-outline-variant p-8 rounded-xl flex flex-col md:flex-row items-center gap-8">
                <div class="flex-1 space-y-4">
                    <h3 class="text-title-lg font-title-lg text-on-surface">Need help or have questions?</h3>
                    <p class="text-body-md font-body-md text-on-surface-variant">
                        Our technical team is available to assist you with any issues regarding your account, data access, or the analysis process.
                    </p>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-3 text-primary">
                            <span class="material-symbols-outlined">mail</span>
                            <span class="text-label-md font-label-md">support@diapredict.com</span>
                        </div>
                        <div class="flex items-center gap-3 text-primary">
                            <span class="material-symbols-outlined">help_center</span>
                            <span class="text-label-md font-label-md">Help Center & FAQ</span>
                        </div>
                    </div>
                </div>
                <div class="w-full md:w-auto">
                    <a href="mailto:support@diapredict.com" class="bg-primary text-on-primary px-8 py-3 rounded-lg text-label-md font-label-md hover:bg-primary-container transition-colors inline-block text-center">Contact Support</a>
                </div>
            </div>
        </section>
    </article>
</main>
@endsection

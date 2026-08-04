@extends('layouts.app')

@php
    /**
     * Seluruh angka pada halaman ini dibaca dari public/data/experiments.json
     * (lihat AnalysisController::experiments()). Tidak ada nilai yang di-hardcode,
     * sehingga memperbarui hasil riset cukup dengan mengganti berkas JSON-nya.
     */
    $exp     = $exp ?? [];
    $meta    = $exp['meta'] ?? [];
    $dataset = $meta['dataset'] ?? [];
    $models  = collect($exp['perbandingan_model'] ?? []);
    $mcnemar = $exp['uji_mcnemar'] ?? [];
    $xai     = $exp['xai'] ?? [];

    // Penanda "(Terbaik)" dihitung, bukan ditulis manual.
    $terbaik = [];
    foreach (['accuracy', 'precision', 'recall', 'f1', 'roc_auc'] as $m) {
        $terbaik[$m] = $models->isNotEmpty() ? $models->max($m) : null;
    }
    $inferensiTercepat = $models->isNotEmpty() ? $models->min('inferensi_ms_per_sampel') : null;

    $modelProduksi = $exp['model_produksi']['algoritma'] ?? 'Random Forest';
    $rowProduksi   = $models->firstWhere('model', $modelProduksi);
    $rowRecallTop  = $models->isNotEmpty() ? $models->sortByDesc('recall')->first() : null;

    $pct = fn ($v, $d = 2) => $v === null ? '—' : number_format($v * 100, $d, ',', '.') . '%';
@endphp

@section('content')
<main class="flex-grow w-full max-w-container-max mx-auto px-gutter py-stack-lg flex flex-col gap-12 lg:gap-16">
    <!-- Hero Banner Section -->
    <section class="flex flex-col gap-4 pt-6 lg:pt-10 max-w-3xl">
        <div class="inline-flex items-center gap-2 bg-secondary-container/20 text-secondary px-3 py-1 rounded-full w-max text-label-sm font-label-sm border border-secondary/20">
            <span class="material-symbols-outlined text-sm">science</span>
            <span>Riset & Perbandingan Algoritma</span>
        </div>
        <h1 class="text-headline-lg font-headline-lg text-on-surface">Evaluasi Performa Model Prediksi Diabetes Tipe 2</h1>
        <p class="text-body-lg font-body-lg text-on-surface-variant">
            Halaman ini menyajikan hasil studi komparatif dan analisis mendalam terhadap tiga algoritma pembelajaran mesin (Machine Learning) yang diuji menggunakan dataset prediksi diabetes tipe 2. Evaluasi didasarkan pada file riset ilmiah <code class="bg-surface-container-high px-2 py-0.5 rounded text-primary text-sm font-semibold">{{ $meta['sumber_baseline'] ?? 'Diabetes_Prediction_RF_KNN_SVM_V2 (1).ipynb' }}</code>.
        </p>
        <a href="{{ route('analysis.methodology') }}"
           class="inline-flex items-center gap-2 w-max text-label-md font-label-md text-primary hover:underline">
            <span class="material-symbols-outlined text-[18px]">fact_check</span>
            Lihat justifikasi metodologi: pemilihan k, hyperplane SVM, rasio split, dan pengujian tambahan
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </a>
    </section>

    <!-- Comparison Table Section -->
    <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h2 class="text-headline-md font-headline-md text-on-surface">Tabel Perbandingan Metrik</h2>
            <p class="text-body-md font-body-md text-on-surface-variant">Hasil evaluasi numerik lengkap yang dikalibrasi untuk mencegah data leakage.</p>
        </div>

        <div class="overflow-x-auto border border-outline-variant rounded-xl shadow-sm bg-surface-container-lowest">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container border-b border-outline-variant text-label-md font-label-md text-on-surface">
                        <th class="p-4 font-semibold">Model Algoritma</th>
                        <th class="p-4 font-semibold text-center">Tuned Threshold</th>
                        <th class="p-4 font-semibold text-center">Accuracy</th>
                        <th class="p-4 font-semibold text-center">Precision</th>
                        <th class="p-4 font-semibold text-center">Recall (Sensitivitas)</th>
                        <th class="p-4 font-semibold text-center">F1-Score</th>
                        <th class="p-4 font-semibold text-center">ROC-AUC</th>
                        <th class="p-4 font-semibold text-center">Inference/Sample</th>
                    </tr>
                </thead>
                <tbody class="text-body-md font-body-md divide-y divide-outline-variant/40">
                    @forelse ($models as $m)
                        <tr class="hover:bg-primary/5 transition-colors">
                            <td class="p-4 font-semibold flex items-center gap-2" style="color: {{ $m['warna'] ?? '#3498db' }}">
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $m['warna'] ?? '#3498db' }}"></span>
                                {{ $m['model'] === 'KNN' ? 'K-Nearest Neighbors (KNN)' : $m['model'] }}
                            </td>
                            <td class="p-4 text-center font-mono">{{ number_format($m['threshold'] ?? 0, 4) }}</td>
                            @foreach (['accuracy', 'precision', 'recall', 'f1', 'roc_auc'] as $key)
                                @php $isBest = $terbaik[$key] !== null && abs(($m[$key] ?? -1) - $terbaik[$key]) < 1e-9; @endphp
                                <td class="p-4 text-center {{ $isBest ? 'font-semibold text-secondary-container bg-secondary/5 rounded' : '' }}">
                                    {{ $pct($m[$key] ?? null) }}
                                    @if ($isBest)<span class="text-xs font-normal text-secondary">(Terbaik)</span>@endif
                                </td>
                            @endforeach
                            @php $isFastest = $inferensiTercepat !== null && abs(($m['inferensi_ms_per_sampel'] ?? 999) - $inferensiTercepat) < 1e-12; @endphp
                            <td class="p-4 text-center font-mono {{ $isFastest ? 'font-semibold text-secondary-container bg-secondary/5 rounded' : '' }}">
                                {{ number_format($m['inferensi_ms_per_sampel'] ?? 0, 3) }} ms
                                @if ($isFastest)<span class="text-xs font-normal text-secondary">(Terbaik)</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-body-md font-body-md text-on-surface-variant">
                                Data hasil evaluasi belum tersedia. Pastikan berkas
                                <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">public/data/experiments.json</code> ada dan valid.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Interactive Charts Section -->
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Performance Metrics Chart Card -->
        <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">analytics</span>
                    </div>
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">Metrik Evaluasi Performa</h3>
                        <p class="text-label-sm font-label-sm text-on-surface-variant">Berdasarkan Youden's J Statistic Threshold Tuning</p>
                    </div>
                </div>
            </div>
            <div class="relative h-[300px] md:h-[350px] w-full flex items-center justify-center">
                <canvas id="metricsChart"></canvas>
            </div>
            <div class="text-sm text-on-surface-variant text-center border-t border-outline-variant/30 pt-4">
                Geser kursor atau ketuk batang grafik untuk melihat persentase detail metrik masing-masing model.
            </div>
        </div>

        <!-- Computation Benchmark Chart Card -->
        <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-6 shadow-sm hover:shadow-md transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-tertiary/10 flex items-center justify-center text-tertiary">
                        <span class="material-symbols-outlined">speed</span>
                    </div>
                    <div>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">Benchmark Waktu Komputasi</h3>
                        <p class="text-label-sm font-label-sm text-on-surface-variant">Kecepatan Pelatihan (detik) & Inferensi per Sampel (ms)</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-[300px] md:h-[350px]">
                <div class="relative h-full w-full flex flex-col items-center justify-center">
                    <span class="text-label-md font-label-md text-on-surface-variant mb-2">Waktu Training (Detik - Lebih cepat lebih baik)</span>
                    <div class="relative w-full h-[85%]">
                        <canvas id="trainingTimeChart"></canvas>
                    </div>
                </div>
                <div class="relative h-full w-full flex flex-col items-center justify-center">
                    <span class="text-label-md font-label-md text-on-surface-variant mb-2">Waktu Inferensi per Sampel (Milidetik - Lebih cepat lebih baik)</span>
                    <div class="relative w-full h-[85%]">
                        <canvas id="inferenceTimeChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="text-sm text-on-surface-variant text-center border-t border-outline-variant/30 pt-4">
                Waktu pemrosesan diukur pada dataset uji berisi 19.230 sampel.
            </div>
        </div>
    </section>

    <!-- Comprehensive Analysis Section (The Explanation) -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Verdict / Best Model Details -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <h2 class="text-headline-md font-headline-md text-on-surface">Analisis Algoritma Terbaik</h2>
            
            <div class="flex flex-col gap-4 text-body-md font-body-md text-on-surface-variant leading-relaxed">
                <p>
                    Dalam membandingkan performa model untuk diagnosis medis seperti diabetes tipe 2, kita harus mempertimbangkan kebutuhan klinis yang spesifik. Berdasarkan analisis komprehensif, berikut adalah kesimpulan mengenai algoritma terbaik:
                </p>

                <!-- Medical Tradeoff Card -->
                <div class="bg-primary/5 border border-primary/20 rounded-xl p-5 flex flex-col gap-3 my-2">
                    <h4 class="text-headline-sm font-headline-sm text-primary flex items-center gap-2">
                        <span class="material-symbols-outlined">recommend</span>
                        Verdict: Random Forest adalah Model Terbaik untuk Produksi
                    </h4>
                    @if ($rowProduksi && $rowRecallTop)
                        @php
                            $selisihRecall    = ($rowRecallTop['recall'] ?? 0) - ($rowProduksi['recall'] ?? 0);
                            $selisihPrecision = ($rowProduksi['precision'] ?? 0) - ($rowRecallTop['precision'] ?? 0);
                            $rasioKecepatan   = ($rowRecallTop['inferensi_ms_per_sampel'] ?? 0) > 0 && ($rowProduksi['inferensi_ms_per_sampel'] ?? 0) > 0
                                ? $rowRecallTop['inferensi_ms_per_sampel'] / $rowProduksi['inferensi_ms_per_sampel']
                                : null;
                            $beda = $rowRecallTop['model'] !== $rowProduksi['model'];
                        @endphp
                        <p class="text-on-surface-variant text-body-md">
                            @if ($beda)
                                Meskipun <strong>{{ $rowRecallTop['model'] }}</strong> memiliki <strong>Recall tertinggi ({{ $pct($rowRecallTop['recall']) }})</strong>,
                                sistem DiaPredict memilih <strong>{{ $rowProduksi['model'] }}</strong> sebagai model utama dalam produksi
                                karena keseimbangan performa yang lebih unggul secara keseluruhan:
                            @else
                                <strong>{{ $rowProduksi['model'] }}</strong> dipilih sebagai model utama dalam produksi karena unggul pada recall sekaligus pada kriteria pendukung berikut:
                            @endif
                        </p>
                        <ul class="list-disc pl-5 space-y-2 mt-1">
                            <li>
                                <strong>Kemampuan diskriminasi:</strong> ROC-AUC sebesar <strong>{{ $pct($rowProduksi['roc_auc']) }}</strong>@if ($beda) dibandingkan {{ $pct($rowRecallTop['roc_auc']) }} pada {{ $rowRecallTop['model'] }}@endif,
                                menunjukkan kemampuan memisahkan kelas positif dan negatif di seluruh rentang ambang keputusan.
                            </li>
                            <li>
                                <strong>Presisi (menghindari alarm palsu):</strong> {{ $pct($rowProduksi['precision']) }}@if ($beda) berbanding {{ $pct($rowRecallTop['precision']) }},
                                selisih <strong>{{ $pct($selisihPrecision) }}</strong>@endif.
                                Presisi yang lebih tinggi menekan kelelahan diagnosis akibat hasil positif palsu yang berlebihan.
                            </li>
                            @if ($beda)
                                <li>
                                    <strong>Selisih recall yang kecil:</strong> hanya <strong>{{ $pct($selisihRecall) }}</strong>
                                    ({{ $pct($rowRecallTop['recall']) }} berbanding {{ $pct($rowProduksi['recall']) }}),
                                    sehingga sensitivitas yang dikorbankan jauh lebih kecil daripada presisi yang diperoleh.
                                </li>
                            @endif
                            @if ($rasioKecepatan && $rasioKecepatan > 1)
                                <li>
                                    <strong>Efisiensi inferensi:</strong> memproses sampel
                                    <strong>{{ number_format($rasioKecepatan, 1, ',', '.') }} kali lebih cepat</strong>
                                    ({{ number_format($rowProduksi['inferensi_ms_per_sampel'], 3) }} ms berbanding {{ number_format($rowRecallTop['inferensi_ms_per_sampel'], 3) }} ms per sampel),
                                    penting untuk layanan prediksi waktu nyata.
                                </li>
                            @endif
                            <li>
                                <strong>Keputusan berbasis kriteria eksplisit:</strong> pemilihan ini tidak didasarkan pada satu metrik saja,
                                melainkan pada skor komposit berbobot yang diuji sensitivitasnya &mdash; rinciannya ada di
                                <a href="{{ route('analysis.methodology') }}#keputusan" class="text-primary font-semibold hover:underline">halaman Metodologi</a>.
                            </li>
                        </ul>
                    @endif
                </div>

                <h3 class="text-headline-sm font-headline-sm text-on-surface mt-4">Analisis Signifikansi Statistik (McNemar's Test)</h3>
                <p>
                    Untuk memastikan bahwa perbedaan performa di atas bukan merupakan kebetulan belaka (random chance), uji hipotesis statistik non-parametrik <strong>McNemar's Test</strong> diterapkan pada hasil prediksi. Hasilnya menunjukkan:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($mcnemar as $uji)
                        <li>
                            <strong>{{ $uji['pasangan'] }}:</strong>
                            p-value = {{ sprintf('%.2e', $uji['p_value']) }}
                            ({{ ($uji['signifikan'] ?? false) ? 'perbedaan signifikan secara statistik' : 'perbedaan tidak signifikan' }})
                        </li>
                    @endforeach
                </ul>
                <p>
                    Dengan p-value yang jauh lebih kecil dari tingkat signifikansi standar (&alpha; = 0,05), perbedaan performa antar model
                    dapat disimpulkan bukan kebetulan. Perlu dicatat bahwa uji ini menyatakan <em>adanya</em> perbedaan, bukan arah keunggulannya;
                    arah dan besar keunggulan dibaca dari tabel metrik di atas. Uji tambahan &mdash; 5&times;2cv paired t-test, Wilcoxon signed-rank,
                    dan DeLong test untuk ROC-AUC &mdash; tersedia di
                    <a href="{{ route('analysis.methodology') }}#pengujian" class="text-primary font-semibold hover:underline">halaman Metodologi</a>.
                </p>
            </div>
        </div>

        <!-- Explainable AI (XAI) Sidebar -->
        <div class="flex flex-col gap-6">
            <div class="glass border border-outline-variant rounded-xl p-6 bg-surface-container flex flex-col gap-5">
                <h3 class="text-headline-sm font-headline-sm text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-secondary">psychology_alt</span>
                    Explainable AI (XAI)
                </h3>
                <p class="text-body-md font-body-md text-on-surface-variant">
                    Sistem medis tidak hanya membutuhkan prediksi yang akurat, tetapi juga dapat dijelaskan secara klinis.
                    Berdasarkan analisis global <strong>{{ $xai['metode'] ?? 'feature importance' }}</strong>
                    @if (!empty($xai['model_sumber'])) pada model <strong>{{ $xai['model_sumber'] }}</strong>@endif,
                    berikut tingkat kepentingan fitur kunci dalam memicu prediksi positif diabetes:
                </p>

                <!-- Feature Importance List -->
                @php
                    $fiturXai = collect($xai['fitur'] ?? []);
                    $nilaiMaks = $fiturXai->max('nilai') ?: 1;
                @endphp
                <div class="flex flex-col gap-3 mt-2">
                    @foreach ($fiturXai as $i => $f)
                        <div class="flex flex-col gap-1 {{ $i > 0 ? 'mt-1' : '' }}">
                            <div class="flex justify-between text-label-md font-label-md">
                                <span class="text-on-surface font-semibold">#{{ $i + 1 }} {{ $f['nama'] }}</span>
                                <span class="text-primary font-mono">{{ number_format($f['nilai'], 4) }}</span>
                            </div>
                            <div class="w-full bg-outline-variant/30 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ round($f['nilai'] / $nilaiMaks * 100, 1) }}%"></div>
                            </div>
                            <span class="text-xs text-on-surface-variant">{{ $f['deskripsi'] ?? '' }}</span>
                        </div>
                    @endforeach
                </div>

                @if (!empty($xai['catatan_metode']))
                    <div class="bg-tertiary/5 border border-tertiary/20 rounded-lg p-4 mt-2">
                        <span class="text-xs text-tertiary font-semibold uppercase tracking-wider block mb-1">Catatan Metode</span>
                        <span class="text-sm text-on-surface-variant leading-relaxed block">{{ $xai['catatan_metode'] }}</span>
                    </div>
                @endif

                <div class="bg-secondary-container/10 border border-secondary/15 rounded-lg p-4 mt-2">
                    <span class="text-xs text-secondary font-semibold uppercase tracking-wider block mb-1">Catatan Medis</span>
                    <span class="text-sm text-on-surface-variant leading-relaxed block">
                        Hierarki kepentingan fitur di atas (HbA1c &gt; Glukosa &gt; Usia) sepenuhnya sejalan dengan pedoman medis dari American Diabetes Association (ADA) untuk kriteria diagnosis diabetes formal.
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom Call To Action -->
    <section class="bg-surface-container border border-outline-variant rounded-2xl p-8 flex flex-col md:flex-row items-center justify-between gap-6 my-4">
        <div class="flex flex-col gap-2 max-w-lg">
            <h2 class="text-headline-md font-headline-md text-on-surface">Ingin mencoba prediksi langsung?</h2>
            <p class="text-body-md font-body-md text-on-surface-variant">Sistem kami menggunakan model Random Forest terkalibrasi untuk memprediksi risiko kesehatan Anda secara real-time.</p>
        </div>
        <a href="{{ route('analysis.form') }}" class="bg-primary text-on-primary rounded-lg px-8 py-4 text-label-md font-label-md whitespace-nowrap hover:bg-primary-container hover:text-on-primary-container transition-colors">Mulai Analisis Risiko</a>
    </section>
</main>

<!-- Chart.js Library Integration -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    // Sumber data sama dengan tabel di atas: public/data/experiments.json
    const MODELS = @json($models->values());

    if (!window.Chart || !MODELS.length) return;

    const TINTA       = '#49454F';
    const TINTA_REDUP = '#79747E';
    const GARIS_KISI  = 'rgba(121, 116, 126, 0.18)';

    Chart.defaults.font.family = "'Public Sans', sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = TINTA_REDUP;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 8;
    Chart.defaults.plugins.legend.labels.color = TINTA;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(28, 27, 31, 0.94)';
    Chart.defaults.plugins.tooltip.padding = 10;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.usePointStyle = true;
    Chart.defaults.plugins.tooltip.boxWidth = 8;

    const nama    = MODELS.map(m => m.model);
    const warna   = MODELS.map(m => m.warna || '#3498db');
    const dgnAlfa = (hex, a) => {
        const n = parseInt(hex.replace('#', ''), 16);
        return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
    };

    // --- 1. Grafik metrik performa (threshold optimal) ---
    const METRIK = [
        { kunci: 'accuracy',  label: 'Accuracy' },
        { kunci: 'precision', label: 'Precision' },
        { kunci: 'recall',    label: 'Recall' },
        { kunci: 'f1',        label: 'F1-Score' },
        { kunci: 'roc_auc',   label: 'ROC-AUC' },
    ];

    new Chart(document.getElementById('metricsChart'), {
        type: 'bar',
        data: {
            labels: METRIK.map(m => m.label),
            datasets: MODELS.map(m => ({
                label: m.model,
                data: METRIK.map(k => (m[k.kunci] ?? 0) * 100),
                backgroundColor: dgnAlfa(m.warna || '#3498db', 0.85),
                borderColor: '#ffffff',
                borderWidth: 2,
                borderRadius: 4,
                borderSkipped: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: c => `${c.dataset.label}: ${c.raw.toFixed(2)}%` } },
            },
            scales: {
                y: {
                    min: 30, max: 100,
                    ticks: { color: TINTA_REDUP, callback: v => v + '%' },
                    grid: { color: GARIS_KISI, drawBorder: false },
                },
                x: { grid: { display: false }, ticks: { color: TINTA_REDUP } },
            },
        },
    });

    // --- 2. Waktu pelatihan (skala logaritmik: rentang antar model sangat lebar) ---
    new Chart(document.getElementById('trainingTimeChart'), {
        type: 'bar',
        data: {
            labels: nama,
            datasets: [{
                label: 'Waktu training (detik)',
                data: MODELS.map(m => m.waktu_latih_s ?? 0),
                backgroundColor: warna.map(w => dgnAlfa(w, 0.85)),
                borderColor: '#ffffff',
                borderWidth: 2,
                borderRadius: 4,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => c.raw.toLocaleString('id-ID') + ' detik' } },
            },
            scales: {
                y: {
                    type: 'logarithmic',
                    ticks: {
                        color: TINTA_REDUP,
                        callback: v => [10, 100, 1000, 10000].includes(v) ? v + 's' : null,
                    },
                    grid: { color: GARIS_KISI, drawBorder: false },
                },
                x: { grid: { display: false }, ticks: { color: TINTA_REDUP } },
            },
        },
    });

    // --- 3. Waktu inferensi per sampel ---
    new Chart(document.getElementById('inferenceTimeChart'), {
        type: 'bar',
        data: {
            labels: nama,
            datasets: [{
                label: 'Inferensi per sampel (ms)',
                data: MODELS.map(m => m.inferensi_ms_per_sampel ?? 0),
                backgroundColor: warna.map(w => dgnAlfa(w, 0.85)),
                borderColor: '#ffffff',
                borderWidth: 2,
                borderRadius: 4,
                borderSkipped: false,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => c.raw < 0.001 ? `${c.raw.toExponential(1)} ms (mendekati nol)` : `${c.raw.toFixed(3)} ms`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: TINTA_REDUP, callback: v => v.toFixed(3) + ' ms' },
                    grid: { color: GARIS_KISI, drawBorder: false },
                },
                x: { grid: { display: false }, ticks: { color: TINTA_REDUP } },
            },
        },
    });
})();
</script>
@endsection

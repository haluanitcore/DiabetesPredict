@extends('layouts.app')

@php
    /**
     * Panel perbandingan model untuk SATU pasien.
     *
     * Seluruh angka di bawah berasal dari controller ($perbandingan, $konsensus,
     * $modelTerbaik) dan dari public/data/experiments.json ($exp). Tidak ada angka
     * yang diketik manual, termasuk penanda "(tertinggi)" yang dihitung di sini.
     *
     * Bila $perbandingan kosong (rekaman lama), bagian ini menampilkan empty state
     * yang jujur, bukan angka nol atau bar kosong.
     */
    $perbandingan = is_array($perbandingan ?? null) ? $perbandingan : [];
    $konsensus    = is_array($konsensus ?? null) ? $konsensus : null;
    $modelTerbaik = is_array($modelTerbaik ?? null) ? $modelTerbaik : null;
    $exp          = is_array($exp ?? null) ? $exp : [];

    // Format Indonesia: koma desimal, titik pemisah ribuan.
    $persenId  = fn ($v, $d = 1) => $v === null ? '—' : number_format((float) $v, $d, ',', '.') . '%';
    $desimalId = fn ($v, $d = 4) => $v === null ? '—' : number_format((float) $v, $d, ',', '.');
    $bulatId   = fn ($v) => $v === null ? null : number_format((float) $v, 0, ',', '.');
    $jepit     = fn ($v) => max(0, min(100, (float) $v));

    /**
     * Baris tampilan per model.
     * Label hasil memakai ambang MASING-MASING model, bukan 50%: nilai `prediction`
     * dari controller dipakai lebih dulu, dan hanya bila tidak ada barulah dihitung
     * ulang dari probabilitas terhadap ambang model itu sendiri.
     */
    $barisModel = [];
    foreach ($perbandingan as $m) {
        if (!is_array($m)) {
            continue;
        }

        $prob   = isset($m['probability']) && is_numeric($m['probability']) ? (float) $m['probability'] : null;
        $persen = isset($m['persen']) && is_numeric($m['persen'])
            ? (float) $m['persen']
            : ($prob !== null ? $prob * 100 : null);
        $ambang = isset($m['threshold']) && is_numeric($m['threshold']) ? (float) $m['threshold'] : null;

        $pred = isset($m['prediction']) && $m['prediction'] !== null
            ? (int) $m['prediction']
            : (($prob !== null && $ambang !== null) ? ($prob >= $ambang ? 1 : 0) : null);

        $kode = $m['id'] ?? null;

        $barisModel[] = [
            'kode'             => $kode,
            'singkat'          => $kode ? strtoupper((string) $kode) : null,
            'nama'             => $m['nama'] ?? ($kode ? strtoupper((string) $kode) : 'Model'),
            'warna'            => $m['warna'] ?? '#3498db',
            'persen'           => $persen,
            'ambang'           => $ambang,
            'ambang_persen'    => $ambang !== null ? $ambang * 100 : null,
            'pred'             => $pred,
            'produksi'         => (bool) ($m['produksi'] ?? false),
            'k'                => isset($m['k']) && is_numeric($m['k']) ? (int) $m['k'] : null,
            'tetangga_positif' => isset($m['tetangga_positif']) && is_numeric($m['tetangga_positif']) ? (int) $m['tetangga_positif'] : null,
        ];
    }

    // Konsensus: pakai kiriman controller; hitung sendiri hanya bila tidak dikirim.
    $konsensusTampil = null;
    if ($konsensus !== null && (int) ($konsensus['total'] ?? 0) > 0) {
        $setuju = (int) ($konsensus['setuju'] ?? 0);
        $total  = (int) $konsensus['total'];
        $konsensusTampil = [
            'setuju' => $setuju,
            'total'  => $total,
            'bulat'  => (bool) ($konsensus['bulat'] ?? ($setuju === $total)),
            'label'  => (int) ($konsensus['label'] ?? 0),
        ];
    } elseif (count($barisModel) > 0) {
        $suara = array_values(array_filter(
            array_map(fn ($b) => $b['pred'], $barisModel),
            fn ($p) => $p !== null
        ));
        if (count($suara) > 0) {
            $positif = count(array_filter($suara, fn ($p) => $p === 1));
            $negatif = count($suara) - $positif;
            $label   = $positif >= $negatif ? 1 : 0;
            $setuju  = $label === 1 ? $positif : $negatif;
            $konsensusTampil = [
                'setuju' => $setuju,
                'total'  => count($suara),
                'bulat'  => $setuju === count($suara),
                'label'  => $label,
            ];
        }
    }

    // Ringkasan ambang tiap model untuk keterangan kecil (dibangun dari data, bukan diketik).
    $ringkasAmbang = [];
    foreach ($barisModel as $b) {
        if ($b['ambang'] !== null) {
            $ringkasAmbang[] = ($b['singkat'] ?? $b['nama']) . ' ' . $desimalId($b['ambang']);
        }
    }

    // Jumlah data uji riset: dipakai untuk menegaskan asal klaim "model terbaik".
    $nDataUji = $exp['meta']['dataset']['jumlah_data_uji']
        ?? $exp['justifikasi_split']['n_uji']
        ?? null;
    $nDataUjiTeks = is_numeric($nDataUji) ? $bulatId($nDataUji) : null;

    // --- Bahan Section B: metrik riset model produksi ---
    $metrikRiset  = is_array($modelTerbaik['metrik_riset'] ?? null) ? $modelTerbaik['metrik_riset'] : [];
    $namaProduksi = $modelTerbaik['nama']
        ?? $modelTerbaik['label']
        ?? $modelTerbaik['model']
        ?? $modelTerbaik['algoritma']
        ?? $exp['meta']['model_produksi']
        ?? 'Model Produksi';
    $warnaProduksi = $modelTerbaik['warna'] ?? '#3498db';

    /**
     * Penanda "(tertinggi)" dihitung dari $exp['perbandingan_model'], bukan ditulis manual.
     * Perbandingan dilakukan di dalam satu sumber yang sama (baris model produksi
     * terhadap nilai maksimum ketiga model), supaya tidak membandingkan angka
     * dari dua sumber dengan pembulatan berbeda.
     */
    $kunciMetrik    = ['accuracy', 'precision', 'recall', 'f1', 'roc_auc'];
    $nilaiTertinggi = [];
    foreach ($kunciMetrik as $k) {
        $nilai = [];
        foreach (($exp['perbandingan_model'] ?? []) as $r) {
            if (is_array($r) && isset($r[$k]) && is_numeric($r[$k])) {
                $nilai[] = (float) $r[$k];
            }
        }
        $nilaiTertinggi[$k] = count($nilai) > 0 ? max($nilai) : null;
    }

    // Baris model produksi di dalam tabel riset, dicocokkan longgar terhadap nama katalog.
    $sederhanakan  = fn ($s) => strtolower(preg_replace('/[^a-z0-9]/i', '', (string) $s));
    $namaProduksiN = $sederhanakan($namaProduksi);
    $barisProduksiRiset = null;
    foreach (($exp['perbandingan_model'] ?? []) as $r) {
        if (!is_array($r)) {
            continue;
        }
        $namaRiset = $sederhanakan($r['model'] ?? '');
        if ($namaRiset !== '' && $namaProduksiN !== ''
            && (str_contains($namaProduksiN, $namaRiset) || str_contains($namaRiset, $namaProduksiN))) {
            $barisProduksiRiset = $r;
            break;
        }
    }

    $daftarMetrik = [
        ['kunci' => 'roc_auc',   'label' => 'ROC-AUC',              'ket' => 'Kemampuan memisahkan pasien berisiko dan tidak, di seluruh rentang ambang.'],
        ['kunci' => 'recall',    'label' => 'Recall (Sensitivitas)', 'ket' => 'Porsi pasien diabetes yang berhasil terdeteksi — kesalahan paling berbahaya secara medis.'],
        ['kunci' => 'precision', 'label' => 'Precision',            'ket' => 'Porsi hasil "risiko tinggi" yang memang benar diabetes.'],
        ['kunci' => 'f1',        'label' => 'F1-Score',             'ket' => 'Keseimbangan antara recall dan precision.'],
        ['kunci' => 'accuracy',  'label' => 'Accuracy',             'ket' => 'Porsi prediksi benar atas seluruh data uji.'],
    ];
@endphp

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

    {{-- ============================================================
         SECTION A — Perbandingan antar model untuk pasien ini
         Tiga nilai saja: bar HTML/CSS dengan label langsung, bukan Chart.js.
         Warna mengikuti entitas model (RF biru, KNN merah, SVM hijau),
         sedangkan angka dan label tetap memakai warna teks.
         ============================================================ --}}
    <section id="perbandingan-model" class="bg-surface border border-outline-variant rounded-xl p-stack-lg flex flex-col shadow-sm scroll-mt-24">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant pb-4 mb-4">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">bar_chart</span>
                <h2 class="text-headline-sm font-headline-sm text-on-surface">Perbandingan Antar Model untuk Pasien Ini</h2>
            </div>

            @if ($konsensusTampil && count($barisModel) > 0)
                @if ($konsensusTampil['bulat'])
                    <span class="inline-flex items-center gap-1.5 text-label-sm font-label-sm text-secondary bg-secondary/10 border border-secondary/25 px-3 py-1 rounded-full">
                        <span class="material-symbols-outlined text-[16px]">check_circle</span>
                        {{ $konsensusTampil['setuju'] }} dari {{ $konsensusTampil['total'] }} model sepakat:
                        {{ $konsensusTampil['label'] === 1 ? 'Risiko Tinggi' : 'Risiko Rendah' }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-label-sm font-label-sm px-3 py-1 rounded-full border"
                          style="color: #8a5300; background-color: rgba(243, 156, 18, 0.12); border-color: rgba(243, 156, 18, 0.40);">
                        <span class="material-symbols-outlined text-[16px]">balance</span>
                        Model terbelah — {{ $konsensusTampil['setuju'] }} dari {{ $konsensusTampil['total'] }} model:
                        {{ $konsensusTampil['label'] === 1 ? 'Risiko Tinggi' : 'Risiko Rendah' }}
                    </span>
                @endif
            @endif
        </div>

        @if (count($barisModel) === 0)
            {{-- Empty state jujur: tidak ada angka nol, tidak ada bar kosong. --}}
            <div class="flex flex-col items-center justify-center gap-2 text-center border border-dashed border-outline-variant rounded-lg bg-surface-container-low px-6 py-10">
                <span class="material-symbols-outlined text-on-surface-variant text-[32px]">insights</span>
                <p class="text-body-md font-body-md text-on-surface">Perbandingan model belum tersedia untuk rekaman ini</p>
                <p class="text-label-sm font-label-sm text-on-surface-variant max-w-md leading-relaxed">
                    Rekaman ini dibuat sebelum sistem menyimpan keluaran ketiga model sekaligus, sehingga tidak ada
                    angka pembanding yang dapat ditampilkan. Analisis baru akan menyertakan perbandingan ini secara otomatis.
                </p>
            </div>
        @else
            <p class="text-body-md font-body-md text-on-surface-variant mb-5">
                Ketiga model dijalankan pada data pasien yang sama. Panjang bar menunjukkan probabilitas risiko
                menurut masing-masing model, dan garis vertikal di dalam bar adalah ambang keputusan milik model itu sendiri.
            </p>

            <div class="flex flex-col gap-6">
                @foreach ($barisModel as $b)
                    <div class="flex flex-col gap-2">
                        {{-- Baris identitas + angka --}}
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $b['warna'] }};" aria-hidden="true"></span>
                            <span class="text-label-md font-label-md text-on-surface">{{ $b['nama'] }}</span>

                            @if ($b['produksi'])
                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary bg-primary/10 border border-primary/25 px-2 py-0.5 rounded-full">
                                    <span class="material-symbols-outlined text-[13px]">verified</span>
                                    DIPAKAI
                                </span>
                            @endif

                            <span class="ml-auto flex items-baseline gap-2">
                                <span class="text-headline-sm font-headline-sm text-on-surface tabular-nums">
                                    {{ $b['persen'] === null ? '—' : $persenId($b['persen']) }}
                                </span>
                                @if ($b['pred'] !== null)
                                    <span class="text-label-sm font-label-sm {{ $b['pred'] === 1 ? 'text-error' : 'text-secondary' }}">
                                        {{ $b['pred'] === 1 ? 'Risiko Tinggi' : 'Risiko Rendah' }}
                                    </span>
                                @endif
                            </span>
                        </div>

                        {{-- Bar: warna entitas + penanda ambang model tersebut --}}
                        @if ($b['persen'] !== null)
                            <div class="relative h-3 w-full rounded-full bg-outline-variant/40">
                                <div class="absolute inset-y-0 left-0 rounded-full"
                                     style="width: {{ $jepit($b['persen']) }}%; background-color: {{ $b['warna'] }};"></div>
                                @if ($b['ambang_persen'] !== null)
                                    <span class="absolute -top-1.5 -bottom-1.5 w-[2px] rounded-full bg-on-surface"
                                          style="left: calc({{ $jepit($b['ambang_persen']) }}% - 1px);"
                                          aria-hidden="true"></span>
                                @endif
                            </div>
                        @endif

                        {{-- Keterangan pendamping --}}
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-label-sm font-label-sm text-on-surface-variant">
                            @if ($b['ambang'] !== null)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="inline-block w-[2px] h-3 rounded-full bg-on-surface" aria-hidden="true"></span>
                                    Ambang model ini: {{ $desimalId($b['ambang']) }} ({{ $persenId($b['ambang_persen']) }})
                                </span>
                            @endif
                            @if ($b['k'] !== null && $b['tetangga_positif'] !== null)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-[14px]">scatter_plot</span>
                                    {{ $b['tetangga_positif'] }} dari {{ $b['k'] }} tetangga terdekat berstatus diabetes
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Catatan pembacaan --}}
            <div class="mt-6 flex flex-col gap-3">
                <div class="bg-surface-container-low border border-outline-variant rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-on-surface-variant text-[20px] shrink-0">rule</span>
                    <div class="flex flex-col gap-1 text-label-sm font-label-sm text-on-surface-variant leading-relaxed">
                        <span>
                            <strong class="text-on-surface">Ambang tiap model memang berbeda, dan itu disengaja.</strong>
                            @if (count($ringkasAmbang) > 0)
                                {{ implode(' · ', $ringkasAmbang) }}.
                            @endif
                            Setiap ambang disetel terpisah dengan Youden&rsquo;s J pada data uji model tersebut,
                            sehingga label &ldquo;Risiko Tinggi/Rendah&rdquo; dibaca terhadap ambangnya sendiri &mdash; bukan terhadap 50%.
                            Karena itu probabilitas yang lebih kecil pada satu model bisa saja tetap berarti &ldquo;Risiko Tinggi&rdquo;.
                        </span>
                        @php
                            $barisKnn = collect($barisModel)->first(fn ($x) => $x['k'] !== null && $x['tetangga_positif'] !== null);
                        @endphp
                        @if ($barisKnn)
                            <span>
                                Probabilitas KNN hanya dapat bernilai kelipatan 1/{{ $barisKnn['k'] }}, karena dihitung dari
                                proporsi tetangga terdekat. Bentuk pecahan
                                &ldquo;{{ $barisKnn['tetangga_positif'] }} dari {{ $barisKnn['k'] }}&rdquo;
                                karena itu lebih jujur dibanding angka desimalnya.
                            </span>
                        @endif
                    </div>
                </div>

                <div class="bg-tertiary/5 border border-tertiary/25 rounded-lg p-4 flex gap-3">
                    <span class="material-symbols-outlined text-tertiary text-[20px] shrink-0">info</span>
                    <p class="text-label-sm font-label-sm text-on-surface-variant leading-relaxed">
                        <strong class="text-on-surface">Model dengan probabilitas tertinggi pada satu pasien bukan berarti model terbaik.</strong>
                        Penentuan model terbaik berasal dari pengujian pada
                        {{ $nDataUjiTeks ? $nDataUjiTeks . ' data uji' : 'keseluruhan data uji' }}, bukan dari satu kasus.
                        Perbandingan di atas hanya memperlihatkan bagaimana tiap model menilai pasien ini.
                    </p>
                </div>
            </div>
        @endif
    </section>

    {{-- ============================================================
         SECTION B — Mengapa model produksi yang dipakai
         Metrik riset model produksi; penanda "(tertinggi)" dihitung
         dari $exp['perbandingan_model'], bukan ditulis manual.
         ============================================================ --}}
    @if ($modelTerbaik)
        <section id="model-produksi" class="bg-surface border border-outline-variant rounded-xl p-stack-lg flex flex-col shadow-sm scroll-mt-24">
            <div class="flex items-center gap-2 border-b border-outline-variant pb-4 mb-4">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">workspace_premium</span>
                <h2 class="text-headline-sm font-headline-sm text-on-surface">
                    Mengapa {{ $namaProduksi }} yang Dipakai
                </h2>
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $warnaProduksi }};" aria-hidden="true"></span>
            </div>

            @if (count($metrikRiset) === 0)
                <div class="flex flex-col items-center justify-center gap-2 text-center border border-dashed border-outline-variant rounded-lg bg-surface-container-low px-6 py-10">
                    <span class="material-symbols-outlined text-on-surface-variant text-[32px]">query_stats</span>
                    <p class="text-body-md font-body-md text-on-surface">Metrik riset belum tersedia</p>
                    <p class="text-label-sm font-label-sm text-on-surface-variant max-w-md leading-relaxed">
                        Angka evaluasi model produksi belum terbaca dari hasil riset, sehingga sengaja dikosongkan
                        alih-alih diisi nilai sementara.
                    </p>
                </div>
            @else
                <p class="text-body-md font-body-md text-on-surface-variant mb-5">
                    Angka berikut adalah hasil pengujian {{ $namaProduksi }} pada
                    {{ $nDataUjiTeks ? $nDataUjiTeks . ' data uji' : 'data uji' }} &mdash; bukan hasil pada pasien ini.
                    Penanda &ldquo;(tertinggi)&rdquo; berarti nilai tersebut yang terbaik di antara ketiga model yang diuji.
                </p>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach ($daftarMetrik as $mk)
                        @php
                            $nilai = isset($metrikRiset[$mk['kunci']]) && is_numeric($metrikRiset[$mk['kunci']])
                                ? (float) $metrikRiset[$mk['kunci']]
                                : null;
                            $puncak = $nilaiTertinggi[$mk['kunci']] ?? null;
                            // Bandingkan baris model produksi pada tabel riset terhadap nilai maksimum;
                            // bila barisnya tidak ditemukan, jatuh ke perbandingan langsung nilai yang tampil.
                            $nilaiRiset = ($barisProduksiRiset !== null && isset($barisProduksiRiset[$mk['kunci']]) && is_numeric($barisProduksiRiset[$mk['kunci']]))
                                ? (float) $barisProduksiRiset[$mk['kunci']]
                                : $nilai;
                            $isTertinggi = $nilaiRiset !== null && $puncak !== null && $nilaiRiset >= $puncak - 1e-9;
                        @endphp
                        <div class="bg-surface-container-low p-4 rounded-lg flex flex-col gap-1">
                            <span class="text-label-sm font-label-sm text-on-surface-variant">{{ $mk['label'] }}</span>
                            <span class="flex flex-wrap items-baseline gap-x-1.5">
                                <span class="text-headline-sm font-headline-sm text-on-surface tabular-nums">
                                    {{ $nilai === null ? '—' : $persenId($nilai * 100) }}
                                </span>
                                @if ($isTertinggi)
                                    <span class="text-xs font-semibold text-secondary">(tertinggi)</span>
                                @endif
                            </span>
                            <span class="text-xs text-on-surface-variant leading-snug">{{ $mk['ket'] }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="text-label-sm font-label-sm text-on-surface-variant leading-relaxed mt-5">
                    Pemilihan model produksi tidak ditentukan oleh satu metrik saja, melainkan oleh
                    <strong class="text-on-surface">skor komposit berbobot</strong> yang menimbang recall, ROC-AUC, precision, F1,
                    serta biaya komputasi sekaligus, lengkap dengan analisis sensitivitas bobotnya.
                    <a href="{{ route('analysis.methodology') }}#keputusan" class="text-primary font-semibold hover:underline">
                        Lihat rincian keputusan di halaman Metodologi
                    </a>.
                </p>
            @endif
        </section>
    @endif
</main>
@endsection

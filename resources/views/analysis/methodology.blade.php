@extends('layouts.app')

@php
    /**
     * Halaman ini TIDAK boleh berisi angka hardcode.
     * Semua nilai dibaca dari public/data/experiments.json (lihat AnalysisController::experiments()).
     * Bagian yang datanya belum tersedia menampilkan empty state, bukan angka karangan.
     */
    $exp        = $exp ?? [];
    $meta       = $exp['meta'] ?? [];
    $dataset    = $meta['dataset'] ?? [];
    $models     = $exp['perbandingan_model'] ?? [];
    $split      = $exp['justifikasi_split'] ?? [];
    $kSel       = $exp['justifikasi_k'] ?? [];
    $svm        = $exp['justifikasi_svm'] ?? [];
    $stat       = $exp['validasi_statistik'] ?? [];
    $ablation   = $exp['ablation'] ?? [];
    $keputusan  = $exp['matriks_keputusan'] ?? [];

    $siap = fn ($bagian) => is_array($bagian) && ($bagian['status'] ?? 'menunggu') !== 'menunggu';

    /**
     * Notebook menandai apakah sebuah eksperimen dijalankan dengan MODE_CEPAT
     * (subsample + grid diperkecil). Angka dari mode itu berkualitas pratinjau dan
     * tidak boleh dilaporkan sebagai hasil akhir, jadi provenance-nya ditampilkan
     * apa adanya alih-alih disembunyikan.
     */
    $pratinjau = function ($bagian) {
        if (!is_array($bagian)) return false;
        return (bool) (($bagian['pengaturan_eksperimen']['mode_cepat'] ?? null)
            ?? ($bagian['metadata']['mode_cepat'] ?? false));
    };

    $adaPratinjau = collect([$split, $kSel, $svm, $stat, $ablation])->contains(fn ($b) => $pratinjau($b));

    // Peringatan metodologis: bila nilai k terpilih jatuh tepat di batas atas ruang
    // pencarian, optimumnya belum terkurung -- kurva masih bisa menanjak di luar rentang.
    $sweepK = $kSel['sweep'] ?? [];
    $kMaksDiuji = collect($sweepK)->max('k');
    $kDiBatas = $kMaksDiuji !== null && ($kSel['k_terpilih'] ?? null) === $kMaksDiuji;

    $poinRevisi = [
        [
            'kode'     => 'A',
            'judul'    => 'Rasio Split Data 80:20',
            'revisi'   => 'kenapa splitting data pakai 80:20',
            'notebook' => $split['notebook'] ?? '01_Justifikasi_Rasio_Split.ipynb',
            'siap'     => $siap($split),
            'anchor'   => 'split',
            'ikon'     => 'call_split',
        ],
        [
            'kode'     => 'B',
            'judul'    => 'Pemilihan Nilai k pada KNN',
            'revisi'   => 'tidak ada pemilihan kenapa k dari KNN',
            'notebook' => $kSel['notebook'] ?? '02_Justifikasi_Pemilihan_K_KNN.ipynb',
            'siap'     => $siap($kSel),
            'anchor'   => 'knn',
            'ikon'     => 'scatter_plot',
        ],
        [
            'kode'     => 'C',
            'judul'    => 'Pemilihan Hyperplane SVM',
            'revisi'   => 'kenapa hyperplane pada SVM yang dipilih',
            'notebook' => $svm['notebook'] ?? '03_Justifikasi_Hyperplane_SVM.ipynb',
            'siap'     => $siap($svm),
            'anchor'   => 'svm',
            'ikon'     => 'linear_scale',
        ],
        [
            'kode'     => 'D',
            'judul'    => 'Pengujian Tambahan',
            'revisi'   => 'diperbanyak pengujiannya',
            'notebook' => '04 & 05 (validasi statistik, ablation, robustness)',
            'siap'     => $siap($stat) || $siap($ablation),
            'anchor'   => 'pengujian',
            'ikon'     => 'science',
        ],
    ];

    $jumlahSiap = collect($poinRevisi)->where('siap', true)->count();
@endphp

@section('content')
<main class="flex-grow w-full max-w-container-max mx-auto px-gutter py-stack-lg flex flex-col gap-12 lg:gap-16">

    {{-- ===================== HERO ===================== --}}
    <section class="flex flex-col gap-4 pt-6 lg:pt-10 max-w-3xl">
        <div class="inline-flex items-center gap-2 bg-tertiary/10 text-tertiary px-3 py-1 rounded-full w-max text-label-sm font-label-sm border border-tertiary/20">
            <span class="material-symbols-outlined text-sm">fact_check</span>
            <span>Metodologi &amp; Justifikasi Pengujian</span>
        </div>
        <h1 class="text-headline-lg font-headline-lg text-on-surface">Dasar Pengambilan Keputusan Desain Model</h1>
        <p class="text-body-lg font-body-lg text-on-surface-variant">
            Halaman ini mendokumentasikan <strong>alasan di balik setiap keputusan desain</strong> pada model DiaPredict:
            mengapa data dibagi 80:20, mengapa nilai <em>k</em> tertentu dipakai pada KNN, mengapa hyperplane SVM yang itu yang dipilih,
            serta rangkaian pengujian tambahan yang memvalidasi keseluruhan pipeline. Setiap klaim di halaman ini
            berasal dari eksperimen yang dapat direproduksi pada notebook di folder
            <code class="bg-surface-container-high px-2 py-0.5 rounded text-primary text-sm font-semibold">Revisi_Pengujian_V3/</code>.
        </p>
    </section>

    {{-- ===================== PROVENANCE: HASIL PRATINJAU ===================== --}}
    @if ($adaPratinjau)
        <section class="border border-[#f39c12]/40 bg-[#f39c12]/5 rounded-xl p-5 flex flex-col md:flex-row md:items-start gap-4">
            <span class="material-symbols-outlined text-[#f39c12] text-[28px]">science</span>
            <div class="flex flex-col gap-1">
                <h2 class="text-headline-sm font-headline-sm text-on-surface">Angka di halaman ini berstatus pratinjau</h2>
                <p class="text-sm text-on-surface-variant">
                    Eksperimen dijalankan dengan <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">MODE_CEPAT = True</code>,
                    yaitu memakai subsample dan ruang pencarian yang diperkecil agar seluruh sel dapat diverifikasi berjalan.
                    Angkanya sah untuk menilai alur dan kecenderungan, <strong>tetapi belum layak dilaporkan sebagai hasil akhir skripsi</strong>.
                    Jalankan ulang notebook dengan <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">MODE_CEPAT = False</code>
                    untuk angka final. Model produksi tidak terpengaruh: BAGIAN 1 dan BAGIAN 7 selalu memakai data penuh.
                </p>
            </div>
        </section>
    @endif

    {{-- ===================== STATUS DATA ===================== --}}
    @if ($jumlahSiap < count($poinRevisi))
        <section class="border border-tertiary/30 bg-tertiary/5 rounded-xl p-5 flex flex-col md:flex-row md:items-center gap-4">
            <span class="material-symbols-outlined text-tertiary text-[28px]">pending_actions</span>
            <div class="flex flex-col gap-1">
                <h2 class="text-headline-sm font-headline-sm text-on-surface">
                    Hasil eksperimen: {{ $jumlahSiap }} dari {{ count($poinRevisi) }} bagian sudah tersedia
                </h2>
                <p class="text-sm text-on-surface-variant">
                    Bagian yang belum tersedia sengaja dibiarkan kosong, bukan diisi angka sementara.
                    Jalankan notebook <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">00</code>–<code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">06</code>
                    di Google Colab dengan <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">PAKAI_DRIVE = True</code>,
                    lalu salin <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">produksi/experiments.json</code> ke
                    <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-xs">public/data/experiments.json</code>.
                    Halaman ini akan langsung menampilkan seluruh grafik dan tabelnya.
                </p>
            </div>
        </section>
    @endif

    {{-- ===================== PETA REVISI ===================== --}}
    <section class="flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h2 class="text-headline-md font-headline-md text-on-surface">Peta Poin Revisi</h2>
            <p class="text-body-md font-body-md text-on-surface-variant">Empat catatan penguji dan notebook yang menjawabnya.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($poinRevisi as $poin)
                <a href="#{{ $poin['anchor'] }}"
                   class="glass border border-outline-variant rounded-xl p-5 flex flex-col gap-3 hover:border-primary/40 hover:shadow-md transition-all duration-300">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined">{{ $poin['ikon'] }}</span>
                        </div>
                        @if ($poin['siap'])
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-secondary bg-secondary/10 border border-secondary/20 px-2 py-1 rounded-full">
                                <span class="material-symbols-outlined text-[14px]">check_circle</span> Tersedia
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-on-surface-variant bg-surface-container-high border border-outline-variant px-2 py-1 rounded-full">
                                <span class="material-symbols-outlined text-[14px]">schedule</span> Menunggu
                            </span>
                        @endif
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-label-sm font-label-sm text-on-surface-variant">Poin {{ $poin['kode'] }}</span>
                        <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ $poin['judul'] }}</h3>
                        <p class="text-sm text-on-surface-variant italic">&ldquo;{{ $poin['revisi'] }}&rdquo;</p>
                    </div>
                    <span class="text-xs text-primary font-mono break-all">{{ $poin['notebook'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ===================== A. RASIO SPLIT ===================== --}}
    <section id="split" class="flex flex-col gap-6 scroll-mt-24">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">call_split</span>
                <h2 class="text-headline-md font-headline-md text-on-surface">A. Mengapa Rasio Split 80:20?</h2>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                Menentukan rasio split adalah trade-off: memperbesar data latih meningkatkan kualitas model,
                tetapi memperkecil data uji membuat estimasi performanya makin tidak presisi.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex flex-col gap-4 text-body-md font-body-md text-on-surface-variant leading-relaxed">
                <p>
                    Rasio dipilih melalui pengujian tujuh konfigurasi (50:50 hingga 90:10), masing-masing diulang dengan
                    lima <em>random seed</em> berbeda untuk ketiga algoritma, lalu dievaluasi dari tiga sudut:
                </p>
                <ol class="list-decimal pl-5 space-y-2">
                    <li><strong>Performa model</strong> &mdash; recall, precision, F1, dan ROC-AUC pada tiap rasio, disertai simpangan baku antar-seed.</li>
                    <li><strong>Presisi estimasi</strong> &mdash; margin of error 95% dari recall, yang bergantung pada jumlah sampel positif di data uji.</li>
                    <li><strong>Learning curve</strong> &mdash; titik ketika penambahan data latih tidak lagi menaikkan performa secara berarti.</li>
                </ol>
                @if (!empty($dataset))
                    <div class="bg-primary/5 border border-primary/20 rounded-xl p-5 flex flex-col gap-2">
                        <h4 class="text-headline-sm font-headline-sm text-primary flex items-center gap-2">
                            <span class="material-symbols-outlined">functions</span> Basis Perhitungan
                        </h4>
                        <p>
                            Dengan {{ number_format($dataset['jumlah_baris_setelah_dedup'] ?? 0, 0, ',', '.') }} baris data dan
                            hanya {{ number_format($dataset['persen_kelas_positif'] ?? 0, 2, ',', '.') }}% kelas positif,
                            porsi uji 20% menyisakan {{ number_format($dataset['jumlah_data_uji'] ?? 0, 0, ',', '.') }} sampel
                            dengan {{ number_format($dataset['jumlah_positif_data_uji'] ?? 0, 0, ',', '.') }} kasus diabetes.
                            Jumlah positif inilah &mdash; bukan total sampel &mdash; yang menentukan presisi estimasi recall,
                            karena recall dihitung hanya atas kelas positif.
                        </p>
                    </div>
                @endif
            </div>

            <div class="glass border border-outline-variant rounded-xl p-6 bg-surface-container flex flex-col gap-4">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Rasio Terpilih</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-[44px] leading-none font-bold text-primary">
                        {{ isset($split['rasio_terpilih']) ? (int) round((1 - $split['rasio_terpilih']) * 100) . ':' . (int) round($split['rasio_terpilih'] * 100) : '80:20' }}
                    </span>
                </div>
                <p class="text-sm text-on-surface-variant">
                    {{ $split['kesimpulan'] ?? $split['ringkasan_sementara'] ?? 'Justifikasi lengkap tersedia setelah notebook 01 dijalankan.' }}
                </p>
            </div>
        </div>

        {{-- Dua grafik terpisah (bukan sumbu ganda): performa dan presisi estimasi --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4">
                <div>
                    <h3 class="text-headline-sm font-headline-sm text-on-surface">Recall vs Proporsi Data Latih</h3>
                    <p class="text-label-sm font-label-sm text-on-surface-variant">Semakin besar data latih, semakin tinggi recall &mdash; sampai titik jenuh.</p>
                </div>
                <div class="relative h-[300px] w-full" data-chart-wrap="chartSplitRecall">
                    <canvas id="chartSplitRecall"></canvas>
                </div>
            </div>
            <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4">
                <div>
                    <h3 class="text-headline-sm font-headline-sm text-on-surface">Margin of Error Recall (95%)</h3>
                    <p class="text-label-sm font-label-sm text-on-surface-variant">Semakin kecil data uji, semakin lebar rentang ketidakpastian estimasi.</p>
                </div>
                <div class="relative h-[300px] w-full" data-chart-wrap="chartSplitMoe">
                    <canvas id="chartSplitMoe"></canvas>
                </div>
            </div>
        </div>

        <div data-table-wrap="tabelSplit" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
    </section>

    {{-- ===================== B. PEMILIHAN K ===================== --}}
    <section id="knn" class="flex flex-col gap-6 scroll-mt-24">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#e74c3c]">scatter_plot</span>
                <h2 class="text-headline-md font-headline-md text-on-surface">B. Mengapa Nilai k Itu yang Dipilih pada KNN?</h2>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                Nilai k mengatur keseimbangan bias&ndash;variance: k kecil membuat batas keputusan terlalu mengikuti derau,
                k besar membuatnya terlalu halus sehingga kasus minoritas terlewat.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex flex-col gap-4 text-body-md font-body-md text-on-surface-variant leading-relaxed">
                <p>
                    Penelitian awal menetapkan k lewat pencarian acak atas tujuh kandidat saja, tanpa menampilkan perilaku
                    model di seluruh rentang k. Revisi ini menguji <strong>26 nilai k ganjil (k = 1 hingga 51)</strong> dengan
                    validasi silang 5-fold, lalu memilih nilai akhir memakai tiga saringan:
                </p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Kurva bias&ndash;variance</strong> &mdash; membandingkan skor latih dan skor validasi pada tiap k; selisih yang melebar menandakan <em>overfitting</em>.</li>
                    <li><strong>Aturan one-standard-error</strong> &mdash; memilih model paling sederhana yang skornya masih berada dalam satu simpangan baku dari skor terbaik, sehingga pilihan tidak mengejar puncak yang bisa jadi kebetulan.</li>
                    <li><strong>Uji signifikansi antar-k</strong> &mdash; memastikan selisih performa antar kandidat memang nyata secara statistik.</li>
                </ul>
                @if (!empty($kSel['catatan_k20_vs_k21']))
                    <div class="bg-tertiary/5 border border-tertiary/20 rounded-xl p-5">
                        <span class="text-xs text-tertiary font-semibold uppercase tracking-wider block mb-1">Klarifikasi</span>
                        <p class="text-sm text-on-surface-variant">{{ $kSel['catatan_k20_vs_k21'] }}</p>
                    </div>
                @endif

                @if ($kDiBatas)
                    <div class="bg-error-container/20 border border-error/30 rounded-xl p-5">
                        <span class="text-xs text-error font-semibold uppercase tracking-wider block mb-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[16px]">warning</span> Keterbatasan yang perlu disebut
                        </span>
                        <p class="text-sm text-on-surface-variant">
                            Nilai k terpilih ({{ $kSel['k_terpilih'] }}) berada tepat di <strong>batas atas ruang pencarian</strong>
                            yang diuji (k maksimum = {{ $kMaksDiuji }}), dan recall validasi masih menanjak sampai titik itu.
                            Artinya nilai optimum belum terkurung: bisa jadi k yang lebih besar memberi hasil lebih baik lagi.
                            Rentang sweep perlu diperluas sebelum angka ini dipakai sebagai kesimpulan akhir.
                        </p>
                    </div>
                @endif
            </div>

            <div class="glass border border-outline-variant rounded-xl p-6 bg-surface-container flex flex-col gap-4">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Nilai k Terpilih</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-[44px] leading-none font-bold text-[#e74c3c]">
                        {{ $kSel['k_terpilih'] ?? $kSel['k_baseline_v2'] ?? '—' }}
                    </span>
                    @if (!isset($kSel['k_terpilih']))
                        <span class="text-label-sm font-label-sm text-on-surface-variant">(baseline, menunggu sweep)</span>
                    @endif
                </div>
                <p class="text-sm text-on-surface-variant">
                    {{ $kSel['alasan'] ?? 'Alasan formal akan terisi dari hasil aturan one-standard-error pada notebook 02.' }}
                </p>
            </div>
        </div>

        <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4">
            <div>
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Kurva Bias&ndash;Variance: Recall terhadap Nilai k</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant">Garis latih dan validasi yang menjauh menandakan overfitting pada k kecil.</p>
            </div>
            <div class="relative h-[340px] w-full" data-chart-wrap="chartElbowK">
                <canvas id="chartElbowK"></canvas>
            </div>
        </div>

        <div data-table-wrap="tabelK" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
    </section>

    {{-- ===================== C. HYPERPLANE SVM ===================== --}}
    <section id="svm" class="flex flex-col gap-6 scroll-mt-24">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-[#2ecc71]">linear_scale</span>
                <h2 class="text-headline-md font-headline-md text-on-surface">C. Mengapa Hyperplane Itu yang Dipilih pada SVM?</h2>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                SVM mencari bidang pemisah <code class="text-sm">w &middot; x + b = 0</code> dengan margin selebar mungkin.
                Memilih hyperplane berarti memilih kernel (ruang tempat bidang dicari) dan parameter C (seberapa toleran terhadap kesalahan).
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 flex flex-col gap-4 text-body-md font-body-md text-on-surface-variant leading-relaxed">
                <p>Pemilihan hyperplane diuji dalam empat lapis:</p>
                <ul class="list-disc pl-5 space-y-2">
                    <li><strong>Perbandingan kernel</strong> &mdash; linear, RBF, polinomial derajat 2 dan 3, serta sigmoid, dievaluasi dengan metrik dan waktu komputasi yang sama.</li>
                    <li><strong>Grid parameter</strong> &mdash; nilai C dari 0,001 sampai 100 untuk kernel linear, dan kombinasi C &times; gamma untuk RBF.</li>
                    <li><strong>Analisis margin</strong> &mdash; menghitung lebar margin <code class="text-sm">2/&Vert;w&Vert;</code> dan jumlah support vector pada tiap nilai C; hyperplane terpilih adalah yang bermargin paling lebar namun tetap menjaga sensitivitas.</li>
                    <li><strong>Interpretasi bobot</strong> &mdash; koefisien w tiap fitur menunjukkan arah dan besar pengaruhnya terhadap risiko, lalu dibandingkan dengan peringkat feature importance.</li>
                </ul>
                <p>
                    Bila kernel non-linear tidak memberi peningkatan yang signifikan secara statistik, hyperplane linear dipertahankan
                    atas dasar kesederhanaan, kecepatan, dan keterjelasan interpretasi &mdash; disertai angka pendukungnya, bukan sekadar asumsi.
                </p>
            </div>

            <div class="glass border border-outline-variant rounded-xl p-6 bg-surface-container flex flex-col gap-4">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Konfigurasi Terpilih</h3>
                <div class="flex flex-col gap-3">
                    <div class="flex justify-between items-baseline border-b border-outline-variant/40 pb-2">
                        <span class="text-label-md font-label-md text-on-surface-variant">Kernel</span>
                        <span class="text-headline-sm font-headline-sm text-[#2ecc71]">{{ $svm['kernel_terpilih'] ?? $svm['kernel_baseline_v2'] ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-baseline border-b border-outline-variant/40 pb-2">
                        <span class="text-label-md font-label-md text-on-surface-variant">Parameter C</span>
                        <span class="text-headline-sm font-headline-sm text-[#2ecc71] font-mono">{{ $svm['C_terpilih'] ?? $svm['C_baseline_v2'] ?? '—' }}</span>
                    </div>
                </div>
                <p class="text-sm text-on-surface-variant">
                    {{ $svm['kesimpulan'] ?? $svm['ringkasan_sementara'] ?? 'Justifikasi lengkap tersedia setelah notebook 03 dijalankan.' }}
                </p>
            </div>
        </div>

        <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4">
            <div>
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Perbandingan Kernel</h3>
                <p class="text-label-sm font-label-sm text-on-surface-variant">Recall dan ROC-AUC tiap kernel pada data uji yang sama.</p>
            </div>
            <div class="relative h-[320px] w-full" data-chart-wrap="chartKernel">
                <canvas id="chartKernel"></canvas>
            </div>
        </div>

        <div data-table-wrap="tabelMargin" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
    </section>

    {{-- ===================== D. PENGUJIAN TAMBAHAN ===================== --}}
    <section id="pengujian" class="flex flex-col gap-6 scroll-mt-24">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary">science</span>
                <h2 class="text-headline-md font-headline-md text-on-surface">D. Pengujian Tambahan</h2>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                Satu kali pembagian data hanya memberi satu titik estimasi tanpa ukuran ketidakpastian.
                Rangkaian pengujian berikut menambahkan dimensi yang sebelumnya belum ada.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @php
                $ujiTambahan = [
                    ['judul' => 'Repeated Stratified CV', 'ikon' => 'repeat', 'isi' => '5 fold &times; 5 pengulangan menghasilkan 25 estimasi per model, dilaporkan sebagai rata-rata dengan interval kepercayaan 95%.'],
                    ['judul' => 'Nested Cross-Validation', 'ikon' => 'account_tree', 'isi' => 'Loop luar 5-fold dan loop dalam 3-fold memisahkan proses tuning dari proses evaluasi, sehingga estimasi generalisasi tidak bias optimistik.'],
                    ['judul' => 'Empat Uji Statistik', 'ikon' => 'query_stats', 'isi' => 'McNemar, 5&times;2cv paired t-test, Wilcoxon signed-rank, dan DeLong test untuk perbedaan ROC-AUC.'],
                    ['judul' => 'Strategi Threshold', 'ikon' => 'tune', 'isi' => 'Delapan strategi penentuan ambang keputusan dibandingkan, termasuk skema sensitif biaya dengan rasio FN:FP 5:1 dan 10:1.'],
                    ['judul' => 'Kalibrasi Probabilitas', 'ikon' => 'compress', 'isi' => 'Reliability diagram, Brier score, expected calibration error, dan decision curve analysis untuk menilai manfaat klinis.'],
                    ['judul' => 'Ablation Penanganan Imbalance', 'ikon' => 'balance', 'isi' => 'Sepuluh strategi dibandingkan: tanpa penanganan, class weight, SMOTE dan variannya, ADASYN, serta under/over-sampling.'],
                    ['judul' => 'Ablation Fitur', 'ikon' => 'checklist', 'isi' => 'Menguji apakah tiga fitur yang dibuang benar-benar tidak berkontribusi, termasuk leave-one-feature-out pada lima fitur terpilih.'],
                    ['judul' => 'Uji Robustness', 'ikon' => 'shield', 'isi' => 'Injeksi derau, simulasi data hilang, dan pergeseran distribusi untuk mengukur ketahanan model di kondisi tidak ideal.'],
                    ['judul' => 'Evaluasi Subgrup', 'ikon' => 'groups', 'isi' => 'Performa dipecah per kelompok usia, kategori BMI, status hipertensi, dan kuartil glukosa untuk mendeteksi kesenjangan klinis.'],
                ];
            @endphp
            @foreach ($ujiTambahan as $uji)
                <div class="glass border border-outline-variant rounded-xl p-5 flex flex-col gap-3">
                    <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center text-secondary">
                        <span class="material-symbols-outlined">{{ $uji['ikon'] }}</span>
                    </div>
                    <h3 class="text-headline-sm font-headline-sm text-on-surface">{{ $uji['judul'] }}</h3>
                    <p class="text-sm text-on-surface-variant leading-relaxed">{!! $uji['isi'] !!}</p>
                </div>
            @endforeach
        </div>

        <div data-table-wrap="tabelUji" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
        <div data-table-wrap="tabelThreshold" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
    </section>

    {{-- ===================== E. MATRIKS KEPUTUSAN ===================== --}}
    <section id="keputusan" class="flex flex-col gap-6 scroll-mt-24">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">balance</span>
                <h2 class="text-headline-md font-headline-md text-on-surface">E. Keputusan Model Produksi</h2>
            </div>
            <p class="text-body-md font-body-md text-on-surface-variant">
                Memilih model hanya dari recall tertinggi mengabaikan precision, kemampuan diskriminasi, dan biaya komputasi.
                Keputusan produksi karena itu memakai skor komposit berbobot yang eksplisit, lengkap dengan analisis sensitivitas bobot.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="glass border border-outline-variant rounded-xl p-6 flex flex-col gap-4">
                <div>
                    <h3 class="text-headline-sm font-headline-sm text-on-surface">Sensitivitas terhadap Bobot Recall</h3>
                    <p class="text-label-sm font-label-sm text-on-surface-variant">Pada bobot recall berapa peringkat model berubah?</p>
                </div>
                <div class="relative h-[300px] w-full" data-chart-wrap="chartSensitivitas">
                    <canvas id="chartSensitivitas"></canvas>
                </div>
            </div>

            <div class="glass border border-outline-variant rounded-xl p-6 bg-surface-container flex flex-col gap-4">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">Bobot Kriteria</h3>
                @if (!empty($keputusan['bobot']))
                    <div class="flex flex-col gap-3">
                        @php $maksBobot = max($keputusan['bobot']) ?: 1; @endphp
                        @foreach ($keputusan['bobot'] as $kriteria => $bobot)
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between text-label-md font-label-md">
                                    <span class="text-on-surface">{{ ucwords(str_replace('_', ' ', $kriteria)) }}</span>
                                    <span class="text-primary font-mono">{{ number_format($bobot * 100, 0) }}%</span>
                                </div>
                                <div class="w-full bg-outline-variant/30 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full" style="width: {{ round($bobot / $maksBobot * 100, 1) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div class="bg-primary/5 border border-primary/20 rounded-lg p-4 mt-1">
                    <span class="text-xs text-primary font-semibold uppercase tracking-wider block mb-1">Model Produksi</span>
                    <span class="text-headline-sm font-headline-sm text-on-surface">{{ $keputusan['model_produksi'] ?? 'Random Forest' }}</span>
                    <p class="text-sm text-on-surface-variant mt-1">
                        Bobot recall dibuat dominan karena konteks medis menempatkan false negative sebagai kesalahan paling berbahaya,
                        namun tidak dibuat mutlak agar precision dan kelayakan operasional tetap diperhitungkan.
                    </p>
                </div>
            </div>
        </div>

        <div data-table-wrap="tabelKeputusan" class="overflow-x-auto border border-outline-variant rounded-xl bg-surface-container-lowest"></div>
    </section>

    {{-- ===================== REPRODUKSI ===================== --}}
    <section class="bg-surface-container border border-outline-variant rounded-2xl p-8 flex flex-col gap-5 my-4">
        <div class="flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">terminal</span>
            <h2 class="text-headline-md font-headline-md text-on-surface">Reproduksi Hasil</h2>
        </div>
        <p class="text-body-md font-body-md text-on-surface-variant max-w-3xl">
            Seluruh angka pada halaman ini dan halaman perbandingan berasal dari satu berkas data yang dihasilkan notebook,
            sehingga tidak ada nilai yang diketik manual ke dalam tampilan.
        </p>
        <ol class="list-decimal pl-5 space-y-2 text-body-md font-body-md text-on-surface-variant">
            <li>Buka folder <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">Revisi_Pengujian_V3/</code> dan jalankan notebook <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">00</code> sampai <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">05</code> di Google Colab dengan <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">PAKAI_DRIVE = True</code>.</li>
            <li>Jalankan notebook <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">06</code> untuk melatih model final dan menggabungkan seluruh hasil menjadi <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">experiments.json</code>.</li>
            <li>Salin <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">experiments.json</code> ke <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">public/data/</code>, serta <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">rf_model.pkl</code>, <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">scaler.pkl</code>, dan <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">model_metadata.json</code> ke <code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">model/</code>.</li>
            <li>Kosongkan cache aplikasi (<code class="bg-surface-container-high px-1.5 py-0.5 rounded text-sm">php artisan cache:clear</code>) agar halaman membaca data terbaru.</li>
        </ol>
        <div class="flex flex-col sm:flex-row gap-3 pt-2">
            <a href="{{ route('analysis.comparison') }}"
               class="inline-flex items-center justify-center gap-2 bg-primary text-on-primary rounded-lg px-6 py-3 text-label-md font-label-md hover:bg-primary-container hover:text-on-primary-container transition-colors">
                <span class="material-symbols-outlined text-[18px]">analytics</span>
                Lihat Hasil Perbandingan Model
            </a>
            <a href="{{ route('analysis.form') }}"
               class="inline-flex items-center justify-center gap-2 border border-outline-variant text-on-surface rounded-lg px-6 py-3 text-label-md font-label-md hover:border-primary hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[18px]">stethoscope</span>
                Coba Prediksi Risiko
            </a>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const DATA = @json($exp);

    const TINTA        = '#49454F';
    const TINTA_REDUP  = '#79747E';
    const GARIS_KISI   = 'rgba(121, 116, 126, 0.18)';
    const WARNA_MODEL  = { 'Random Forest': '#3498db', 'KNN': '#e74c3c', 'SVM (Linear)': '#2ecc71', 'SVM': '#2ecc71' };
    const AKSEN        = '#f39c12';

    // Label dan angka memakai warna teks, bukan warna seri; identitas seri dibawa oleh legend + mark.
    if (window.Chart) {
        Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
        Chart.defaults.font.size = 12;
        Chart.defaults.color = TINTA_REDUP;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;
        Chart.defaults.plugins.legend.labels.boxWidth = 8;
        Chart.defaults.plugins.legend.labels.color = TINTA;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(28, 27, 31, 0.94)';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.tooltip.displayColors = true;
        Chart.defaults.plugins.tooltip.boxWidth = 8;
        Chart.defaults.plugins.tooltip.usePointStyle = true;
    }

    const sumbuY = (judul, opsi = {}) => Object.assign({
        title: { display: !!judul, text: judul, color: TINTA_REDUP },
        grid: { color: GARIS_KISI, drawBorder: false },
        ticks: { color: TINTA_REDUP },
    }, opsi);

    const sumbuX = (judul, opsi = {}) => Object.assign({
        title: { display: !!judul, text: judul, color: TINTA_REDUP },
        grid: { display: false },
        ticks: { color: TINTA_REDUP },
    }, opsi);

    /** Empty state: tampil ketika data eksperimen belum tersedia. */
    function kosong(idCanvas, pesan, notebook) {
        const wrap = document.querySelector(`[data-chart-wrap="${idCanvas}"]`);
        if (!wrap) return;
        wrap.innerHTML = `
            <div class="h-full w-full flex flex-col items-center justify-center gap-3 text-center px-6 border border-dashed border-outline-variant rounded-lg bg-surface-container-low">
                <span class="material-symbols-outlined text-on-surface-variant text-[32px]">insights</span>
                <p class="text-sm text-on-surface-variant max-w-sm">${pesan}</p>
                ${notebook ? `<code class="text-xs text-primary font-mono">${notebook}</code>` : ''}
            </div>`;
    }

    /** Tabel pendamping setiap grafik: memenuhi kebutuhan aksesibilitas dan pembacaan angka persis. */
    function bangunTabel(kunci, judul, kolom, baris, catatan) {
        const wrap = document.querySelector(`[data-table-wrap="${kunci}"]`);
        if (!wrap) return;
        if (!baris || !baris.length) { wrap.remove(); return; }

        const thead = kolom.map(k => `<th class="p-3 font-semibold ${k.rata || 'text-left'}">${k.label}</th>`).join('');
        const tbody = baris.map(r => `<tr class="hover:bg-primary/5 transition-colors">${
            kolom.map(k => `<td class="p-3 ${k.rata || 'text-left'} ${k.mono ? 'font-mono' : ''}">${k.render(r)}</td>`).join('')
        }</tr>`).join('');

        wrap.innerHTML = `
            <div class="p-4 border-b border-outline-variant bg-surface-container">
                <h3 class="text-headline-sm font-headline-sm text-on-surface">${judul}</h3>
                ${catatan ? `<p class="text-label-sm font-label-sm text-on-surface-variant mt-1">${catatan}</p>` : ''}
            </div>
            <table class="w-full text-left border-collapse text-sm">
                <thead><tr class="bg-surface-container-high border-b border-outline-variant text-label-md font-label-md text-on-surface">${thead}</tr></thead>
                <tbody class="divide-y divide-outline-variant/40">${tbody}</tbody>
            </table>`;
    }

    const persen = (v, d = 2) => (v === null || v === undefined || isNaN(v)) ? '—' : (v * 100).toFixed(d) + '%';
    const angka  = (v, d = 4) => (v === null || v === undefined || isNaN(v)) ? '—' : Number(v).toFixed(d);
    const bulat  = v => (v === null || v === undefined || isNaN(v)) ? '—' : Number(v).toLocaleString('id-ID');

    /**
     * Ambil nilai pertama yang tersedia dari beberapa kemungkinan nama field.
     * Notebook menamai kolomnya mengikuti konvensi pandas (mis. `val_recall_mean`),
     * sedangkan tampilan memakai nama yang lebih ringkas. Pencarian bertoleransi ini
     * membuat halaman tetap terisi meskipun penamaan kolom notebook berubah sedikit.
     */
    const ambil = (obj, ...kunci) => {
        for (const k of kunci) {
            if (obj && obj[k] !== undefined && obj[k] !== null) return obj[k];
        }
        return null;
    };

    /** Label rasio: pakai label dari notebook bila ada, jika tidak susun dari rasio uji. */
    const labelRasio = r => ambil(r, 'rasio_label') ??
        `${Math.round((1 - (r.rasio_uji ?? 0)) * 100)}:${Math.round((r.rasio_uji ?? 0) * 100)}`;

    // ---------------------------------------------------------------- A. SPLIT
    (function grafikSplit() {
        const src = (DATA.justifikasi_split || {});
        const tabel = src.tabel || [];
        const moe = src.margin_of_error || [];

        if (!tabel.length) {
            kosong('chartSplitRecall', 'Grafik recall terhadap proporsi data latih akan muncul setelah eksperimen rasio split dijalankan.', src.notebook);
        } else {
            const rasio = [...new Set(tabel.map(r => r.proporsi_latih ?? (1 - r.rasio_uji)))].sort((a, b) => a - b);
            const modelUnik = [...new Set(tabel.map(r => r.model))];
            new Chart(document.getElementById('chartSplitRecall'), {
                type: 'line',
                data: {
                    labels: rasio.map(r => Math.round(r * 100) + '%'),
                    datasets: modelUnik.map(m => ({
                        label: m,
                        data: rasio.map(r => {
                            const baris = tabel.find(x => x.model === m && Math.abs((x.proporsi_latih ?? (1 - x.rasio_uji)) - r) < 1e-6);
                            return baris ? ambil(baris, 'recall_tuned_mean', 'recall_mean', 'recall') : null;
                        }),
                        borderColor: WARNA_MODEL[m] || AKSEN,
                        backgroundColor: WARNA_MODEL[m] || AKSEN,
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        tension: 0.25,
                    })),
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: sumbuX('Proporsi data latih'),
                        y: sumbuY('Recall', { ticks: { color: TINTA_REDUP, callback: v => (v * 100).toFixed(0) + '%' } }),
                    },
                    plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: ${persen(c.parsed.y)}` } } },
                },
            });
        }

        if (!moe.length) {
            kosong('chartSplitMoe', 'Grafik margin of error akan muncul setelah analisis presisi estimasi dijalankan.', src.notebook);
        } else {
            // `moe_persen` dari notebook sudah dalam satuan poin persen.
            new Chart(document.getElementById('chartSplitMoe'), {
                type: 'bar',
                data: {
                    labels: moe.map(labelRasio),
                    datasets: [{
                        label: 'Margin of error recall (95%)',
                        data: moe.map(r => ambil(r, 'moe_persen') ?? (ambil(r, 'moe') ?? 0) * 100),
                        backgroundColor: moe.map(r => Math.abs((r.rasio_uji ?? -1) - (src.rasio_terpilih ?? 0.2)) < 1e-6 ? AKSEN : 'rgba(52, 152, 219, 0.85)'),
                        borderRadius: 4,
                        borderSkipped: false,
                        borderColor: '#ffffff',
                        borderWidth: 2,
                    }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: sumbuX('Rasio latih:uji'),
                        y: sumbuY('± poin persen', { ticks: { color: TINTA_REDUP, callback: v => v.toFixed(1) } }),
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: c => `± ${c.parsed.y.toFixed(2)} poin persen (batang oranye = rasio terpilih)` } },
                    },
                },
            });
        }

        bangunTabel('tabelSplit', 'Rincian Eksperimen Rasio Split',
            [
                { label: 'Rasio', mono: true, render: labelRasio },
                { label: 'n latih', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'n_train', 'n_latih')) },
                { label: 'n uji', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'n_test', 'n_uji')) },
                { label: 'n positif uji', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'n_positif_test', 'n_positif_uji')) },
                { label: 'Recall', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'recall')) },
                { label: 'CI 95%', rata: 'text-right', mono: true, render: r => (r.ci_bawah === undefined || r.ci_bawah === null) ? '—' : `${persen(r.ci_bawah, 1)} – ${persen(r.ci_atas, 1)}` },
                { label: '± MoE 95%', rata: 'text-right', mono: true, render: r => { const v = ambil(r, 'moe_persen'); return v === null ? persen(ambil(r, 'moe'), 2) : Number(v).toFixed(2) + ' poin'; } },
            ], moe,
            'Margin of error dihitung dari jumlah sampel positif pada data uji, karena recall hanya dihitung atas kelas positif.');
    })();

    // ---------------------------------------------------------------- B. NILAI K
    (function grafikK() {
        const src = (DATA.justifikasi_k || {});
        const sweep = src.sweep || [];

        if (!sweep.length) {
            kosong('chartElbowK', 'Kurva bias–variance untuk k = 1 sampai 51 akan muncul setelah sweep nilai k dijalankan.', src.notebook);
        } else {
            const kTerpilih = src.k_terpilih ?? null;
            new Chart(document.getElementById('chartElbowK'), {
                type: 'line',
                data: {
                    labels: sweep.map(r => r.k),
                    datasets: [
                        {
                            label: 'Recall latih',
                            data: sweep.map(r => ambil(r, 'train_recall_mean', 'recall_train', 'train_recall')),
                            borderColor: TINTA_REDUP,
                            backgroundColor: 'rgba(121, 116, 126, 0.10)',
                            borderWidth: 2, borderDash: [5, 4],
                            pointRadius: 0, pointHoverRadius: 5, tension: 0.25, fill: false,
                        },
                        {
                            label: 'Recall validasi',
                            data: sweep.map(r => ambil(r, 'val_recall_mean', 'recall_val', 'val_recall', 'recall')),
                            borderColor: '#e74c3c',
                            backgroundColor: 'rgba(231, 76, 60, 0.12)',
                            borderWidth: 2,
                            pointRadius: sweep.map(r => (kTerpilih !== null && r.k === kTerpilih) ? 7 : 3),
                            pointBackgroundColor: sweep.map(r => (kTerpilih !== null && r.k === kTerpilih) ? AKSEN : '#e74c3c'),
                            pointBorderColor: '#ffffff', pointBorderWidth: 2,
                            pointHoverRadius: 8, tension: 0.25, fill: true,
                        },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: sumbuX('Nilai k (jumlah tetangga)'),
                        y: sumbuY('Recall', { ticks: { color: TINTA_REDUP, callback: v => (v * 100).toFixed(0) + '%' } }),
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: c => `k = ${c[0].label}`,
                                label: c => `${c.dataset.label}: ${persen(c.parsed.y)}`,
                                afterBody: c => (kTerpilih !== null && Number(c[0].label) === kTerpilih) ? 'Nilai k terpilih' : '',
                            },
                        },
                    },
                },
            });
        }

        const oneSe = src.one_se_rule || {};
        const catatanOneSe = oneSe.ambang_1se !== undefined
            ? `Skor CV terbaik ${persen(oneSe.skor_terbaik)} pada k = ${oneSe.k_terbaik_cv} dengan standard error ${angka(oneSe.standard_error, 4)}; seluruh k dengan recall di atas ambang ${persen(oneSe.ambang_1se)} dianggap setara secara statistik.`
            : 'Aturan one-standard-error memilih model paling stabil yang performanya belum berbeda berarti dari skor terbaik.';

        bangunTabel('tabelK', 'Kandidat k dalam Rentang One-Standard-Error',
            [
                { label: 'k', mono: true, render: r => r.k },
                { label: 'Recall validasi', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'val_recall_mean', 'recall_val', 'recall')) },
                { label: 'Std antar fold', rata: 'text-right', mono: true, render: r => angka(ambil(r, 'val_recall_std', 'std'), 4) },
                { label: 'Recall latih', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'train_recall_mean', 'recall_train')) },
                { label: 'Gap latih–validasi', rata: 'text-right', mono: true, render: r => angka(ambil(r, 'gap_recall', 'gap'), 4) },
                { label: 'ROC-AUC', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'val_roc_auc_mean', 'roc_auc')) },
            ],
            oneSe.tabel_kandidat || oneSe.kandidat || [],
            catatanOneSe);
    })();

    // ---------------------------------------------------------------- C. SVM
    (function grafikSvm() {
        const src = (DATA.justifikasi_svm || {});
        // Kandidat yang gagal dilatih (mis. kernel terlalu berat) tidak ditampilkan sebagai batang nol.
        const kernel = (src.perbandingan_kernel || []).filter(r => (r.status ?? 'ok') !== 'gagal' && r.recall !== null && r.recall !== undefined);

        if (!kernel.length) {
            kosong('chartKernel', 'Perbandingan kernel linear, RBF, polinomial, dan sigmoid akan muncul setelah eksperimen kernel dijalankan.', src.notebook);
        } else {
            const terpilih = (src.kernel_terpilih || '').toLowerCase();
            new Chart(document.getElementById('chartKernel'), {
                type: 'bar',
                data: {
                    // `kandidat` membedakan konfigurasi dengan kernel sama namun parameter berbeda.
                    labels: kernel.map(r => ambil(r, 'kandidat', 'kernel')),
                    datasets: [
                        {
                            label: 'Recall',
                            data: kernel.map(r => r.recall),
                            backgroundColor: kernel.map(r => (terpilih && (r.kernel || '').toLowerCase() === terpilih) ? AKSEN : 'rgba(46, 204, 113, 0.85)'),
                            borderRadius: 4, borderSkipped: false, borderColor: '#ffffff', borderWidth: 2,
                        },
                        {
                            label: 'ROC-AUC',
                            data: kernel.map(r => r.roc_auc),
                            backgroundColor: 'rgba(52, 152, 219, 0.85)',
                            borderRadius: 4, borderSkipped: false, borderColor: '#ffffff', borderWidth: 2,
                        },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: sumbuX('Kernel'),
                        y: sumbuY('Skor', { min: 0, max: 1, ticks: { color: TINTA_REDUP, callback: v => (v * 100).toFixed(0) + '%' } }),
                    },
                    plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: ${persen(c.parsed.y)}` } } },
                },
            });
        }

        const cTerpilih = src.C_terpilih ?? src.C_baseline_v2 ?? null;
        bangunTabel('tabelMargin', 'Analisis Margin dan Support Vector',
            [
                {
                    label: 'C', mono: true,
                    render: r => (cTerpilih !== null && Number(r.C) === Number(cTerpilih))
                        ? `${r.C} <span class="text-xs font-normal text-secondary">(terpilih)</span>`
                        : r.C,
                },
                { label: '‖w‖', rata: 'text-right', mono: true, render: r => angka(ambil(r, 'norma_w'), 4) },
                { label: 'Lebar margin 2/‖w‖', rata: 'text-right', mono: true, render: r => angka(ambil(r, 'margin_2_per_w', 'margin'), 4) },
                { label: 'Support vector', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'n_SV', 'n_sv')) },
                { label: 'Rasio SV', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'rasio_SV', 'rasio_sv'), 1) },
                { label: 'Recall', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'recall')) },
                { label: 'Precision', rata: 'text-right', mono: true, render: r => persen(ambil(r, 'precision')) },
            ],
            (src.analisis_margin || []).filter(r => (r.status ?? 'ok') !== 'gagal'),
            'Hyperplane terpilih adalah yang memberi margin paling lebar sambil mempertahankan sensitivitas pada kelas diabetes. Nilai C yang lebih kecil menghasilkan margin lebih lebar dengan toleransi kesalahan lebih besar.');
    })();

    // ---------------------------------------------------------------- D. UJI STATISTIK
    (function tabelUjiStatistik() {
        const src = (DATA.validasi_statistik || {});
        bangunTabel('tabelUji', 'Hasil Uji Signifikansi Antar Model',
            [
                { label: 'Pasangan', render: r => r.pasangan },
                { label: 'Uji', render: r => r.uji },
                { label: 'Statistik', rata: 'text-right', mono: true, render: r => angka(r.statistik, 4) },
                { label: 'p-value', rata: 'text-right', mono: true, render: r => (r.p_value === null || r.p_value === undefined) ? '—' : Number(r.p_value).toExponential(2) },
                { label: 'Kesimpulan', render: r => r.kesimpulan ?? (r.signifikan ? 'Signifikan' : 'Tidak signifikan') },
            ],
            src.uji_statistik || DATA.uji_mcnemar?.map(r => ({
                pasangan: r.pasangan, uji: "McNemar's test", statistik: r.chi2,
                p_value: r.p_value, kesimpulan: r.signifikan ? 'Signifikan (α = 0,05)' : 'Tidak signifikan',
            })) || [],
            'Uji dilakukan pada prediksi data uji yang sama untuk setiap pasangan model.');

        // Strategi threshold: hanya model produksi yang ditampilkan agar tabel tetap terbaca.
        const modelProduksi = DATA.matriks_keputusan?.model_produksi || DATA.meta?.model_produksi || 'Random Forest';
        const strategi = (src.strategi_threshold || []).filter(r => !r.model || r.model === modelProduksi);

        bangunTabel('tabelThreshold', `Perbandingan Strategi Penentuan Threshold (${modelProduksi})`,
            [
                { label: 'Strategi', render: r => r.strategi },
                { label: 'Threshold', rata: 'text-right', mono: true, render: r => angka(r.threshold, 4) },
                { label: 'Recall', rata: 'text-right', mono: true, render: r => persen(r.recall) },
                { label: 'Precision', rata: 'text-right', mono: true, render: r => persen(r.precision) },
                { label: 'F1', rata: 'text-right', mono: true, render: r => persen(r.f1) },
                { label: 'FN (terlewat)', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'jumlah_FN')) },
                { label: 'FP (rujukan sia-sia)', rata: 'text-right', mono: true, render: r => bulat(ambil(r, 'jumlah_FP')) },
            ],
            strategi,
            'Threshold produksi ditentukan dengan Youden\'s J karena memaksimalkan sensitivitas dan spesifisitas sekaligus. Kolom FN dan FP memperlihatkan konsekuensi klinis nyata dari tiap pilihan ambang.');
    })();

    // ---------------------------------------------------------------- E. KEPUTUSAN
    (function grafikKeputusan() {
        const src = (DATA.matriks_keputusan || {});
        const sens = src.sensitivitas_bobot || [];

        if (!sens.length) {
            kosong('chartSensitivitas', 'Analisis sensitivitas bobot akan muncul setelah matriks keputusan multi-kriteria dihitung.', src.notebook);
        } else if (sens[0].pemenang !== undefined) {
            // Bentuk data: satu baris per bobot, berisi pemenang dan jarak ke peringkat dua.
            // Batang diwarnai menurut model pemenang, sehingga titik pergantian peringkat
            // terbaca sebagai pergantian warna, dan tingginya menunjukkan seberapa aman keunggulannya.
            const pemenangUnik = [...new Set(sens.map(r => r.pemenang))];
            new Chart(document.getElementById('chartSensitivitas'), {
                type: 'bar',
                data: {
                    labels: sens.map(r => Math.round(r.bobot_recall * 100) + '%'),
                    datasets: [{
                        label: 'Selisih skor peringkat 1 dan 2',
                        data: sens.map(r => r.selisih_juara_1_2),
                        backgroundColor: sens.map(r => WARNA_MODEL[r.pemenang] || AKSEN),
                        borderColor: '#ffffff', borderWidth: 2, borderRadius: 4, borderSkipped: false,
                    }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: {
                        x: sumbuX('Bobot yang diberikan pada recall'),
                        y: sumbuY('Selisih skor peringkat 1 vs 2'),
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: c => `Bobot recall ${c[0].label}`,
                                label: c => `Pemenang: ${sens[c.dataIndex].pemenang}`,
                                afterLabel: c => `Unggul ${angka(c.parsed.y, 4)} atas peringkat dua`,
                            },
                        },
                    },
                },
            });
            // Legend manual: warna batang mewakili model pemenang, bukan besaran.
            const wrap = document.querySelector('[data-chart-wrap="chartSensitivitas"]');
            if (wrap && wrap.parentElement) {
                const ket = document.createElement('div');
                ket.className = 'flex flex-wrap items-center gap-4 pt-1 text-label-sm font-label-sm text-on-surface-variant';
                ket.innerHTML = 'Warna batang = model pemenang: ' + pemenangUnik.map(m =>
                    `<span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:${WARNA_MODEL[m] || AKSEN}"></span>${m}</span>`
                ).join('');
                wrap.parentElement.appendChild(ket);
            }
        } else {
            const modelUnik = [...new Set(sens.map(r => r.model))];
            const bobotUnik = [...new Set(sens.map(r => r.bobot_recall))].sort((a, b) => a - b);
            new Chart(document.getElementById('chartSensitivitas'), {
                type: 'line',
                data: {
                    labels: bobotUnik.map(b => Math.round(b * 100) + '%'),
                    datasets: modelUnik.map(m => ({
                        label: m,
                        data: bobotUnik.map(b => {
                            const baris = sens.find(x => x.model === m && Math.abs(x.bobot_recall - b) < 1e-9);
                            return baris ? ambil(baris, 'skor', 'skor_komposit') : null;
                        }),
                        borderColor: WARNA_MODEL[m] || AKSEN,
                        backgroundColor: WARNA_MODEL[m] || AKSEN,
                        borderWidth: 2, pointRadius: 4, pointHoverRadius: 6,
                        pointBorderColor: '#ffffff', pointBorderWidth: 2, tension: 0.25,
                    })),
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        x: sumbuX('Bobot yang diberikan pada recall'),
                        y: sumbuY('Skor komposit'),
                    },
                    plugins: { tooltip: { callbacks: { label: c => `${c.dataset.label}: ${angka(c.parsed.y, 3)}` } } },
                },
            });
        }

        // Kolom tabel keputusan disusun mengikuti kolom yang benar-benar ada pada data,
        // karena notebook menyimpan kontribusi per kriteria (kontrib_*) yang jumlahnya
        // bergantung pada berapa kriteria yang dipakai.
        const skor = src.skor || [];
        if (skor.length) {
            const contoh = skor[0];
            const kolomKontrib = Object.keys(contoh).filter(k => k.startsWith('kontrib_'));
            const kolom = [
                { label: 'Model', render: r => ambil(r, 'model', 'index', 'Model') ?? '—' },
                { label: 'Skor komposit', rata: 'text-right', mono: true, render: r => angka(ambil(r, 'skor_komposit', 'skor'), 4) },
                { label: 'Peringkat', rata: 'text-center', mono: true, render: r => ambil(r, 'peringkat') ?? '—' },
            ];
            for (const k of kolomKontrib) {
                kolom.push({
                    label: 'Kontribusi ' + k.replace('kontrib_', '').replace(/_/g, ' '),
                    rata: 'text-right', mono: true,
                    render: r => angka(r[k], 4),
                });
            }
            bangunTabel('tabelKeputusan', 'Matriks Keputusan Multi-Kriteria', kolom, skor,
                'Skor komposit adalah jumlah nilai tiap kriteria yang telah dinormalisasi lalu dikalikan bobotnya. Kolom kontribusi memperlihatkan sumbangan tiap kriteria terhadap skor akhir.');
        } else {
            bangunTabel('tabelKeputusan', 'Matriks Keputusan Multi-Kriteria', [], [], '');
        }
    })();
})();
</script>
@endsection

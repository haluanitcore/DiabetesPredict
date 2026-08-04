<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya pintu aplikasi menuju lapisan machine learning (RF / KNN / SVM).
 *
 * Alasan dikumpulkan di satu kelas: sebelumnya pemanggilan Python berada di dalam
 * controller, sehingga perintah CLI dan halaman detail harus menyalin logika yang
 * sama. Semua pemanggil sekarang memakai kelas ini, jadi perubahan protokol
 * (argumen CLI, endpoint microservice, satuan probabilitas) cukup di satu berkas.
 */
class PrediksiMultiModel
{
    /**
     * Warna seri per model. Disamakan dengan warna pada halaman perbandingan riset
     * agar satu model selalu diwakili warna yang sama di seluruh aplikasi.
     */
    public const WARNA = [
        'rf'  => '#3498db',
        'knn' => '#e74c3c',
        'svm' => '#2ecc71',
    ];

    /**
     * Kolom penyimpan probabilitas tiap model pada tabel analysis_results.
     * Urutan larik ini juga menjadi urutan pembacaan (sebelum diurutkan ulang).
     * Model produksi memakai kolom lama `probability` supaya rekaman lama tetap
     * terbaca tanpa migrasi data.
     */
    private const KOLOM_PROBABILITAS = [
        'rf'  => 'probability',
        'knn' => 'knn_probability',
        'svm' => 'svm_probability',
    ];

    /** Nama cadangan bila model_metadata.json tidak terbaca. */
    private const NAMA_CADANGAN = [
        'rf'  => 'Random Forest',
        'knn' => 'KNN',
        'svm' => 'SVM (Linear)',
    ];

    /** Ambang cadangan bila katalog tidak menyertakan ambang model. */
    private const AMBANG_CADANGAN = 0.5;

    /**
     * Jalankan inferensi untuk satu pasien.
     *
     * Urutan parameter sengaja mengikuti urutan argumen CLI `model/predict.py`
     * (age, hypertension, bmi, hba1c, glucose) — bukan urutan fitur saat training —
     * karena skrip Python-lah yang menyusun ulang vektor fiturnya sendiri.
     *
     * Mengembalikan larik hasil apa adanya dari Python; bila terjadi kegagalan yang
     * bisa ditangani, larik berisi kunci `error` (pemanggil wajib memeriksanya).
     */
    public function prediksi(float $age, int $hypertension, float $bmi, float $hba1c, float $glucose): array
    {
        // Mode inference: 'http' (microservice FastAPI) atau 'exec' (default, python lokal).
        $mode = config('services.ml.mode', 'exec');

        return $mode === 'http'
            ? $this->prediksiViaHttp($age, $hypertension, $bmi, $hba1c, $glucose)
            : $this->prediksiViaExec($age, $hypertension, $bmi, $hba1c, $glucose);
    }

    /**
     * Mode microservice: panggil FastAPI ML service via HTTP.
     * Model di-load sekali di service -> prediksi cepat & scale mandiri.
     */
    private function prediksiViaHttp(float $age, int $hypertension, float $bmi, float $hba1c, float $glucose): array
    {
        $base = rtrim(config('services.ml.service_url', 'http://localhost:8000'), '/');

        $response = Http::timeout(config('services.ml.http_timeout', 15))
            ->acceptJson()
            ->post($base . '/predict', [
                'age' => $age,
                'hypertension' => $hypertension,
                'bmi' => $bmi,
                'hba1c_level' => $hba1c,
                'blood_glucose_level' => $glucose,
            ]);

        if ($response->failed()) {
            return ['error' => 'ML service HTTP ' . $response->status() . ': ' . $response->body()];
        }

        $hasil = $response->json();

        return is_array($hasil) ? $hasil : ['error' => 'Invalid output from AI model.'];
    }

    /**
     * Mode default: jalankan model/predict.py lewat proses Python lokal.
     * Model di-load ulang tiap panggilan (lebih lambat, tapi tanpa service tambahan).
     */
    private function prediksiViaExec(float $age, int $hypertension, float $bmi, float $hba1c, float $glucose): array
    {
        // Fallback ke 'python3' agar aman di Linux/Docker bila PYTHON_PATH tak diset.
        $pythonPath = config('services.ml.python_path', 'python3');

        $command = sprintf(
            '"%s" "%s" %s %s %s %s %s 2>&1',
            $pythonPath,
            base_path('model/predict.py'),
            escapeshellarg((string) $age),
            escapeshellarg((string) $hypertension),
            escapeshellarg((string) $bmi),
            escapeshellarg((string) $hba1c),
            escapeshellarg((string) $glucose)
        );

        $outputLines = [];
        $returnVar = 0;
        exec($command, $outputLines, $returnVar);

        $output = implode("\n", $outputLines);

        if ($returnVar !== 0 && empty($outputLines)) {
            return ['error' => 'Analysis failed to run. Return code: ' . $returnVar];
        }

        $result = json_decode($output, true);

        return is_array($result) ? $result : ['error' => 'Invalid output from AI model.'];
    }

    /**
     * Katalog model dari model/model_metadata.json, terindeks berdasarkan id model.
     *
     * Berkas ini adalah sumber kebenaran untuk nama, ambang keputusan, dan metrik uji
     * tiap model. Isinya hanya berubah saat artefak model diekspor ulang dari notebook,
     * jadi aman di-cache; 10 menit cukup pendek agar pergantian artefak cepat terlihat.
     */
    public function katalogModel(): array
    {
        return Cache::remember('diapredict.katalog_model', now()->addMinutes(10), function () {
            $path = base_path('model/model_metadata.json');

            if (!is_file($path)) {
                Log::warning('model_metadata.json tidak ditemukan', ['path' => $path]);
                return [];
            }

            $data = json_decode((string) file_get_contents($path), true);

            if (!is_array($data) || !is_array($data['models'] ?? null)) {
                Log::warning('Blok "models" tidak ada atau tidak dapat diparse pada model_metadata.json');
                return [];
            }

            return $this->indeksKatalog($data['models']);
        });
    }

    /**
     * Susun data siap-render untuk panel perbandingan pada halaman detail.
     *
     * Angka diambil dari kolom database (bukan dari Python) supaya membuka halaman
     * detail tidak pernah menjalankan inferensi ulang. Nama, ambang, dan metrik
     * diambil dari katalog karena ketiganya properti model, bukan properti rekaman.
     *
     * Mengembalikan larik kosong untuk rekaman lama (KNN dan SVM sama-sama null),
     * yang menjadi tanda bagi controller untuk menghitung ulang sekali.
     *
     * @param  array<string, array<string, mixed>>|array<int, array<string, mixed>>  $katalog
     * @return array<int, array<string, mixed>>
     */
    public function rakitPerbandingan(?object $history, array $katalog): array
    {
        if ($history === null) {
            return [];
        }

        $knnPersen = $history->knn_probability ?? null;
        $svmPersen = $history->svm_probability ?? null;

        if ($knnPersen === null && $svmPersen === null) {
            return [];
        }

        $katalog = $this->indeksKatalog($katalog);
        $hasil = [];

        foreach (self::KOLOM_PROBABILITAS as $id => $kolom) {
            $persen = $history->{$kolom} ?? null;

            if ($persen === null) {
                continue;
            }

            $entri = $katalog[$id] ?? [];

            // Kolom database menyimpan persen; view bekerja dengan dua satuan sekaligus
            // (0..1 untuk perbandingan dengan ambang, 0..100 untuk teks & lebar bar).
            $probability = ((float) $persen) / 100;

            // Tiap model memakai AMBANG-nya sendiri (RF 0.4621, KNN 0.3810, SVM 0.4951).
            // Ambang itu hasil optimasi Youden's J per model; memakai satu ambang untuk
            // ketiganya membuat KNN tampak jauh lebih buruk daripada kenyataannya.
            // Keputusan dihitung ulang di sini (bukan diambil dari kolom prediction)
            // agar probabilitas, ambang, dan keputusan yang tampil selalu konsisten
            // walaupun artefak model diganti dengan ambang baru.
            $ambang = isset($entri['threshold']) ? (float) $entri['threshold'] : self::AMBANG_CADANGAN;

            $k = $this->jumlahTetangga($entri);
            $metrik = $entri['metrik_test'] ?? null;

            $hasil[] = [
                'id'               => $id,
                'nama'             => $entri['nama'] ?? (self::NAMA_CADANGAN[$id] ?? strtoupper($id)),
                'warna'            => self::WARNA[$id] ?? '#3498db',
                'probability'      => $probability,
                'persen'           => $probability * 100,
                'prediction'       => $probability >= $ambang ? 1 : 0,
                'threshold'        => $ambang,
                'produksi'         => ($entri['produksi'] ?? false) === true,
                'k'                => $k,
                // Probabilitas KNN hanyalah proporsi tetangga positif, jadi bentuk
                // "x dari k tetangga" lebih jujur daripada angka desimal panjang.
                'tetangga_positif' => $k !== null ? (int) round($probability * $k) : null,
                'metrik'           => is_array($metrik) && $metrik !== [] ? $metrik : null,
            ];
        }

        // Model produksi selalu di depan (itu yang dipakai untuk keputusan pasien),
        // sisanya menurun berdasarkan probabilitas.
        usort($hasil, function (array $a, array $b): int {
            if ($a['produksi'] !== $b['produksi']) {
                return $a['produksi'] ? -1 : 1;
            }

            return $b['probability'] <=> $a['probability'];
        });

        return $hasil;
    }

    /**
     * Ringkasan kesepakatan antar model.
     *
     * Label acuan adalah keputusan model produksi — bukan suara terbanyak — karena
     * model produksilah yang menentukan hasil yang ditampilkan ke pasien; model lain
     * berfungsi sebagai pembanding, bukan pemilih.
     *
     * @param  array<int, array<string, mixed>>  $perbandingan
     * @return array{setuju:int,total:int,bulat:bool,label:int}|null
     */
    public function konsensus(array $perbandingan): ?array
    {
        if ($perbandingan === []) {
            return null;
        }

        $daftar = array_values($perbandingan);
        $acuan = null;

        foreach ($daftar as $model) {
            if (!empty($model['produksi'])) {
                $acuan = $model;
                break;
            }
        }

        $label = (int) ($acuan['prediction'] ?? $daftar[0]['prediction'] ?? 0);

        $setuju = 0;
        foreach ($daftar as $model) {
            if ((int) ($model['prediction'] ?? -1) === $label) {
                $setuju++;
            }
        }

        $total = count($daftar);

        return [
            'setuju' => $setuju,
            'total'  => $total,
            'bulat'  => $setuju === $total,
            'label'  => $label,
        ];
    }

    /**
     * Terjemahkan respons Python menjadi kolom tabel analysis_results.
     *
     * Dipakai bersama oleh penyimpanan analisis baru, perhitungan ulang rekaman lama,
     * dan perintah backfill supaya konversi satuan (probabilitas -> persen) hanya
     * ditulis satu kali.
     *
     * Blok `models` boleh tidak ada (mis. artefak KNN/SVM belum diekspor): kolom
     * pembanding cukup diisi null, prediksi utama tetap tersimpan.
     *
     * @param  array<string, mixed>  $hasil
     * @return array<string, mixed>
     */
    public function kolomDariHasil(array $hasil): array
    {
        $models = is_array($hasil['models'] ?? null) ? $hasil['models'] : [];

        $kolom = [
            'prediction'    => (int) ($hasil['prediction'] ?? 0),
            'probability'   => (float) ($hasil['probability'] ?? 0) * 100,
            'model_version' => isset($hasil['model_version'])
                ? substr((string) $hasil['model_version'], 0, 32)
                : null,
        ];

        foreach (['knn', 'svm'] as $id) {
            $model = is_array($models[$id] ?? null) ? $models[$id] : [];

            $kolom[$id . '_probability'] = isset($model['probability'])
                ? (float) $model['probability'] * 100
                : null;
            $kolom[$id . '_prediction'] = isset($model['prediction'])
                ? (int) $model['prediction']
                : null;
        }

        return $kolom;
    }

    /**
     * Ubah katalog menjadi larik terindeks id model.
     *
     * Menerima dua bentuk: daftar (seperti pada model_metadata.json) maupun larik yang
     * sudah terindeks, supaya pemanggil — termasuk pengujian yang menyuntikkan katalog
     * palsu — tidak perlu tahu bentuk aslinya.
     *
     * @param  array<int|string, mixed>  $katalog
     * @return array<string, array<string, mixed>>
     */
    private function indeksKatalog(array $katalog): array
    {
        $hasil = [];

        foreach ($katalog as $kunci => $entri) {
            if (!is_array($entri)) {
                continue;
            }

            $id = $entri['id'] ?? (is_string($kunci) ? $kunci : null);

            if (!is_string($id) || $id === '') {
                continue;
            }

            $entri['id'] = $id;
            $hasil[$id] = $entri;
        }

        return $hasil;
    }

    /**
     * Nilai k pada KNN. Metadata menyimpannya di dua tempat tergantung versi notebook.
     *
     * @param  array<string, mixed>  $entri
     */
    private function jumlahTetangga(array $entri): ?int
    {
        $k = $entri['kuantisasi']['k'] ?? $entri['hyperparameter']['n_neighbors'] ?? null;

        return is_numeric($k) ? (int) $k : null;
    }
}

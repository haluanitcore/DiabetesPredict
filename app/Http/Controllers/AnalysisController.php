<?php

namespace App\Http\Controllers;

use App\Services\PrediksiMultiModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    /**
     * Seluruh pemanggilan model ML lewat service ini, tidak lagi dari controller,
     * supaya perintah CLI backfill memakai jalur inferensi yang persis sama.
     */
    public function __construct(private readonly PrediksiMultiModel $prediksi)
    {
    }

    /**
     * Sumber tunggal angka riset yang ditampilkan website.
     * Dihasilkan oleh notebook `Revisi_Pengujian_V3/06_Model_Final_dan_Export_Produksi.ipynb`
     * lalu disalin ke public/data/experiments.json. Dibaca (dan di-cache) di sini
     * supaya tidak ada lagi angka yang di-hardcode di dalam Blade.
     */
    private function experiments(): array
    {
        return Cache::remember('diapredict.experiments', now()->addMinutes(10), function () {
            $path = public_path('data/experiments.json');

            if (!is_file($path)) {
                Log::warning('experiments.json tidak ditemukan', ['path' => $path]);
                return [];
            }

            $data = json_decode((string) file_get_contents($path), true);

            if (!is_array($data)) {
                Log::warning('experiments.json tidak dapat diparse sebagai JSON');
                return [];
            }

            return $this->normalizeExperiments($data);
        });
    }

    /**
     * Menyeragamkan bentuk data riset.
     *
     * Berkas experiments.json bisa datang dari dua sumber dengan penamaan berbeda:
     * berkas dasar yang disiapkan manual, atau keluaran notebook 06 yang mengikuti
     * konvensi penamaan notebook. Normalisasi di satu tempat ini membuat Blade tidak
     * perlu tahu asal berkasnya, sekaligus mencegah bagian kosong tampil sebagai angka nol.
     */
    private function normalizeExperiments(array $data): array
    {
        // --- meta.dataset: samakan nama kunci dari kedua konvensi ---
        $ds = $data['meta']['dataset'] ?? [];
        $baris = $ds['jumlah_baris_setelah_dedup'] ?? $ds['baris_dipakai'] ?? null;
        $persenPositif = $ds['persen_kelas_positif'] ?? $ds['persen_positif'] ?? null;

        $rasioUji = $data['justifikasi_split']['rasio_terpilih']
            ?? $data['meta']['rasio_split']['uji']
            ?? 0.20;

        $nUji = $ds['jumlah_data_uji']
            ?? $data['justifikasi_split']['n_uji']
            ?? ($baris ? (int) round($baris * $rasioUji) : null);
        $nPositifUji = $ds['jumlah_positif_data_uji']
            ?? (($nUji && $persenPositif) ? (int) round($nUji * $persenPositif / 100) : null);

        $data['meta']['dataset'] = array_merge($ds, [
            'jumlah_baris_setelah_dedup' => $baris,
            'persen_kelas_positif'       => $persenPositif,
            'jumlah_data_uji'            => $nUji,
            'jumlah_positif_data_uji'    => $nPositifUji,
        ]);

        // --- warna seri + waktu inferensi per sampel ---
        // Notebook menyimpan `waktu_infer_ms` sebagai total untuk seluruh data uji,
        // sedangkan tabel menampilkan biaya per sampel. Konversinya dilakukan di sini
        // supaya Blade tidak perlu tahu satuan mana yang datang dari notebook.
        $warna = ['Random Forest' => '#3498db', 'KNN' => '#e74c3c', 'SVM (Linear)' => '#2ecc71', 'SVM' => '#2ecc71'];
        $data['perbandingan_model'] = array_map(function ($m) use ($warna, $nUji) {
            $m['warna'] = $m['warna'] ?? ($warna[$m['model'] ?? ''] ?? '#3498db');
            if (!isset($m['inferensi_ms_per_sampel']) && isset($m['waktu_infer_ms']) && $nUji) {
                $m['inferensi_ms_per_sampel'] = $m['waktu_infer_ms'] / $nUji;
            }
            return $m;
        }, $data['perbandingan_model'] ?? []);

        // --- McNemar: notebook menaruhnya di dalam validasi_statistik.uji_statistik ---
        if (empty($data['uji_mcnemar'])) {
            $data['uji_mcnemar'] = array_values(array_map(
                fn ($u) => [
                    'pasangan'   => $u['pasangan'] ?? $u['perbandingan'] ?? '-',
                    'p_value'    => $u['p_value'] ?? null,
                    'chi2'       => $u['chi2'] ?? $u['statistik'] ?? null,
                    'signifikan' => $u['signifikan'] ?? (($u['p_value'] ?? 1) < 0.05),
                ],
                array_filter(
                    $data['validasi_statistik']['uji_statistik'] ?? [],
                    fn ($u) => stripos($u['uji'] ?? '', 'mcnemar') !== false
                )
            ));
        }

        // --- XAI: samakan bentuk lama (metode/fitur) dengan bentuk notebook ---
        $xai = $data['xai'] ?? [];
        $penjelasanFitur = [
            'HbA1c_level'         => 'Indikator rata-rata glukosa darah 3 bulan terakhir. Prediktor medis terkuat.',
            'blood_glucose_level' => 'Kadar gula darah sewaktu. Kenaikan drastis berkorelasi langsung dengan diagnosis diabetes.',
            'age'                 => 'Faktor risiko degeneratif yang meningkat seiring bertambahnya usia.',
            'bmi'                 => 'Korelasi obesitas dengan resistensi insulin.',
            'hypertension'        => 'Riwayat tekanan darah tinggi yang memperparah sindrom metabolik.',
        ];
        if (empty($xai['fitur']) && !empty($xai['permutation_importance'])) {
            $xai['fitur'] = array_map(fn ($f) => [
                'nama'      => $f['label'] ?? $f['fitur'] ?? '-',
                'nilai'     => $f['nilai'] ?? 0,
                'deskripsi' => $penjelasanFitur[$f['fitur'] ?? ''] ?? '',
            ], $xai['permutation_importance']);
        }
        $xai['metode'] = $xai['metode'] ?? $xai['metode_utama'] ?? 'feature importance';
        $xai['catatan_metode'] = $xai['catatan_metode'] ?? $xai['sumber_metode_importance'] ?? null;
        $xai['model_sumber'] = $xai['model_sumber'] ?? null;
        $data['xai'] = $xai;

        // --- identitas sumber angka, dipakai di kepala halaman perbandingan ---
        $data['meta']['sumber_baseline'] = $data['meta']['sumber_baseline']
            ?? $data['meta']['dilatih_oleh']
            ?? $data['sumber_perbandingan_model']
            ?? null;

        // --- matriks keputusan: notebook 06 mengirim daftar, berkas dasar mengirim objek ---
        $mk = $data['matriks_keputusan'] ?? [];
        if (array_is_list($mk)) {
            $mk = ['skor' => $mk];
        }
        $mk['skor'] = $mk['skor'] ?? [];
        $mk['bobot'] = $mk['bobot']
            ?? ($data['ablation']['matriks_keputusan']['bobot'] ?? null)
            ?? ($data['ablation']['bobot_kriteria'] ?? null)
            ?? ['recall' => 0.35, 'roc_auc' => 0.25, 'precision' => 0.15, 'f1' => 0.10, 'kecepatan_inferensi' => 0.10, 'kompleksitas_model' => 0.05];
        $mk['sensitivitas_bobot'] = $mk['sensitivitas_bobot']
            ?? ($data['ablation']['sensitivitas_bobot'] ?? []);
        $mk['model_produksi'] = $mk['model_produksi']
            ?? ($data['ablation']['model_produksi'] ?? null)
            ?? ($data['meta']['model_produksi'] ?? 'Random Forest');
        $data['matriks_keputusan'] = $mk;

        // --- status tiap bagian: dihitung dari isinya, bukan dari label yang bisa basi ---
        $berisi = function ($bagian, array $kunciIsi): bool {
            if (!is_array($bagian)) return false;
            foreach ($kunciIsi as $k) {
                if (!empty($bagian[$k])) return true;
            }
            return false;
        };

        $petaIsi = [
            'justifikasi_split'  => ['tabel', 'margin_of_error', 'learning_curve'],
            'justifikasi_k'      => ['sweep', 'one_se_rule'],
            'justifikasi_svm'    => ['perbandingan_kernel', 'analisis_margin'],
            'validasi_statistik' => ['repeated_cv', 'nested_cv', 'uji_statistik'],
            'ablation'           => ['resampling', 'fitur', 'robustness', 'subgrup'],
            'matriks_keputusan'  => ['skor', 'sensitivitas_bobot'],
        ];

        foreach ($petaIsi as $bagian => $kunciIsi) {
            if (!isset($data[$bagian]) || !is_array($data[$bagian])) {
                $data[$bagian] = [];
            }
            $data[$bagian]['status'] = $berisi($data[$bagian], $kunciIsi) ? 'siap' : 'menunggu';
        }

        return $data;
    }

    public function comparison()
    {
        return view('analysis.comparison', ['exp' => $this->experiments()]);
    }

    public function showForm()
    {
        return view('analysis.form');
    }

    public function process(Request $request)
    {
        $validated = $request->validate([
            'age' => 'required|numeric|min:0|max:120',
            'hypertension' => 'required|in:0,1',
            'weight' => 'required|numeric|min:10|max:300',
            'height' => 'required|numeric|min:50|max:250',
            'bmi' => 'required|numeric|min:10|max:100',
            'hba1c_level' => 'required|numeric|min:3|max:20',
            'blood_glucose_level' => 'required|numeric|min:50|max:500',
        ]);

        // Normalize numeric inputs (replace comma with dot if user entered it)
        $bmi = (float) str_replace(',', '.', $validated['bmi']);
        $hba1c = (float) str_replace(',', '.', $validated['hba1c_level']);
        $age = (float) str_replace(',', '.', $validated['age']);
        $hypertension = (int) $validated['hypertension'];
        $glucose = (float) $validated['blood_glucose_level'];

        try {
            $result = $this->prediksi->prediksi($age, $hypertension, $bmi, $hba1c, $glucose);
        } catch (\Throwable $e) {
            Log::error('DiaPredict inference failed', [
                'mode' => config('services.ml.mode', 'exec'),
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Analysis Error: ' . $e->getMessage())->withInput();
        }

        if (!$result || isset($result['error'])) {
            $msg = $result['error'] ?? 'Invalid output from AI model.';
            return back()->with('error', 'Analysis Error: ' . $msg)->withInput();
        }

        // Save to Database.
        // Hasil per model (KNN/SVM) ikut disimpan lewat kolomDariHasil(); bila blok
        // `models` tidak ada pada respons Python, kolom pembandingnya menjadi null
        // dan analisis tetap tersimpan.
        $analysisId = DB::table('analysis_results')->insertGetId(array_merge([
            'user_id' => Auth::id(),
            'gender' => 0, // Default dummy
            'age' => $validated['age'],
            'hypertension' => $validated['hypertension'],
            'heart_disease' => 0, // Default dummy
            'smoking_history' => 0, // Default dummy
            'bmi' => $validated['bmi'],
            'hba1c_level' => $validated['hba1c_level'],
            'blood_glucose_level' => $validated['blood_glucose_level'],
            'created_at' => now(),
            'updated_at' => now(),
        ], $this->prediksi->kolomDariHasil($result)));

        return redirect()->route('analysis.history')->with('success', 'Analysis completed successfully.');
    }

    public function history()
    {
        $histories = DB::table('analysis_results')
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        $totalAnalyses = $histories->count();
            
        return view('analysis.history', compact('histories', 'totalAnalyses'));
    }

    public function show($id)
    {
        $history = DB::table('analysis_results')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$history) {
            abort(404);
        }

        $katalog = $this->prediksi->katalogModel();
        $perbandingan = $this->prediksi->rakitPerbandingan($history, $katalog);

        // Rekaman lama tidak punya hasil KNN/SVM. Rekaman itu juga dibuat oleh versi
        // predict.py yang menukar kolom BMI dan hipertensi saat menyusun vektor fitur,
        // jadi angka Random Forest-nya pun cacat. Karena itu perhitungan ulang dilakukan
        // menyeluruh (RF + KNN + SVM), lalu disimpan; kunjungan berikutnya cukup membaca
        // database. `model_version` yang sudah terisi menandakan rekaman pernah dihitung
        // ulang, sehingga inferensi tidak pernah berjalan dua kali untuk rekaman yang sama
        // (mis. saat artefak KNN/SVM memang belum tersedia di server).
        if ($perbandingan === [] && ($history->model_version ?? null) === null) {
            $history = $this->hitungUlangRekamanLama($history) ?? $history;
            $perbandingan = $this->prediksi->rakitPerbandingan($history, $katalog);
        }

        // `modelTerbaik` tidak lagi dikirim: panel tidak menandai satu model sebagai
        // yang dipakai, karena hasil pasien diambil dari model berprobabilitas
        // tertinggi -- bukan dari satu model tetap.
        return view('analysis.detail', [
            'history'      => $history,
            'perbandingan' => $perbandingan,
            'konsensus'    => $this->prediksi->konsensus($perbandingan),
            'exp'          => $this->experiments(),
        ]);
    }

    /**
     * Hitung ulang satu rekaman lama dan simpan hasilnya.
     *
     * Mengembalikan objek rekaman yang sudah diperbarui, atau null bila inferensi
     * gagal. Kegagalan sengaja tidak dilempar: halaman detail tetap harus tampil
     * (tanpa panel perbandingan) walau Python tidak tersedia di server.
     */
    private function hitungUlangRekamanLama(object $history): ?object
    {
        try {
            $hasil = $this->prediksi->prediksi(
                (float) $history->age,
                (int) $history->hypertension,
                (float) $history->bmi,
                (float) $history->hba1c_level,
                (float) $history->blood_glucose_level
            );

            if (isset($hasil['error'])) {
                Log::warning('Perhitungan ulang multi-model gagal', [
                    'id' => $history->id,
                    'error' => $hasil['error'],
                ]);
                return null;
            }

            if (empty($hasil['models'])) {
                // Artefak KNN/SVM belum ada di server: panel perbandingan tetap kosong,
                // tetapi nilai Random Forest yang cacat tetap diperbaiki dan disimpan.
                Log::warning('Respons model tidak memuat blok pembanding', ['id' => $history->id]);
            }

            $kolom = $this->prediksi->kolomDariHasil($hasil);

            DB::table('analysis_results')
                ->where('id', $history->id)
                ->update($kolom + ['updated_at' => now()]);

            foreach ($kolom as $nama => $nilai) {
                $history->{$nama} = $nilai;
            }

            return $history;
        } catch (\Throwable $e) {
            Log::warning('Perhitungan ulang multi-model gagal', [
                'id' => $history->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Entri katalog untuk model produksi, dilengkapi metrik risetnya.
     *
     * Metrik pada katalog berasal dari metadata artefak, sedangkan `metrik_riset`
     * berasal dari experiments.json (hasil notebook) — keduanya ditampilkan supaya
     * pembaca bisa memverifikasi bahwa model yang dipakai sistem sama dengan model
     * yang dilaporkan pada bab hasil.
     *
     * @param  array<string, array<string, mixed>>  $katalog
     * @param  array<string, mixed>  $exp
     * @return array<string, mixed>|null
     */
    private function modelTerbaik(array $katalog, array $exp): ?array
    {
        $produksi = null;

        foreach ($katalog as $entri) {
            if (is_array($entri) && ($entri['produksi'] ?? false) === true) {
                $produksi = $entri;
                break;
            }
        }

        if ($produksi === null) {
            return null;
        }

        $produksi['metrik_riset'] = null;

        foreach ($exp['perbandingan_model'] ?? [] as $riset) {
            if ($this->namaModelSama($riset['model'] ?? '', $produksi['nama'] ?? '')) {
                $produksi['metrik_riset'] = $riset;
                break;
            }
        }

        return $produksi;
    }

    /**
     * Cocokkan nama model dari dua sumber yang penulisannya berbeda,
     * mis. "KNN" pada experiments.json vs "KNN (k=21)" pada metadata artefak.
     */
    private function namaModelSama(string $a, string $b): bool
    {
        $bersihkan = fn (string $s): string => preg_replace('/[^a-z0-9]/', '', strtolower($s)) ?? '';

        $a = $bersihkan($a);
        $b = $bersihkan($b);

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($a, $b) || str_contains($b, $a);
    }
}

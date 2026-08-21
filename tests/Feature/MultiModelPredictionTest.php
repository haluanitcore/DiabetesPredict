<?php

namespace Tests\Feature;

use App\Services\PrediksiMultiModel;
use Tests\TestCase;

/**
 * Pengujian perakitan data perbandingan RF/KNN/SVM.
 *
 * Katalog dan rekaman sengaja dibuat palsu (bukan dibaca dari model_metadata.json
 * atau database) supaya pengujian tidak pernah memanggil Python dan tetap lulus di
 * mesin CI yang tidak punya artefak model.
 */
class MultiModelPredictionTest extends TestCase
{
    private PrediksiMultiModel $prediksi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prediksi = new PrediksiMultiModel();
    }

    /**
     * Katalog palsu dengan ambang asli tiap model (hasil Youden's J pada notebook).
     */
    private function katalogPalsu(): array
    {
        return [
            'rf' => [
                'id' => 'rf',
                'nama' => 'Random Forest',
                'produksi' => true,
                'threshold' => 0.46214982219609185,
                'metrik_test' => ['recall' => 0.9198, 'roc_auc' => 0.9732],
            ],
            'knn' => [
                'id' => 'knn',
                'nama' => 'KNN (k=21)',
                'produksi' => false,
                'threshold' => 0.380952,
                'hyperparameter' => ['n_neighbors' => 21],
                'kuantisasi' => ['k' => 21],
                'metrik_test' => ['recall' => 0.9121, 'roc_auc' => 0.9524],
            ],
            'svm' => [
                'id' => 'svm',
                'nama' => 'SVM (Linear)',
                'produksi' => false,
                'threshold' => 0.495105,
                'metrik_test' => ['recall' => 0.8833, 'roc_auc' => 0.9581],
            ],
        ];
    }

    /**
     * Tiruan satu baris analysis_results. Probabilitas dalam PERSEN, seperti di database.
     */
    private function riwayatPalsu(array $ubah = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'age' => 45,
            'hypertension' => 0,
            'bmi' => 27.5,
            'hba1c_level' => 6.1,
            'blood_glucose_level' => 140,
            'prediction' => 1,
            'probability' => 99.97,
            'knn_probability' => 100.0,
            'knn_prediction' => 1,
            'svm_probability' => 99.98,
            'svm_prediction' => 1,
            'model_version' => 'v3.0-revisi',
        ], $ubah);
    }

    public function test_rekaman_lama_tanpa_kolom_knn_dan_svm_menghasilkan_perbandingan_kosong(): void
    {
        $history = $this->riwayatPalsu([
            'knn_probability' => null,
            'knn_prediction' => null,
            'svm_probability' => null,
            'svm_prediction' => null,
            'model_version' => null,
        ]);

        $this->assertSame([], $this->prediksi->rakitPerbandingan($history, $this->katalogPalsu()));
    }

    public function test_riwayat_null_menghasilkan_perbandingan_kosong(): void
    {
        $this->assertSame([], $this->prediksi->rakitPerbandingan(null, $this->katalogPalsu()));
    }

    public function test_model_produksi_selalu_di_urutan_pertama_sisanya_menurun(): void
    {
        // Random Forest sengaja diberi probabilitas paling rendah untuk memastikan
        // urutan ditentukan oleh status produksi, bukan semata oleh besarnya angka.
        $history = $this->riwayatPalsu([
            'probability' => 20.0,
            'knn_probability' => 90.0,
            'svm_probability' => 50.0,
        ]);

        $perbandingan = $this->prediksi->rakitPerbandingan($history, $this->katalogPalsu());

        $this->assertSame(['rf', 'knn', 'svm'], array_column($perbandingan, 'id'));
        $this->assertTrue($perbandingan[0]['produksi']);
        $this->assertFalse($perbandingan[1]['produksi']);
        $this->assertGreaterThan($perbandingan[2]['probability'], $perbandingan[1]['probability']);
    }

    public function test_probabilitas_persen_dikonversi_ke_skala_nol_sampai_satu(): void
    {
        $history = $this->riwayatPalsu([
            'probability' => 99.97,
            'knn_probability' => 100.0,
            'svm_probability' => 42.5,
        ]);

        $perbandingan = collect($this->prediksi->rakitPerbandingan($history, $this->katalogPalsu()))
            ->keyBy('id');

        $this->assertEqualsWithDelta(0.9997, $perbandingan['rf']['probability'], 1e-6);
        $this->assertEqualsWithDelta(99.97, $perbandingan['rf']['persen'], 1e-6);
        $this->assertEqualsWithDelta(1.0, $perbandingan['knn']['probability'], 1e-6);
        $this->assertEqualsWithDelta(0.425, $perbandingan['svm']['probability'], 1e-6);
        $this->assertEqualsWithDelta(42.5, $perbandingan['svm']['persen'], 1e-6);

        // KNN: 21 dari 21 tetangga positif pada probabilitas 100%.
        $this->assertSame(21, $perbandingan['knn']['k']);
        $this->assertSame(21, $perbandingan['knn']['tetangga_positif']);
    }

    public function test_tiap_model_memakai_ambangnya_sendiri(): void
    {
        // Probabilitas identik 0,40 untuk ketiga model:
        // KNN (ambang 0,381) -> positif, RF (0,4621) dan SVM (0,4951) -> negatif.
        // Bila satu ambang dipakai untuk ketiganya, KNN akan salah dinilai negatif.
        $history = $this->riwayatPalsu([
            'probability' => 40.0,
            'knn_probability' => 40.0,
            'svm_probability' => 40.0,
        ]);

        $perbandingan = collect($this->prediksi->rakitPerbandingan($history, $this->katalogPalsu()))
            ->keyBy('id');

        $this->assertSame(1, $perbandingan['knn']['prediction']);
        $this->assertSame(0, $perbandingan['rf']['prediction']);
        $this->assertSame(0, $perbandingan['svm']['prediction']);

        $this->assertEqualsWithDelta(0.380952, $perbandingan['knn']['threshold'], 1e-6);
        $this->assertEqualsWithDelta(0.46214982219609185, $perbandingan['rf']['threshold'], 1e-9);
        $this->assertEqualsWithDelta(0.495105, $perbandingan['svm']['threshold'], 1e-6);
    }

    public function test_warna_dan_metrik_diambil_dari_katalog(): void
    {
        $perbandingan = collect($this->prediksi->rakitPerbandingan($this->riwayatPalsu(), $this->katalogPalsu()))
            ->keyBy('id');

        $this->assertSame('#3498db', $perbandingan['rf']['warna']);
        $this->assertSame('#e74c3c', $perbandingan['knn']['warna']);
        $this->assertSame('#2ecc71', $perbandingan['svm']['warna']);

        $this->assertSame('KNN (k=21)', $perbandingan['knn']['nama']);
        $this->assertSame(0.9198, $perbandingan['rf']['metrik']['recall']);
        $this->assertNull($perbandingan['rf']['k']);
        $this->assertNull($perbandingan['rf']['tetangga_positif']);
    }

    public function test_konsensus_bulat_saat_semua_model_sepakat(): void
    {
        $perbandingan = $this->prediksi->rakitPerbandingan($this->riwayatPalsu(), $this->katalogPalsu());

        $this->assertSame(
            ['setuju' => 3, 'total' => 3, 'bulat' => true, 'label' => 1],
            $this->prediksi->konsensus($perbandingan)
        );
    }

    public function test_konsensus_saat_model_tidak_sepakat(): void
    {
        // RF 90% -> 1, KNN 90% -> 1, SVM 10% -> 0. Label mengikuti model produksi (RF).
        $history = $this->riwayatPalsu([
            'probability' => 90.0,
            'knn_probability' => 90.0,
            'svm_probability' => 10.0,
        ]);

        $perbandingan = $this->prediksi->rakitPerbandingan($history, $this->katalogPalsu());

        $this->assertSame(
            ['setuju' => 2, 'total' => 3, 'bulat' => false, 'label' => 1],
            $this->prediksi->konsensus($perbandingan)
        );
    }

    public function test_konsensus_mengikuti_model_produksi_bukan_suara_terbanyak(): void
    {
        // RF 20% -> 0 (produksi), KNN & SVM tinggi -> 1. Meski kalah suara,
        // label tetap keputusan model produksi karena itulah hasil untuk pasien.
        $history = $this->riwayatPalsu([
            'probability' => 20.0,
            'knn_probability' => 95.0,
            'svm_probability' => 95.0,
        ]);

        $perbandingan = $this->prediksi->rakitPerbandingan($history, $this->katalogPalsu());
        $konsensus = $this->prediksi->konsensus($perbandingan);

        $this->assertSame(0, $konsensus['label']);
        $this->assertSame(1, $konsensus['setuju']);
        $this->assertFalse($konsensus['bulat']);
    }

    public function test_konsensus_null_saat_perbandingan_kosong(): void
    {
        $this->assertNull($this->prediksi->konsensus([]));
    }

    public function test_kolom_dari_hasil_memakai_probabilitas_tertinggi(): void
    {
        $kolom = $this->prediksi->kolomDariHasil([
            'prediction' => 1,
            'probability' => 0.9997,
            'model_version' => 'v3.0-revisi',
            'models' => [
                'rf' => ['probability' => 0.9997, 'prediction' => 1],
                'knn' => ['probability' => 1.0, 'prediction' => 1],
                'svm' => ['probability' => 0.4, 'prediction' => 0],
            ],
        ]);

        // KNN yang tertinggi (100%), jadi itu yang dipakai -- bukan Random Forest (99,97%).
        $this->assertSame(1, $kolom['prediction']);
        $this->assertEqualsWithDelta(100.0, $kolom['probability'], 1e-6);
        $this->assertEqualsWithDelta(100.0, $kolom['knn_probability'], 1e-6);
        $this->assertSame(1, $kolom['knn_prediction']);
        $this->assertEqualsWithDelta(40.0, $kolom['svm_probability'], 1e-6);
        $this->assertSame(0, $kolom['svm_prediction']);
        $this->assertSame('v3.0-revisi', $kolom['model_version']);
    }

    public function test_kolom_dari_hasil_mengikuti_model_tertinggi_walau_berbeda_label(): void
    {
        // Kasus nyata yang dilaporkan: Random Forest menilai Risiko Rendah (35,9%)
        // sementara SVM menilai Risiko Tinggi (64,1%). Hasil yang disimpan harus
        // mengikuti SVM, termasuk LABEL-nya, bukan hanya angkanya.
        $kolom = $this->prediksi->kolomDariHasil([
            'prediction' => 0,
            'probability' => 0.359,
            'models' => [
                'rf'  => ['probability' => 0.359, 'prediction' => 0],
                'knn' => ['probability' => 0.333, 'prediction' => 0],
                'svm' => ['probability' => 0.641, 'prediction' => 1],
            ],
        ]);

        $this->assertSame(1, $kolom['prediction'], 'label harus ikut SVM yang tertinggi');
        $this->assertEqualsWithDelta(64.1, $kolom['probability'], 1e-6);
    }

    public function test_kolom_dari_hasil_menghitung_label_bila_prediction_tidak_dikirim(): void
    {
        // Bila `prediction` per model tidak ada, label dihitung dari ambang model
        // itu sendiri -- bukan dari 50%.
        $kolom = $this->prediksi->kolomDariHasil([
            'prediction' => 0,
            'probability' => 0.20,
            'models' => [
                'rf'  => ['probability' => 0.20, 'threshold' => 0.4621],
                'knn' => ['probability' => 0.45, 'threshold' => 0.4118],
            ],
        ]);

        // KNN tertinggi (45%) dan melampaui ambangnya sendiri (41,18%) -> Risiko Tinggi,
        // walau angkanya masih di bawah 50%.
        $this->assertSame(1, $kolom['prediction']);
        $this->assertEqualsWithDelta(45.0, $kolom['probability'], 1e-6);
    }

    public function test_kolom_dari_hasil_tetap_aman_tanpa_blok_models(): void
    {
        // Artefak KNN/SVM belum diekspor: analisis tetap harus tersimpan.
        $kolom = $this->prediksi->kolomDariHasil([
            'prediction' => 0,
            'probability' => 0.12,
        ]);

        $this->assertSame(0, $kolom['prediction']);
        $this->assertEqualsWithDelta(12.0, $kolom['probability'], 1e-6);
        $this->assertNull($kolom['knn_probability']);
        $this->assertNull($kolom['knn_prediction']);
        $this->assertNull($kolom['svm_probability']);
        $this->assertNull($kolom['svm_prediction']);
        $this->assertNull($kolom['model_version']);
    }

    public function test_katalog_berbentuk_daftar_juga_diterima(): void
    {
        // model_metadata.json menyimpan `models` sebagai daftar, bukan larik terindeks id.
        $katalogDaftar = array_values($this->katalogPalsu());

        $perbandingan = $this->prediksi->rakitPerbandingan($this->riwayatPalsu(), $katalogDaftar);

        $this->assertCount(3, $perbandingan);
        $this->assertSame('rf', $perbandingan[0]['id']);
        $this->assertTrue($perbandingan[0]['produksi']);
    }
}

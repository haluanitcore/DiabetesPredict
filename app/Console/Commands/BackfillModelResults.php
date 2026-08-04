<?php

namespace App\Console\Commands;

use App\Services\PrediksiMultiModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Mengisi hasil KNN/SVM pada rekaman analisis yang dibuat sebelum panel
 * perbandingan ada.
 *
 * Halaman detail sebenarnya sudah menghitung ulang rekaman lama saat dibuka, tetapi
 * cara itu hanya menyentuh rekaman yang kebetulan dibuka. Perintah ini memproses
 * seluruh tabel sekaligus (mis. setelah deploy artefak model baru), sehingga tidak
 * ada pengguna yang menunggu inferensi berjalan di tengah request.
 */
class BackfillModelResults extends Command
{
    protected $signature = 'diapredict:backfill-models
                            {--limit= : Batasi jumlah baris yang diproses}
                            {--force : Proses ulang semua baris, termasuk yang hasil KNN/SVM-nya sudah terisi}';

    protected $description = 'Hitung ulang prediksi RF/KNN/SVM untuk rekaman analisis lama dan simpan ke kolom pembanding';

    public function handle(PrediksiMultiModel $prediksi): int
    {
        $query = DB::table('analysis_results')->orderBy('id');

        if (!$this->option('force')) {
            $query->whereNull('knn_probability');
        }

        $limit = $this->option('limit');
        if ($limit !== null && $limit !== '' && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $baris = $query->get();

        if ($baris->isEmpty()) {
            $this->info('Tidak ada rekaman yang perlu diproses.');
            return self::SUCCESS;
        }

        $this->info('Memproses ' . $baris->count() . ' rekaman...');

        $bar = $this->output->createProgressBar($baris->count());
        $bar->start();

        $diproses = 0;
        $gagal = 0;
        $berubah = 0;
        $catatanGagal = [];

        foreach ($baris as $rekaman) {
            try {
                $hasil = $prediksi->prediksi(
                    (float) $rekaman->age,
                    (int) $rekaman->hypertension,
                    (float) $rekaman->bmi,
                    (float) $rekaman->hba1c_level,
                    (float) $rekaman->blood_glucose_level
                );
            } catch (\Throwable $e) {
                $gagal++;
                $catatanGagal[] = ['id' => $rekaman->id, 'alasan' => $e->getMessage()];
                $bar->advance();
                continue;
            }

            if (isset($hasil['error'])) {
                $gagal++;
                $catatanGagal[] = ['id' => $rekaman->id, 'alasan' => (string) $hasil['error']];
                $bar->advance();
                continue;
            }

            $kolom = $prediksi->kolomDariHasil($hasil);

            // Rekaman lama dihitung oleh versi predict.py yang menukar kolom BMI dan
            // hipertensi, jadi keputusan Random Forest bisa berbeda setelah dihitung
            // ulang. Perubahan itu dihitung agar dampaknya terlihat di ringkasan.
            if ((int) $kolom['prediction'] !== (int) $rekaman->prediction) {
                $berubah++;
            }

            DB::table('analysis_results')
                ->where('id', $rekaman->id)
                ->update($kolom + ['updated_at' => now()]);

            $diproses++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Ringkasan', 'Jumlah'],
            [
                ['Rekaman diproses', $diproses],
                ['Gagal', $gagal],
                ['Keputusan Random Forest berubah', $berubah],
            ]
        );

        if ($catatanGagal !== []) {
            $this->warn('Rekaman yang gagal diproses:');
            foreach (array_slice($catatanGagal, 0, 10) as $catatan) {
                $this->line('  #' . $catatan['id'] . ' - ' . $catatan['alasan']);
            }
            if (count($catatanGagal) > 10) {
                $this->line('  ... dan ' . (count($catatanGagal) - 10) . ' rekaman lain.');
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

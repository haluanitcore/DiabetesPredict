<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Memberi Random Forest kolomnya sendiri.
     *
     * Sebelumnya kolom `probability` merangkap dua peran: hasil untuk pasien
     * SEKALIGUS probabilitas Random Forest (lihat KOLOM_PROBABILITAS pada
     * PrediksiMultiModel). Rangkap peran itu aman selama hasil pasien memang
     * selalu berasal dari Random Forest.
     *
     * Sejak hasil pasien diambil dari model BERPROBABILITAS TERTINGGI, rangkap
     * peran itu merusak data: `probability` terisi nilai model pemenang, sehingga
     * angka asli Random Forest hilang dan panel perbandingan menampilkan baris
     * "Random Forest" dengan angka milik model lain. Baris RF pun selalu seri
     * dengan nilai tertinggi, membuat acuan konsensus tak pernah berpindah.
     *
     * Setelah migrasi ini pembagian perannya tegas:
     *   probability / prediction        -> hasil yang ditampilkan untuk pasien
     *   rf_probability / rf_prediction  -> penilaian Random Forest itu sendiri
     *   knn_* , svm_*                   -> penilaian model pembanding
     *
     * Nullable karena rekaman lama belum punya nilainya; null berarti "belum
     * dihitung" dan ditangani sebagai cadangan di rakitPerbandingan().
     * Satuannya PERSEN, mengikuti kolom lain pada tabel ini.
     */
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->float('rf_probability')->nullable()->after('probability');
            $table->tinyInteger('rf_prediction')->nullable()->after('rf_probability');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropColumn(['rf_probability', 'rf_prediction']);
        });
    }
};

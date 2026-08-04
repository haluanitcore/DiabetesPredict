<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah hasil model pembanding (KNN & SVM) pada tabel analysis_results.
     *
     * Semua kolom nullable karena tabel sudah berisi rekaman lama yang dibuat
     * sebelum panel perbandingan ada: nilai null adalah penanda "belum dihitung",
     * dipakai controller & perintah backfill untuk menghitung ulang sekali.
     *
     * Satuan probabilitas KNN/SVM mengikuti kolom `probability` yang sudah ada,
     * yaitu PERSEN (sudah dikali 100), supaya tidak ada dua satuan pada satu tabel.
     */
    public function up(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->float('knn_probability')->nullable()->after('probability');
            $table->tinyInteger('knn_prediction')->nullable()->after('knn_probability');
            $table->float('svm_probability')->nullable()->after('knn_prediction');
            $table->tinyInteger('svm_prediction')->nullable()->after('svm_probability');

            // Versi artefak model saat prediksi dibuat (mis. "v3.0-revisi"). Berguna
            // untuk membedakan rekaman hasil model lama dan hasil model terbaru.
            $table->string('model_version', 32)->nullable()->after('svm_prediction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analysis_results', function (Blueprint $table) {
            $table->dropColumn([
                'knn_probability',
                'knn_prediction',
                'svm_probability',
                'svm_prediction',
                'model_version',
            ]);
        });
    }
};

@extends('layouts.app')

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
            Halaman ini menyajikan hasil studi komparatif dan analisis mendalam terhadap tiga algoritma pembelajaran mesin (Machine Learning) yang diuji menggunakan dataset prediksi diabetes tipe 2. Evaluasi didasarkan pada file riset ilmiah <code class="bg-surface-container-high px-2 py-0.5 rounded text-primary text-sm font-semibold">Diabetes_Prediction_RF_KNN_SVM_V2 (1).ipynb</code>.
        </p>
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
                    <!-- Random Forest Row -->
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="p-4 font-semibold text-primary flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#3498db]"></span>
                            Random Forest
                        </td>
                        <td class="p-4 text-center font-mono">0.4965</td>
                        <td class="p-4 text-center font-semibold text-on-surface">89.33%</td>
                        <td class="p-4 text-center font-semibold text-secondary-container bg-secondary/5 rounded">44.81% <span class="text-xs font-normal text-secondary">(Terbaik)</span></td>
                        <td class="p-4 text-center">90.57%</td>
                        <td class="p-4 text-center font-semibold text-secondary-container bg-secondary/5 rounded">59.95% <span class="text-xs font-normal text-secondary">(Terbaik)</span></td>
                        <td class="p-4 text-center font-semibold text-secondary-container bg-secondary/5 rounded">97.33% <span class="text-xs font-normal text-secondary">(Terbaik)</span></td>
                        <td class="p-4 text-center font-mono">0.018 ms</td>
                    </tr>
                    <!-- KNN Row -->
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="p-4 font-semibold text-[#e74c3c] flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#e74c3c]"></span>
                            K-Nearest Neighbors (KNN)
                        </td>
                        <td class="p-4 text-center font-mono">0.3810</td>
                        <td class="p-4 text-center">85.59%</td>
                        <td class="p-4 text-center">37.10%</td>
                        <td class="p-4 text-center font-semibold text-secondary-container bg-secondary/5 rounded">91.21% <span class="text-xs font-normal text-secondary">(Terbaik)</span></td>
                        <td class="p-4 text-center">52.74%</td>
                        <td class="p-4 text-center">95.24%</td>
                        <td class="p-4 text-center font-mono">0.081 ms</td>
                    </tr>
                    <!-- SVM Row -->
                    <tr class="hover:bg-primary/5 transition-colors">
                        <td class="p-4 font-semibold text-[#2ecc71] flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-[#2ecc71]"></span>
                            SVM (Linear)
                        </td>
                        <td class="p-4 text-center font-mono">0.4951</td>
                        <td class="p-4 text-center">87.75%</td>
                        <td class="p-4 text-center">40.97%</td>
                        <td class="p-4 text-center">88.33%</td>
                        <td class="p-4 text-center">55.98%</td>
                        <td class="p-4 text-center">95.81%</td>
                        <td class="p-4 text-center font-semibold text-secondary-container bg-secondary/5 rounded font-mono">0.000 ms <span class="text-xs font-normal text-secondary">(Terbaik)</span></td>
                    </tr>
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
            <div class="text-body-sm font-body-sm text-on-surface-variant text-center border-t border-outline-variant/30 pt-4">
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
            <div class="text-body-sm font-body-sm text-on-surface-variant text-center border-t border-outline-variant/30 pt-4">
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
                    <p class="text-on-surface-variant text-body-md">
                        Meskipun <strong>KNN</strong> memiliki nilai <strong>Recall tertinggi (91.21%)</strong>, sistem DiaPredict memilih <strong>Random Forest</strong> sebagai model utama dalam produksi karena keseimbangan performa yang jauh lebih unggul secara keseluruhan:
                    </p>
                    <ul class="list-disc pl-5 space-y-2 mt-1">
                        <li>
                            <strong>Akurasi & Diskriminasi Tinggi:</strong> Random Forest mendominasi dengan nilai <strong>ROC-AUC sebesar 97.33%</strong> (dibandingkan KNN yang hanya 95.24%), menunjukkan kekuatan pemisahan kelas positif dan negatif yang luar biasa.
                        </li>
                        <li>
                            <strong>Presisi Tinggi (Menghindari Alarm Palsu):</strong> Random Forest memiliki Presisi sebesar <strong>44.81%</strong>, sedangkan KNN hanya <strong>37.10%</strong>. Hal ini krusial untuk mencegah kelelahan diagnosis (diagnostic fatigue) pada pasien akibat alarm positif palsu (False Positives) yang berlebihan.
                        </li>
                        <li>
                            <strong>Recall Medis yang Sangat Dekat:</strong> Selisih Recall antara KNN (91.21%) and Random Forest (90.57%) sangatlah kecil (hanya <strong>0.64%</strong>). Kehilangan sedikit sensitivitas ini terbayar dengan peningkatan presisi yang sangat signifikan (naik <strong>7.71%</strong>).
                        </li>
                        <li>
                            <strong>Efisiensi Komputasi Uji (Inference Speed):</strong> Random Forest memproses sampel <strong>4,5 kali lebih cepat</strong> dibanding KNN (0.018 ms vs 0.081 ms per sampel). KNN membutuhkan pencarian tetangga terdekat di seluruh memori pada setiap prediksi, membuatnya tidak ramah skala untuk aplikasi real-time.
                        </li>
                    </ul>
                </div>

                <h3 class="text-headline-sm font-headline-sm text-on-surface mt-4">Analisis Signifikansi Statistik (McNemar's Test)</h3>
                <p>
                    Untuk memastikan bahwa perbedaan performa di atas bukan merupakan kebetulan belaka (random chance), uji hipotesis statistik non-parametrik <strong>McNemar's Test</strong> diterapkan pada hasil prediksi. Hasilnya menunjukkan:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>Random Forest vs KNN:</strong> p-value = 1.70e-80 (Perbedaan Sangat Signifikan secara Statistik)</li>
                    <li><strong>Random Forest vs SVM:</strong> p-value = 8.73e-15 (Perbedaan Sangat Signifikan secara Statistik)</li>
                    <li><strong>KNN vs SVM:</strong> p-value = 8.00e-21 (Perbedaan Sangat Signifikan secara Statistik)</li>
                </ul>
                <p>
                    Dengan p-value yang jauh lebih kecil dari tingkat signifikansi standar (&alpha; = 0.05), kita dapat menyimpulkan dengan keyakinan tinggi bahwa model Random Forest memang secara konsisten mengungguli model lainnya dalam memetakan kompleksitas fitur pasien.
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
                    Sistem medis tidak hanya membutuhkan prediksi yang akurat, tetapi juga dapat dijelaskan secara klinis. Berdasarkan analisis global <strong>SHAP (SHapley Additive exPlanations)</strong>, berikut adalah tingkat kepentingan fitur kunci dalam memicu prediksi positif diabetes:
                </p>

                <!-- Feature Importance List -->
                <div class="flex flex-col gap-3 mt-2">
                    <!-- Feature 1 -->
                    <div class="flex flex-col gap-1">
                        <div class="flex justify-between text-label-md font-label-md">
                            <span class="text-on-surface font-semibold">#1 HbA1c Level</span>
                            <span class="text-primary font-mono">0.3571</span>
                        </div>
                        <div class="w-full bg-outline-variant/30 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: 100%"></div>
                        </div>
                        <span class="text-xs text-on-surface-variant">Indikator rata-rata glukosa darah 3 bulan terakhir. Merupakan prediktor medis terkuat.</span>
                    </div>

                    <!-- Feature 2 -->
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex justify-between text-label-md font-label-md">
                            <span class="text-on-surface font-semibold">#2 Blood Glucose Level</span>
                            <span class="text-primary font-mono">0.2048</span>
                        </div>
                        <div class="w-full bg-outline-variant/30 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: 57.3%"></div>
                        </div>
                        <span class="text-xs text-on-surface-variant">Kadar gula darah sewaktu. Kenaikan drastis berkorelasi langsung dengan diagnosis diabetes.</span>
                    </div>

                    <!-- Feature 3 -->
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex justify-between text-label-md font-label-md">
                            <span class="text-on-surface font-semibold">#3 Usia (Age)</span>
                            <span class="text-primary font-mono">0.1286</span>
                        </div>
                        <div class="w-full bg-outline-variant/30 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: 36.0%"></div>
                        </div>
                        <span class="text-xs text-on-surface-variant">Faktor risiko degeneratif. Risiko terdeteksi meningkat secara eksponensial seiring bertambahnya usia.</span>
                    </div>

                    <!-- Feature 4 -->
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex justify-between text-label-md font-label-md">
                            <span class="text-on-surface font-semibold">#4 Indeks Massa Tubuh (BMI)</span>
                            <span class="text-primary font-mono">0.0667</span>
                        </div>
                        <div class="w-full bg-outline-variant/30 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: 18.7%"></div>
                        </div>
                        <span class="text-xs text-on-surface-variant">Korelasi obesitas dengan resistensi insulin.</span>
                    </div>

                    <!-- Feature 5 -->
                    <div class="flex flex-col gap-1 mt-1">
                        <div class="flex justify-between text-label-md font-label-md">
                            <span class="text-on-surface font-semibold">#5 Hipertensi (Hypertension)</span>
                            <span class="text-primary font-mono">0.0238</span>
                        </div>
                        <div class="w-full bg-outline-variant/30 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full" style="width: 6.7%"></div>
                        </div>
                        <span class="text-xs text-on-surface-variant">Riwayat tekanan darah tinggi yang memperparah sindrom metabolik.</span>
                    </div>
                </div>

                <div class="bg-secondary-container/10 border border-secondary/15 rounded-lg p-4 mt-2">
                    <span class="text-xs text-secondary font-semibold uppercase tracking-wider block mb-1">Catatan Medis</span>
                    <span class="text-body-sm font-body-sm text-on-surface-variant leading-relaxed block">
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
    document.addEventListener("DOMContentLoaded", function() {
        // --- 1. Performance Metrics Chart ---
        const ctxMetrics = document.getElementById('metricsChart').getContext('2d');
        new Chart(ctxMetrics, {
            type: 'bar',
            data: {
                labels: ['Accuracy', 'Precision', 'Recall', 'F1-Score', 'ROC-AUC'],
                datasets: [
                    {
                        label: 'Random Forest',
                        data: [89.33, 44.81, 90.57, 59.95, 97.33],
                        backgroundColor: 'rgba(52, 152, 219, 0.85)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    },
                    {
                        label: 'KNN',
                        data: [85.59, 37.10, 91.21, 52.74, 95.24],
                        backgroundColor: 'rgba(231, 76, 60, 0.85)',
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    },
                    {
                        label: 'SVM (Linear)',
                        data: [87.75, 40.97, 88.33, 55.98, 95.81],
                        backgroundColor: 'rgba(46, 204, 113, 0.85)',
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1.5,
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: "'Public Sans', sans-serif",
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 30,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // --- 2. Training Time Chart ---
        const ctxTraining = document.getElementById('trainingTimeChart').getContext('2d');
        new Chart(ctxTraining, {
            type: 'bar',
            data: {
                labels: ['Random Forest', 'KNN', 'SVM (Linear)'],
                datasets: [{
                    label: 'Waktu Training (detik)',
                    data: [5132.6, 539.1, 32.1],
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.75)',
                        'rgba(231, 76, 60, 0.75)',
                        'rgba(46, 204, 113, 0.75)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(231, 76, 60, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw.toLocaleString() + ' detik';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'logarithmic',
                        ticks: {
                            callback: function(value) {
                                if (value === 10 || value === 100 || value === 1000 || value === 10000) {
                                    return value + 's';
                                }
                                return null;
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // --- 3. Inference Time Chart ---
        const ctxInference = document.getElementById('inferenceTimeChart').getContext('2d');
        new Chart(ctxInference, {
            type: 'bar',
            data: {
                labels: ['Random Forest', 'KNN', 'SVM (Linear)'],
                datasets: [{
                    label: 'Inferensi / Sampel (ms)',
                    data: [0.018, 0.081, 0.0001], // SVM is set to 0.0001 for visual scale since it's 0.0000
                    backgroundColor: [
                        'rgba(52, 152, 219, 0.75)',
                        'rgba(231, 76, 60, 0.75)',
                        'rgba(46, 204, 113, 0.75)'
                    ],
                    borderColor: [
                        'rgba(52, 152, 219, 1)',
                        'rgba(231, 76, 60, 1)',
                        'rgba(46, 204, 113, 1)'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let val = context.raw;
                                if (val === 0.0001) return 'SVM: ~0.000 ms';
                                return val.toFixed(3) + ' ms';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        min: 0,
                        max: 0.1,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(3) + ' ms';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>
@endsection

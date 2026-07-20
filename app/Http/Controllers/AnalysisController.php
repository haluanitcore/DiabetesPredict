<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    public function comparison()
    {
        return view('analysis.comparison');
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

        // Mode inference dipilih via env: 'http' (microservice FastAPI) atau 'exec' (default, panggil python lokal).
        $mode = env('ML_MODE', 'exec');

        try {
            if ($mode === 'http') {
                $result = $this->predictViaHttp($age, $hypertension, $bmi, $hba1c, $glucose);
            } else {
                $result = $this->predictViaExec($age, $hypertension, $bmi, $hba1c, $glucose);
            }
        } catch (\Throwable $e) {
            Log::error('DiaPredict inference failed', ['mode' => $mode, 'error' => $e->getMessage()]);
            return back()->with('error', 'Analysis Error: ' . $e->getMessage())->withInput();
        }

        if (!$result || isset($result['error'])) {
            $msg = $result['error'] ?? 'Invalid output from AI model.';
            return back()->with('error', 'Analysis Error: ' . $msg)->withInput();
        }

        // Save to Database
        $analysisId = DB::table('analysis_results')->insertGetId([
            'user_id' => Auth::id(),
            'gender' => 0, // Default dummy
            'age' => $validated['age'],
            'hypertension' => $validated['hypertension'],
            'heart_disease' => 0, // Default dummy
            'smoking_history' => 0, // Default dummy
            'bmi' => $validated['bmi'],
            'hba1c_level' => $validated['hba1c_level'],
            'blood_glucose_level' => $validated['blood_glucose_level'],
            'prediction' => $result['prediction'],
            'probability' => $result['probability'] * 100, // Convert to percentage
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('analysis.history')->with('success', 'Analysis completed successfully.');
    }

    /**
     * Mode microservice: panggil FastAPI ML service via HTTP.
     * Model di-load sekali di service -> prediksi cepat & scale mandiri.
     */
    private function predictViaHttp(float $age, int $hypertension, float $bmi, float $hba1c, float $glucose): array
    {
        $base = rtrim(env('ML_SERVICE_URL', 'http://localhost:8000'), '/');

        $response = Http::timeout((int) env('ML_HTTP_TIMEOUT', 15))
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

        return $response->json();
    }

    /**
     * Mode default: jalankan model/predict.py lewat proses Python lokal.
     * Model di-load ulang tiap panggilan (lebih lambat, tapi tanpa service tambahan).
     */
    private function predictViaExec(float $age, int $hypertension, float $bmi, float $hba1c, float $glucose): array
    {
        // Fallback ke 'python3' agar aman di Linux/Docker bila PYTHON_PATH tak diset.
        $pythonPath = env('PYTHON_PATH', 'python3');

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

        return view('analysis.detail', compact('history'));
    }
}

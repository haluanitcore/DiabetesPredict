<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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

        // Build the arguments array
        // Normalize numeric inputs (replace comma with dot if user entered it)
        $bmi = str_replace(',', '.', $validated['bmi']);
        $hba1c = str_replace(',', '.', $validated['hba1c_level']);
        $age = str_replace(',', '.', $validated['age']);

        // Fallback ke 'python3' agar aman di Linux/Docker bila PYTHON_PATH tak diset.
        $pythonPath = env('PYTHON_PATH', 'python3');

        // Build the command line string
        $command = sprintf(
            '"%s" "%s" %s %s %s %s %s 2>&1',
            $pythonPath,
            base_path('model/predict.py'),
            escapeshellarg($age),
            escapeshellarg($validated['hypertension']),
            escapeshellarg($bmi),
            escapeshellarg($hba1c),
            escapeshellarg($validated['blood_glucose_level'])
        );

        // Execute using native exec() to avoid Symfony Process pipe issues on Windows
        $outputLines = [];
        $returnVar = 0;
        exec($command, $outputLines, $returnVar);

        $output = implode("\n", $outputLines);

        if ($returnVar !== 0 && empty($outputLines)) {
             return back()->with('error', 'Analysis failed to run. Return code: ' . $returnVar)->withInput();
        }

        $result = json_decode($output, true);

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

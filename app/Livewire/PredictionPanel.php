<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\School;
use App\Models\Student;
use App\Services\DropoutPredictionService;

class PredictionPanel extends Component
{
    public $search = '';
    public $school_id = '';
    public $standard = '';
    public $risk_level = '';

    public function render()
    {
        $predictor = new DropoutPredictionService();

        // Get enrolled students and analyze
        $query = Student::with('school')->enrolled();
        
        $user = auth()->user();
        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        } elseif ($this->school_id) {
            $query->where('school_id', $this->school_id);
        }

        if ($this->standard) {
            $query->where('standard', $this->standard);
        }
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $allStudents = $query->get();
        $analyzed = [];
        $highRiskCount = 0;
        $mediumRiskCount = 0;
        $lowRiskCount = 0;
        $totalRiskScore = 0;

        foreach ($allStudents as $student) {
            $analysis = $predictor->analyzeStudent($student);
            
            // Filter by risk level if requested
            if ($this->risk_level && $analysis['level'] !== $this->risk_level) {
                continue;
            }

            $student->ai_analysis = $analysis;
            $analyzed[] = $student;

            if ($analysis['level'] === 'High Risk') {
                $highRiskCount++;
            } elseif ($analysis['level'] === 'Medium Risk') {
                $mediumRiskCount++;
            } else {
                $lowRiskCount++;
            }
            $totalRiskScore += $analysis['score'];
        }

        $count = count($analyzed);
        $avgRiskScore = $count > 0 ? round($totalRiskScore / $count, 1) : 0;

        // Paginate/Sort manually or just pass collection
        $students = collect($analyzed)->sortByDesc(function($s) {
            return $s->ai_analysis['score'];
        })->take(100); 

        $schools = School::orderBy('name')->get();

        return view('livewire.prediction-panel', compact(
            'students',
            'schools',
            'highRiskCount',
            'mediumRiskCount',
            'lowRiskCount',
            'avgRiskScore',
            'count'
        ));
    }
}

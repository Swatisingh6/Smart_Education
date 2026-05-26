<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Student;
use App\Models\StudentPerformance;
use App\Models\Notification;
use App\Models\ActivityLog;

class PerformanceTracker extends Component
{
    public $standard = 8;
    public $student_id = '';
    public $subject = 'Mathematics';
    public $marks_obtained = '';
    public $max_marks = 100;
    public $term = 'Term 1';
    public $academic_year = '2025-2026';

    public function updatedStandard()
    {
        $this->student_id = '';
    }

    public function storePerformance()
    {
        $this->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string',
            'marks_obtained' => 'required|integer|min:0|max:100',
            'max_marks' => 'required|integer',
            'term' => 'required|string',
            'academic_year' => 'required|string'
        ]);

        $student = Student::findOrFail($this->student_id);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'save_grade',
            'description' => "Logged exam subject marks for '{$student->name}' in {$this->subject} via Livewire.",
            'ip_address' => request()->ip(),
        ]);

        StudentPerformance::create([
            'student_id' => $this->student_id,
            'subject' => $this->subject,
            'marks_obtained' => $this->marks_obtained,
            'max_marks' => $this->max_marks,
            'term' => $this->term,
            'academic_year' => $this->academic_year
        ]);

        // Trigger subject failure recommendation warnings
        if ($this->marks_obtained < 50) {
            Notification::create([
                'user_id' => auth()->id(),
                'title' => 'ACADEMIC PERFORMANCE FAILURE',
                'message' => "Student '{$student->name}' scored sub-optimal marks ({$this->marks_obtained}%) in {$this->subject}.",
                'type' => 'dropout_risk'
            ]);

            // Add dynamic student intervention recommendation
            $student->studentInterventions()->create([
                'intervention_type' => 'Counseling',
                'details' => "Recommended for remedial tutoring in {$this->subject} due to scoring under 50% in standard exam.",
                'status' => 'Recommended',
                'cost' => 0.00
            ]);
        }

        // Reset input fields
        $this->student_id = '';
        $this->marks_obtained = '';

        session()->flash('success', 'Grades logged successfully and synced with recommendation engine!');
    }

    public function render()
    {
        $user = auth()->user();
        $query = Student::with(['school', 'performances'])->enrolled();

        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $query->where('standard', $this->standard);
        $students = $query->orderBy('name')->get();

        return view('livewire.performance-tracker', compact('students'));
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Student;
use App\Models\FeedbackComplaint;
use App\Models\Notification;
use App\Models\ActivityLog;

class ComplaintsDesk extends Component
{
    public $name = '';
    public $type = 'Financial Issue';
    public $student_id = '';
    public $description = '';

    // Array to bind responses for individual complaints: complaint_id => response_text
    public $responses = [];

    public function mount()
    {
        $this->name = auth()->user()->name;
    }

    public function storeComplaint()
    {
        $this->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'description' => 'required|string',
            'student_id' => 'nullable|exists:students,id'
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'file_complaint',
            'description' => "Filed support ticket '{$this->type}' regarding student logistics via Livewire.",
            'ip_address' => request()->ip(),
        ]);

        FeedbackComplaint::create([
            'student_id' => $this->student_id ?: null,
            'user_id' => auth()->id(),
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'status' => 'Pending'
        ]);

        Notification::create([
            'user_id' => auth()->id(),
            'title' => 'NEW COMPLAINT FILED',
            'message' => "Support request filed under '{$this->type}' by parent '{$this->name}'.",
            'type' => 'complaint'
        ]);

        $this->description = '';
        $this->student_id = '';

        session()->flash('success', 'Support request logged successfully! School principal has been notified.');
    }

    public function respondComplaint($complaintId)
    {
        $this->validate([
            "responses.{$complaintId}" => 'required|string'
        ]);

        $complaint = FeedbackComplaint::findOrFail($complaintId);
        $response = $this->responses[$complaintId];

        $complaint->update([
            'status' => 'Resolved',
            'response' => $response
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'respond_complaint',
            'description' => "Audited complaint #{$complaintId} and resolved with response via Livewire.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('success', "Complaint resolved successfully!");
    }

    public function render()
    {
        $user = auth()->user();
        
        $compQuery = FeedbackComplaint::with('student.school');
        $studQuery = Student::enrolled();

        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $compQuery->whereHas('student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
            $studQuery->where('school_id', $user->school_id);
        }

        $complaints = $compQuery->orderBy('created_at', 'desc')->get();
        $students = $studQuery->orderBy('name')->get();

        return view('livewire.complaints-desk', compact('complaints', 'students'));
    }
}

<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\ActivityLog;

class DocumentLocker extends Component
{
    use WithFileUploads;

    public $student_id = '';
    public $document_type = 'Aadhaar';
    public $file;

    public function verifyDocument($documentId, $status)
    {
        $doc = StudentDocument::findOrFail($documentId);
        $doc->update([
            'status' => $status,
            'notes' => "Verified by Principal on " . date('Y-m-d')
        ]);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'verify_document',
            'description' => "Audited student document '{$doc->document_type}' status to '{$status}' via Livewire.",
            'ip_address' => request()->ip(),
        ]);

        session()->flash('success', "Document status updated to '{$status}' successfully!");
    }

    public function uploadDocument()
    {
        $this->validate([
            'student_id' => 'required|exists:students,id',
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:2048'
        ]);

        $student = Student::findOrFail($this->student_id);

        if ($this->file) {
            $path = $this->file->store('private/documents');

            StudentDocument::create([
                'student_id' => $this->student_id,
                'document_type' => $this->document_type,
                'file_path' => $path,
                'status' => 'Pending'
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'upload_document',
                'description' => "Uploaded secure certificate '{$this->document_type}' for '{$student->name}' via Livewire.",
                'ip_address' => request()->ip(),
            ]);

            // Reset file input
            $this->student_id = '';
            $this->file = null;

            session()->flash('success', 'Certificate uploaded to secure student locker and queued for audit review!');
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $docQuery = StudentDocument::with('student.school');
        $studQuery = Student::enrolled();

        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $docQuery->whereHas('student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
            $studQuery->where('school_id', $user->school_id);
        }

        $documents = $docQuery->orderBy('created_at', 'desc')->get();
        $students = $studQuery->orderBy('name')->get();

        return view('livewire.document-locker', compact('documents', 'students'));
    }
}

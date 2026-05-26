<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Notification;

class AttendanceManager extends Component
{
    public $standard = 8;
    public $date;
    public $attendance = []; // student_id => status
    public $remarks = []; // student_id => remarks

    public function mount()
    {
        $this->date = date('Y-m-d');
        $this->loadRoster();
    }

    public function updatedStandard()
    {
        $this->loadRoster();
    }

    public function updatedDate()
    {
        $this->loadRoster();
    }

    public function loadRoster()
    {
        $user = auth()->user();
        $query = Student::with('school')->enrolled();

        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $query->where('standard', $this->standard);
        $students = $query->orderBy('name')->get();

        $attendanceLogs = Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('date', $this->date)
            ->get();

        $this->attendance = [];
        $this->remarks = [];

        foreach ($students as $st) {
            $log = $attendanceLogs->where('student_id', $st->id)->first();
            $this->attendance[$st->id] = $log ? $log->status : 'Present';
            $this->remarks[$st->id] = $log ? $log->remarks : '';
        }
    }

    public function saveAttendance()
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:today',
            'standard' => 'required|integer',
            'attendance' => 'required|array',
            'remarks' => 'nullable|array'
        ]);

        \App\Models\ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'save_attendance',
            'description' => "Logged daily student attendance for Class {$this->standard} on {$this->date} via Livewire.",
            'ip_address' => request()->ip(),
        ]);

        foreach ($this->attendance as $studentId => $status) {
            $remark = $this->remarks[$studentId] ?? null;

            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $this->date],
                ['status' => $status, 'remarks' => $remark]
            );

            // Fetch student and check average attendance
            $student = Student::find($studentId);
            if ($student && $student->attendance_rate < 75) {
                // Trigger dynamic Low Attendance Alert
                Notification::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'title' => 'LOW ATTENDANCE WARNING',
                        'message' => "Student '{$student->name}' has dropped below the 75% baseline (current: {$student->attendance_rate}%).",
                        'type' => 'low_attendance'
                    ]
                );
            }
        }

        session()->flash('success', 'Attendance record saved successfully and synced with the AI Risk Module!');
    }

    public function render()
    {
        $user = auth()->user();
        $query = Student::with('school')->enrolled();

        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $query->where('standard', $this->standard);
        $students = $query->orderBy('name')->get();

        return view('livewire.attendance-manager', compact('students'));
    }
}

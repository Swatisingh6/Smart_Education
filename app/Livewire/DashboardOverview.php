<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\School;
use App\Models\Student;
use App\Models\Intervention;
use Illuminate\Support\Facades\DB;

class DashboardOverview extends Component
{
    public $academic_year = '';
    public $school_id = '';
    public $area_type = '';
    public $caste = '';

    public function mount()
    {
        // Set initial values if passed in request
        $this->academic_year = request('academic_year', '');
        $this->school_id = request('school_id', '');
        $this->area_type = request('area_type', '');
        $this->caste = request('caste', '');
    }

    public function updated()
    {
        $this->dispatchChartsUpdate();
    }

    public function resetFilters()
    {
        $this->academic_year = '';
        $this->school_id = '';
        $this->area_type = '';
        $this->caste = '';
        
        $this->dispatchChartsUpdate();
    }

    private function applyFilters($query)
    {
        $user = auth()->user();
        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('students.school_id', $user->school_id);
        } elseif ($this->school_id) {
            $query->where('students.school_id', $this->school_id);
        }

        if ($this->academic_year) {
            $query->where('students.academic_year', $this->academic_year);
        }
        if ($this->area_type) {
            if (str_contains($query->toSql(), 'join `schools`') || str_contains($query->toSql(), 'join schools')) {
                $query->where('schools.area_type', $this->area_type);
            } else {
                $query->whereHas('school', function ($q) {
                    $q->where('area_type', $this->area_type);
                });
            }
        }
        if ($this->caste) {
            $query->where('students.caste', $this->caste);
        }
        return $query;
    }

    private function getInterventionMetrics($targetType, $targetValue)
    {
        $query = Student::query();
        
        if ($targetType === 'Gender') {
            $query->where('gender', $targetValue);
        } elseif ($targetType === 'Caste') {
            $query->where('caste', $targetValue);
        } elseif ($targetType === 'Standard') {
            $std = (int) filter_var($targetValue, FILTER_SANITIZE_NUMBER_INT);
            $query->where('standard', $std ?: 9);
        } elseif ($targetType === 'Area') {
            $query->whereHas('school', function ($q) use ($targetValue) {
                $q->where('area_type', $targetValue);
            });
        } elseif ($targetType === 'School') {
            $query->whereHas('school', function ($q) use ($targetValue) {
                $q->where('name', 'like', '%' . $targetValue . '%');
            });
        }

        $total = (clone $query)->count();
        $dropouts = $query->droppedOut()->count();

        return [
            'total' => $total,
            'dropouts' => $dropouts
        ];
    }

    private function dispatchChartsUpdate()
    {
        // Prepare reasons data
        $topReasons = $this->applyFilters(Student::droppedOut())
            ->select('dropout_reason', DB::raw('count(*) as count'))
            ->groupBy('dropout_reason')
            ->orderByDesc('count')
            ->take(4)
            ->get();

        $reasonsLabels = $topReasons->pluck('dropout_reason')->toArray();
        $reasonsData = $topReasons->pluck('count')->toArray();

        // Prepare area data
        $areaStats = $this->applyFilters(Student::select('schools.area_type', 'students.status', DB::raw('count(*) as count'))
            ->join('schools', 'students.school_id', '=', 'schools.id'))
            ->groupBy('schools.area_type', 'students.status')
            ->get()
            ->groupBy('area_type');

        $urbanTotal = isset($areaStats['Urban']) ? $areaStats['Urban']->sum('count') : 0;
        $urbanDropouts = isset($areaStats['Urban']) ? ($areaStats['Urban']->where('status', 'Dropped Out')->first()?->count ?? 0) : 0;
        $urbanEnrolled = $urbanTotal - $urbanDropouts;

        $ruralTotal = isset($areaStats['Rural']) ? $areaStats['Rural']->sum('count') : 0;
        $ruralDropouts = isset($areaStats['Rural']) ? ($areaStats['Rural']->where('status', 'Dropped Out')->first()?->count ?? 0) : 0;
        $ruralEnrolled = $ruralTotal - $ruralDropouts;

        $this->dispatch('charts-data-updated', [
            'reasonsLabels' => $reasonsLabels,
            'reasonsData' => $reasonsData,
            'urbanEnrolled' => $urbanEnrolled,
            'urbanDropouts' => $urbanDropouts,
            'ruralEnrolled' => $ruralEnrolled,
            'ruralDropouts' => $ruralDropouts,
        ]);
    }

    public function render()
    {
        $totalStudents = $this->applyFilters(Student::query())->count();
        $totalEnrolled = $this->applyFilters(Student::enrolled())->count();
        $totalDropouts = $this->applyFilters(Student::droppedOut())->count();
        $dropoutRate = $totalStudents > 0 ? round(($totalDropouts / $totalStudents) * 100, 1) : 0;

        $activeInterventions = Intervention::where('status', 'Active')->get();
        $totalBudget = Intervention::sum('budget_allocated');

        $preventedDropouts = 0;
        foreach ($activeInterventions as $int) {
            $metrics = $this->getInterventionMetrics($int->target_type, $int->target_value);
            $preventedDropouts += round($metrics['dropouts'] * ($int->expected_reduction_rate / 100));
        }

        $criticalSchools = School::withCount([
            'students',
            'students as dropouts_count' => function ($q) {
                $q->droppedOut();
            }
        ])->get()
        ->map(function ($school) {
            $school->dropout_rate = $school->students_count > 0 
                ? round(($school->dropouts_count / $school->students_count) * 100, 1) 
                : 0;
            return $school;
        })
        ->sortByDesc('dropout_rate')
        ->take(3);

        $insights = [];
        
        $worstSchool = School::withCount([
            'students',
            'students as dropouts_count' => function ($q) {
                $q->droppedOut();
            }
        ])->get()
        ->map(function ($s) {
            $s->rate = $s->students_count > 0 ? ($s->dropouts_count / $s->students_count) * 100 : 0;
            return $s;
        })->sortByDesc('rate')->first();
        
        if ($worstSchool && $worstSchool->rate > 0) {
            $insights[] = [
                'type' => 'critical',
                'title' => 'Critical Institutional Dropout Alert',
                'message' => "School '{$worstSchool->name}' has the highest dropout rate at " . round($worstSchool->rate, 1) . "%, far exceeding the 5.0% state limit.",
                'icon' => 'fa-school-flag'
            ];
        }

        $femaleRuralDropouts = Student::droppedOut()->where('gender', 'Female')->whereHas('school', function($q){
            $q->where('area_type', 'Rural');
        })->count();
        $femaleUrbanDropouts = Student::droppedOut()->where('gender', 'Female')->whereHas('school', function($q){
            $q->where('area_type', 'Urban');
        })->count();
        
        if ($femaleRuralDropouts > $femaleUrbanDropouts) {
            $diffRatio = $femaleUrbanDropouts > 0 ? round((($femaleRuralDropouts - $femaleUrbanDropouts) / $femaleUrbanDropouts) * 100) : 100;
            $insights[] = [
                'type' => 'warning',
                'title' => 'Rural Female Vulnerability',
                'message' => "Girls in rural communities are highly vulnerable, dropping out at a {$diffRatio}% higher rate than their urban peers.",
                'icon' => 'fa-venus'
            ];
        }

        $transitionSpikeCount = Student::droppedOut()->whereIn('standard', [8, 9, 10])->count();
        $totalDropoutsCount = Student::droppedOut()->count();
        if ($totalDropoutsCount > 0) {
            $transitionRatio = round(($transitionSpikeCount / $totalDropoutsCount) * 100);
            if ($transitionRatio > 30) {
                $insights[] = [
                    'type' => 'info',
                    'title' => 'Middle School Transition Spikes',
                    'message' => "Transition dropouts are heavily concentrated in Standards 8 to 10, accounting for {$transitionRatio}% of all regional dropouts.",
                    'icon' => 'fa-arrow-trend-up'
                ];
            }
        }

        $povertyCount = Student::droppedOut()->where('dropout_reason', 'Poverty')->count();
        if ($totalDropoutsCount > 0) {
            $povertyRatio = round(($povertyCount / $totalDropoutsCount) * 100);
            $insights[] = [
                'type' => 'info',
                'title' => 'Socio-Economic Threat Factor',
                'message' => "Poverty remains the primary systemic barrier, directly driving {$povertyRatio}% of all recorded dropouts.",
                'icon' => 'fa-sack-dollar'
            ];
        }

        $activityLogs = \App\Models\ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentDropouts = $this->applyFilters(Student::with('school'))
            ->droppedOut()
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        $allSchools = School::orderBy('name')->get();

        return view('livewire.dashboard-overview', compact(
            'totalStudents',
            'totalEnrolled',
            'totalDropouts',
            'dropoutRate',
            'activeInterventions',
            'totalBudget',
            'preventedDropouts',
            'criticalSchools',
            'allSchools',
            'insights',
            'activityLogs',
            'recentDropouts'
        ));
    }
}

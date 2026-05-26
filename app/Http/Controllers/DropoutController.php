<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use App\Models\Intervention;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DropoutController extends Controller
{
    /**
     * Helper to log activities in the database.
     */
    private function logActivity($action, $description)
    {
        \App\Models\ActivityLog::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Helper to apply HTTP query filters chain across student records.
     */
    private function applyFilters($query)
    {
        $user = auth()->user();
        if ($user && in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('students.school_id', $user->school_id);
        } elseif (request()->filled('school_id')) {
            $query->where('students.school_id', request('school_id'));
        }

        if (request()->filled('gender')) {
            $query->where('students.gender', request('gender'));
        }
        if (request()->filled('caste')) {
            $query->where('students.caste', request('caste'));
        }
        if (request()->filled('academic_year')) {
            $query->where('students.academic_year', request('academic_year'));
        }
        if (request()->filled('area_type')) {
            // Check if query is a join or standard Eloquent
            if (str_contains($query->toSql(), 'join `schools`') || str_contains($query->toSql(), 'join schools')) {
                $query->where('schools.area_type', request('area_type'));
            } else {
                $query->whereHas('school', function ($q) {
                    $q->where('area_type', request('area_type'));
                });
            }
        }
        return $query;
    }

    /**
     * Share common header alerts across views.
     */
    private function getSystemAlerts()
    {
        $alerts = [];
        
        // Threshold check 1: high dropout schools
        $criticalCount = School::withCount([
            'students',
            'students as dropouts_count' => function ($q) {
                $q->droppedOut();
            }
        ])->get()
        ->map(function ($s) {
            $s->rate = $s->students_count > 0 ? ($s->dropouts_count / $s->students_count) * 100 : 0;
            return $s;
        })->where('rate', '>=', 25)->count();
        
        if ($criticalCount > 0) {
            $alerts[] = [
                'title' => 'CRITICAL DROP LIMIT EXCEEDED',
                'desc' => "{$criticalCount} government institutions exceed the 25% critical dropout rate!",
                'time' => '1 min ago',
                'type' => 'critical'
            ];
        }
        
        // Active policies count budget alert
        $activeCount = Intervention::where('status', 'Active')->count();
        $totalAlloc = Intervention::sum('budget_allocated');
        if ($totalAlloc > 4000000) {
            $alerts[] = [
                'title' => 'BUDGET ALLOCATION ALERT',
                'desc' => "₹" . number_format($totalAlloc/100000, 1) . " Lakhs allocated across {$activeCount} active policy schemes.",
                'time' => '1 hour ago',
                'type' => 'budget'
            ];
        }
        
        // Recent dropout report alert
        $latestDropout = Student::droppedOut()->orderBy('updated_at', 'desc')->first();
        if ($latestDropout) {
            $alerts[] = [
                'title' => 'NEW DROPOUT REPORTED',
                'desc' => "Student '{$latestDropout->name}' registered as dropped due to {$latestDropout->dropout_reason}.",
                'time' => $latestDropout->updated_at->diffForHumans(),
                'type' => 'report'
            ];
        }

        return $alerts;
    }

    /**
     * Helper to calculate matching students and dropouts for an intervention.
     */
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

    /**
     * Dashboard Overview (Index)
     */
    public function index()
    {
        $totalStudents = $this->applyFilters(Student::query())->count();
        $totalEnrolled = $this->applyFilters(Student::enrolled())->count();
        $totalDropouts = $this->applyFilters(Student::droppedOut())->count();
        $dropoutRate = $totalStudents > 0 ? round(($totalDropouts / $totalStudents) * 100, 1) : 0;

        $activeInterventions = Intervention::where('status', 'Active')->get();
        $totalBudget = Intervention::sum('budget_allocated');

        // Dynamic calculation of prevented dropouts
        $preventedDropouts = 0;
        foreach ($activeInterventions as $int) {
            $metrics = $this->getInterventionMetrics($int->target_type, $int->target_value);
            $preventedDropouts += round($metrics['dropouts'] * ($int->expected_reduction_rate / 100));
        }

        // Top 3 critical schools (highest dropout rates)
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

        // Top dropout reasons
        $topReasons = $this->applyFilters(Student::droppedOut())
            ->select('dropout_reason', DB::raw('count(*) as count'))
            ->groupBy('dropout_reason')
            ->orderByDesc('count')
            ->take(4)
            ->get();

        // Area overview (Urban vs Rural)
        $areaStats = $this->applyFilters(Student::select('schools.area_type', 'students.status', DB::raw('count(*) as count'))
            ->join('schools', 'students.school_id', '=', 'schools.id'))
            ->groupBy('schools.area_type', 'students.status')
            ->get()
            ->groupBy('area_type');

        // Recent dropouts log for display
        $recentDropouts = $this->applyFilters(Student::with('school'))
            ->droppedOut()
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Fetch all schools for dashboard filter select box
        $allSchools = School::orderBy('name')->get();

        // Compile dynamic AI insights
        $insights = [];
        
        // Insight 1: Highest dropout school
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

        // Insight 2: Vulnerable demographic (Gender rural vs urban)
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

        // Insight 3: Grade Transition dropout spikes
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

        // Insight 4: Poverty leading reason
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

        // Fetch recent administrative activities
        $activityLogs = \App\Models\ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Get header notifications
        $headerAlerts = $this->getSystemAlerts();

        // Share the alerts with the view or layouts
        view()->share('headerAlerts', $headerAlerts);

        return view('dashboard', compact(
            'totalStudents',
            'totalEnrolled',
            'totalDropouts',
            'dropoutRate',
            'activeInterventions',
            'totalBudget',
            'preventedDropouts',
            'criticalSchools',
            'topReasons',
            'areaStats',
            'recentDropouts',
            'allSchools',
            'insights',
            'activityLogs'
        ));
    }

    /**
     * Detailed category analysis views
     */
    public function analysis($category)
    {
        // Share alerts with layouts
        view()->share('headerAlerts', $this->getSystemAlerts());

        if ($category === 'school') {
            // School-wise analytics
            $schools = School::withCount([
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
            ->sortByDesc('dropout_rate');

            return view('analysis.school', compact('schools'));
        }

        if ($category === 'area') {
            // Area-wise analytics (Rural vs Urban, and District-wise)
            $areaData = Student::select('schools.area_type', 'students.status', DB::raw('count(*) as count'))
                ->join('schools', 'students.school_id', '=', 'schools.id')
                ->groupBy('schools.area_type', 'students.status')
                ->get();

            $districtData = Student::select('schools.district', 'students.status', DB::raw('count(*) as count'))
                ->join('schools', 'students.school_id', '=', 'schools.id')
                ->groupBy('schools.district', 'students.status')
                ->get()
                ->groupBy('district')
                ->map(function ($items) {
                    $total = $items->sum('count');
                    $dropouts = $items->where('status', 'Dropped Out')->first()?->count ?? 0;
                    return [
                        'total' => $total,
                        'dropouts' => $dropouts,
                        'rate' => $total > 0 ? round(($dropouts / $total) * 100, 1) : 0
                    ];
                })
                ->sortByDesc('rate');

            // Reasons by Area Type
            $areaReasons = Student::join('schools', 'students.school_id', '=', 'schools.id')
                ->droppedOut()
                ->select('schools.area_type', 'students.dropout_reason', DB::raw('count(*) as count'))
                ->groupBy('schools.area_type', 'students.dropout_reason')
                ->orderBy('schools.area_type')
                ->orderByDesc('count')
                ->get()
                ->groupBy('area_type');

            return view('analysis.area', compact('areaData', 'districtData', 'areaReasons'));
        }

        if ($category === 'demographics') {
            // Gender-wise
            $genderStats = Student::select('gender', 'status', DB::raw('count(*) as count'))
                ->groupBy('gender', 'status')
                ->get()
                ->groupBy('gender')
                ->map(function ($items) {
                    $total = $items->sum('count');
                    $dropouts = $items->where('status', 'Dropped Out')->first()?->count ?? 0;
                    return [
                        'total' => $total,
                        'dropouts' => $dropouts,
                        'rate' => $total > 0 ? round(($dropouts / $total) * 100, 1) : 0
                    ];
                });

            $genderReasons = Student::droppedOut()
                ->select('gender', 'dropout_reason', DB::raw('count(*) as count'))
                ->groupBy('gender', 'dropout_reason')
                ->orderBy('gender')
                ->orderByDesc('count')
                ->get()
                ->groupBy('gender');

            // Caste-wise
            $casteStats = Student::select('caste', 'status', DB::raw('count(*) as count'))
                ->groupBy('caste', 'status')
                ->get()
                ->groupBy('caste')
                ->map(function ($items) {
                    $total = $items->sum('count');
                    $dropouts = $items->where('status', 'Dropped Out')->first()?->count ?? 0;
                    return [
                        'total' => $total,
                        'dropouts' => $dropouts,
                        'rate' => $total > 0 ? round(($dropouts / $total) * 100, 1) : 0
                    ];
                });

            $casteReasons = Student::droppedOut()
                ->select('caste', 'dropout_reason', DB::raw('count(*) as count'))
                ->groupBy('caste', 'dropout_reason')
                ->orderBy('caste')
                ->orderByDesc('count')
                ->get()
                ->groupBy('caste');

            return view('analysis.demographics', compact('genderStats', 'genderReasons', 'casteStats', 'casteReasons'));
        }

        if ($category === 'academic') {
            // Standard/Age-wise analytics
            $standardStats = Student::select('standard', 'status', DB::raw('count(*) as count'))
                ->groupBy('standard', 'status')
                ->get()
                ->groupBy('standard')
                ->map(function ($items) {
                    $total = $items->sum('count');
                    $dropouts = $items->where('status', 'Dropped Out')->first()?->count ?? 0;
                    return [
                        'total' => $total,
                        'dropouts' => $dropouts,
                        'rate' => $total > 0 ? round(($dropouts / $total) * 100, 1) : 0
                    ];
                });

            // Database-specific age calculation (supports SQLite and MySQL)
            $ageRaw = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'mysql'
                ? '(YEAR(CURDATE()) - YEAR(date_of_birth)) as age'
                : "(strftime('%Y', 'now') - strftime('%Y', date_of_birth)) as age";

            $ageStats = Student::select(\Illuminate\Support\Facades\DB::raw($ageRaw), 'status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
                ->groupBy('age', 'status')
                ->get()
                ->groupBy('age')
                ->map(function ($items) {
                    $total = $items->sum('count');
                    $dropouts = $items->where('status', 'Dropped Out')->first()?->count ?? 0;
                    return [
                        'total' => $total,
                        'dropouts' => $dropouts,
                        'rate' => $total > 0 ? round(($dropouts / $total) * 100, 1) : 0
                    ];
                })
                ->sortKeys();

            // Transition reasons (Primary: 1-5, Middle: 6-8, Secondary: 9-10, Sr Secondary: 11-12)
            $transitionReasons = Student::droppedOut()
                ->select(DB::raw("CASE 
                    WHEN standard <= 5 THEN 'Primary (1-5)'
                    WHEN standard <= 8 THEN 'Middle (6-8)'
                    WHEN standard <= 10 THEN 'Secondary (9-10)'
                    ELSE 'Sr. Secondary (11-12)'
                END as level"), 'dropout_reason', DB::raw('count(*) as count'))
                ->groupBy('level', 'dropout_reason')
                ->orderBy('level')
                ->orderByDesc('count')
                ->get()
                ->groupBy('level');

            return view('analysis.academic', compact('standardStats', 'ageStats', 'transitionReasons'));
        }

        abort(404);
    }

    /**
     * Interventions Index & Policy Simulator
     */
    public function interventions()
    {
        view()->share('headerAlerts', $this->getSystemAlerts());

        $interventions = Intervention::orderBy('created_at', 'desc')->get();
        $schools = School::orderBy('name')->get();
        $enrolledStudents = Student::enrolled()->orderBy('name')->take(100)->get(); 

        return view('interventions', compact('interventions', 'schools', 'enrolledStudents'));
    }

    /**
     * Store new intervention
     */
    public function storeIntervention(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|string',
            'target_value' => 'nullable|string',
            'type' => 'required|string',
            'description' => 'nullable|string',
            'budget_allocated' => 'required|numeric|min:0',
            'status' => 'required|string',
            'expected_reduction_rate' => 'required|integer|min:0|max:100',
        ]);

        $int = Intervention::create($request->all());

        $this->logActivity('create_intervention', "Launched new policy scheme '{$int->name}' with allocation ₹" . number_format($int->budget_allocated) . ".");

        return redirect()->route('interventions.index')->with('success', 'Intervention scheme launched successfully!');
    }

    /**
     * Simulate Policy Intervention via Ajax
     */
    public function simulate(Request $request)
    {
        $targetType = $request->input('target_type');
        $targetValue = $request->input('target_value');
        $policyType = $request->input('type');
        $reductionRate = (int) $request->input('expected_reduction_rate', 15);

        // Get matching students metrics
        $metrics = $this->getInterventionMetrics($targetType, $targetValue);
        
        $preventedDropouts = round($metrics['dropouts'] * ($reductionRate / 100));
        
        // Cost estimation based on policy type & target volume
        $costPerStudent = 0;
        switch ($policyType) {
            case 'Meal': $costPerStudent = 1200; break;
            case 'Transport': $costPerStudent = 3000; break;
            case 'Scholarship': $costPerStudent = 5000; break;
            case 'Counseling': $costPerStudent = 500; break;
            case 'Infrastructure': $costPerStudent = 1500; break;
        }

        $estimatedBudget = $metrics['total'] * $costPerStudent;

        return response()->json([
            'target_students' => $metrics['total'],
            'current_dropouts' => $metrics['dropouts'],
            'prevented_dropouts' => $preventedDropouts,
            'estimated_budget' => $estimatedBudget,
            'cost_per_student' => $costPerStudent,
            'target_description' => "Targeting all {$targetType} = '{$targetValue}' students. Cost: ₹" . number_format($costPerStudent) . "/student per year."
        ]);
    }

    /**
     * Report mock dropout (marks student as dropped out)
     */
    public function reportDropout(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'dropout_reason' => 'required|string',
        ]);

        $student = Student::findOrFail($request->input('student_id'));
        $student->status = 'Dropped Out';
        $student->dropout_reason = $request->input('dropout_reason');
        $student->dropout_date = Carbon::now()->toDateString();
        $student->save();

        $this->logActivity('report_dropout', "Marked student '{$student->name}' as dropped out due to '{$student->dropout_reason}'.");

        return redirect()->back()->with('success', "Student '{$student->name}' has been marked as dropped out due to {$student->dropout_reason}. Analytics updated.");
    }

    /**
     * SQLite Database Manager & Diagnostics Panel
     */
    public function databaseManager()
    {
        view()->share('headerAlerts', $this->getSystemAlerts());

        $schools = School::orderBy('name')->get();
        
        // Paginate students, showing recently added/updated records first
        $students = Student::with('school')->orderBy('id', 'desc')->paginate(15);
        
        // Diagnostics
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $dbName = config('database.connections.mysql.database');
            $sizeResult = DB::select("
                SELECT SUM(data_length + index_length) AS size 
                FROM information_schema.TABLES 
                WHERE table_schema = ?
            ", [$dbName]);
            $dbSizeByte = $sizeResult[0]->size ?? 0;
            $dbPath = "MySQL Host: " . config('database.connections.mysql.host') . ", Database: " . $dbName;
        } else {
            $dbPath = database_path('database.sqlite');
            $dbSizeByte = file_exists($dbPath) ? filesize($dbPath) : 0;
        }

        $dbSize = $dbSizeByte > 1024 * 1024 
            ? round($dbSizeByte / (1024 * 1024), 2) . ' MB'
            : round($dbSizeByte / 1024, 2) . ' KB';
            
        $diagnostics = [
            'connection' => $driver,
            'path' => $dbPath,
            'size' => $dbSize,
            'counts' => [
                'users' => DB::table('users')->count(),
                'schools' => DB::table('schools')->count(),
                'students' => DB::table('students')->count(),
                'interventions' => DB::table('interventions')->count(),
            ]
        ];

        return view('database_manager', compact('schools', 'students', 'diagnostics'));
    }

    /**
     * Store a brand new school record
     */
    public function storeSchool(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'area_type' => 'required|string',
            'district' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
        ]);

        $school = School::create($request->all());

        $this->logActivity('store_school', "Registered new school '{$school->name}' under {$school->type} sector.");

        return redirect()->route('database.manager')
            ->with('success', "School '{$request->name}' registered successfully in the database!")
            ->with('new_school_id', $school->id);
    }

    /**
     * Store a brand new student record
     */
    public function storeStudent(Request $request)
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:255',
            'gender' => 'required|string',
            'caste' => 'required|string',
            'date_of_birth' => 'required|date',
            'standard' => 'required|integer|min:1|max:12',
            'area_village_city' => 'required|string|max:255',
            'parent_income' => 'required|numeric|min:0',
            'academic_year' => 'required|string|max:15',
        ]);

        $student = Student::create(array_merge($request->all(), [
            'status' => 'Enrolled',
        ]));

        $this->logActivity('store_student', "Registered student record for '{$student->name}' in Class {$student->standard}.");

        return redirect()->route('database.manager')
            ->with('success', "Student '{$request->name}' registered successfully in the database!")
            ->with('new_student_id', $student->id);
    }

    /**
     * Destroy Student Record
     */
    public function destroyStudent(Student $student)
    {
        $name = $student->name;
        $student->delete();
        
        $this->logActivity('delete_student', "Removed student record '{$name}' from the database.");

        return redirect()->route('database.manager')->with('success', "Student '{$name}' deleted successfully!");
    }

    /**
     * Destroy School Record (Cascading Students)
     */
    public function destroySchool(School $school)
    {
        $name = $school->name;
        $school->delete();
        
        $this->logActivity('delete_school', "Removed school record '{$name}' and all associated student entries.");

        return redirect()->route('database.manager')->with('success', "School '{$name}' deleted successfully!");
    }

    /**
     * Update Student Details
     */
    public function updateStudent(Request $request, Student $student)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|string',
            'caste' => 'required|string',
            'date_of_birth' => 'required|date',
            'standard' => 'required|integer|min:1|max:12',
            'area_village_city' => 'required|string|max:255',
            'parent_income' => 'required|numeric|min:0',
            'academic_year' => 'required|string|max:15',
            'status' => 'required|string',
            'dropout_reason' => 'nullable|string',
        ]);

        // If status changes to Enrolled, clear dropout columns
        $data = $request->all();
        if ($request->input('status') === 'Enrolled') {
            $data['dropout_reason'] = null;
            $data['dropout_date'] = null;
        } elseif ($request->input('status') === 'Dropped Out' && $student->status !== 'Dropped Out') {
            $data['dropout_date'] = Carbon::now()->toDateString();
        }

        $student->update($data);

        $this->logActivity('update_student', "Updated student configurations for '{$student->name}'.");

        return redirect()->route('database.manager')->with('success', "Student '{$student->name}' updated successfully!");
    }

    /**
     * Update School Details
     */
    public function updateSchool(Request $request, School $school)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'area_type' => 'required|string',
            'district' => 'required|string|max:255',
            'pincode' => 'required|string|max:10',
        ]);

        $school->update($request->all());

        $this->logActivity('update_school', "Updated school configuration profiles for '{$school->name}'.");

        return redirect()->route('database.manager')->with('success', "School '{$school->name}' updated successfully!");
    }

    /**
     * Reports Module overview view
     */
    public function reports()
    {
        view()->share('headerAlerts', $this->getSystemAlerts());

        $schools = School::orderBy('name')->get();
        
        $query = Student::with('school');
        $query = $this->applyFilters($query);
        
        // Paginate results
        $students = $query->orderBy('name')->paginate(15);
        
        // Summary stats under active filters
        $totalStudents = (clone $query)->count();
        $totalEnrolled = (clone $query)->enrolled()->count();
        $totalDropouts = (clone $query)->droppedOut()->count();
        $dropoutRate = $totalStudents > 0 ? round(($totalDropouts / $totalStudents) * 100, 1) : 0;
        
        // Breakdown aggregates
        $genderBreakdown = (clone $query)->select('gender', DB::raw('count(*) as count'))
            ->groupBy('gender')->get();
            
        $casteBreakdown = (clone $query)->select('caste', DB::raw('count(*) as count'))
            ->groupBy('caste')->get();

        return view('reports', compact(
            'schools',
            'students',
            'totalStudents',
            'totalEnrolled',
            'totalDropouts',
            'dropoutRate',
            'genderBreakdown',
            'casteBreakdown'
        ));
    }

    /**
     * Map Heatmap & Geo Analytics
     */
    public function heatmap()
    {
        $this->logActivity('view_heatmap', "Accessed the geographic Leaflet heatmap analytics map.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        // Get schools with dropout rates
        $schools = School::withCount([
            'students',
            'students as dropouts_count' => function ($q) {
                $q->droppedOut();
            }
        ])->get()
        ->map(function ($school) {
            $school->dropout_rate = $school->students_count > 0 
                ? round(($school->dropouts_count / $school->students_count) * 100, 1) 
                : 0;

            // Map district coordinates
            $coords = [
                'Jaipur' => [26.9124, 75.7873],
                'Udaipur' => [24.5854, 73.7125],
                'Jodhpur' => [26.2389, 73.0243],
                'Kota' => [25.2138, 75.8648],
                'Ajmer' => [26.4499, 74.6399],
                'Bikaner' => [28.0166, 73.3119],
                'Tonk' => [26.1558, 75.7852],
                'Sikar' => [27.6018, 75.1396]
            ];

            $base = $coords[$school->district] ?? [26.5, 74.5];
            
            // Add stable pseudo-random offset based on school ID
            $offsetLat = (($school->id % 7) - 3) * 0.015;
            $offsetLng = (($school->id % 5) - 2) * 0.015;

            $school->latitude = $base[0] + $offsetLat;
            $school->longitude = $base[1] + $offsetLng;

            // Assign risk level
            if ($school->dropout_rate >= 25) {
                $school->risk_level = 'High';
                $school->marker_color = '#f43f5e'; // Red
            } elseif ($school->dropout_rate >= 15) {
                $school->risk_level = 'Medium';
                $school->marker_color = '#f59e0b'; // Orange
            } else {
                $school->risk_level = 'Low';
                $school->marker_color = '#10b981'; // Green
            }

            return $school;
        });

        return view('heatmap', compact('schools'));
    }

    /**
     * AI Dropout Risk Predictions
     */
    public function predictions()
    {
        $this->logActivity('view_predictions', "Accessed the AI Dropout Risk Predictions panel.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        $predictor = new \App\Services\DropoutPredictionService();

        // Get enrolled students and analyze
        $query = Student::with('school')->enrolled();
        
        // Apply standard dashboard filters
        if (request()->filled('school_id')) {
            $query->where('school_id', request('school_id'));
        }
        if (request()->filled('gender')) {
            $query->where('gender', request('gender'));
        }
        if (request()->filled('caste')) {
            $query->where('caste', request('caste'));
        }
        if (request()->filled('standard')) {
            $query->where('standard', request('standard'));
        }
        if (request()->filled('academic_year')) {
            $query->where('academic_year', request('academic_year'));
        }
        if (request()->filled('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
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
            if (request()->filled('risk_level') && $analysis['level'] !== request('risk_level')) {
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

        // Paginate manually or just pass collection
        $students = collect($analyzed)->sortByDesc(function($s) {
            return $s->ai_analysis['score'];
        })->take(100); 

        $schools = School::orderBy('name')->get();

        return view('predictions', compact(
            'students',
            'schools',
            'highRiskCount',
            'mediumRiskCount',
            'lowRiskCount',
            'avgRiskScore',
            'count'
        ));
    }

    /**
     * Attendance Management Board
     */
    public function attendance()
    {
        $this->logActivity('view_attendance', "Accessed classroom student attendance register book.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        $user = auth()->user();
        $query = Student::with('school')->enrolled();

        // Boundary constraints matching user role
        if (in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $selectedStandard = request('standard', 8);
        $selectedDate = request('date', date('Y-m-d'));

        $query->where('standard', $selectedStandard);
        $students = $query->orderBy('name')->get();

        // Get existing attendance logs for this day
        $attendanceLogs = \App\Models\Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('date', $selectedDate)
            ->get();

        return view('attendance.index', compact('students', 'attendanceLogs', 'selectedStandard', 'selectedDate'));
    }

    /**
     * Store student daily attendance logs
     */
    public function storeAttendance(Request $request)
    {
        $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'standard' => 'required|integer',
            'attendance' => 'required|array',
            'remarks' => 'nullable|array'
        ]);

        $date = $request->date;
        $standard = $request->standard;
        $attendanceData = $request->attendance;
        $remarksData = $request->remarks ?? [];

        $this->logActivity('save_attendance', "Logged daily student attendance for Class {$standard} on {$date}.");

        foreach ($attendanceData as $studentId => $status) {
            $remarks = $remarksData[$studentId] ?? null;

            // Find or create attendance
            $log = \App\Models\Attendance::updateOrCreate(
                ['student_id' => $studentId, 'date' => $date],
                ['status' => $status, 'remarks' => $remarks]
            );

            // Fetch student and check average attendance
            $student = Student::find($studentId);
            if ($student && $student->attendance_rate < 75) {
                // Trigger dynamic Low Attendance Alert
                \App\Models\Notification::updateOrCreate(
                    [
                        'user_id' => auth()->id(),
                        'title' => 'LOW ATTENDANCE WARNING',
                        'message' => "Student '{$student->name}' has dropped below the 75% baseline (current: {$student->attendance_rate}%).",
                        'type' => 'low_attendance'
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Attendance record saved successfully and synced with the AI Risk Module!');
    }

    /**
     * Classroom Student Performance scorebook
     */
    public function performance()
    {
        $this->logActivity('view_performance', "Accessed student academic scorecard markbook.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        $user = auth()->user();
        $query = Student::with(['school', 'performances'])->enrolled();

        if (in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $query->where('school_id', $user->school_id);
        }

        $selectedStandard = request('standard', 8);
        $query->where('standard', $selectedStandard);
        $students = $query->orderBy('name')->get();

        return view('performance.index', compact('students', 'selectedStandard'));
    }

    /**
     * Store student academic grades
     */
    public function storePerformance(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string',
            'marks_obtained' => 'required|integer|min:0|max:100',
            'max_marks' => 'required|integer',
            'term' => 'required|string',
            'academic_year' => 'required|string'
        ]);

        $st = Student::find($request->student_id);
        $this->logActivity('save_grade', "Logged exam subject marks for '{$st->name}' in {$request->subject}.");

        $perf = \App\Models\StudentPerformance::create([
            'student_id' => $request->student_id,
            'subject' => $request->subject,
            'marks_obtained' => $request->marks_obtained,
            'max_marks' => $request->max_marks,
            'term' => $request->term,
            'academic_year' => $request->academic_year
        ]);

        // Trigger subject failure recommendation warnings
        if ($request->marks_obtained < 50) {
            \App\Models\Notification::create([
                'user_id' => auth()->id(),
                'title' => 'ACADEMIC PERFORMANCE FAILURE',
                'message' => "Student '{$st->name}' scored sub-optimal marks ({$request->marks_obtained}%) in {$request->subject}.",
                'type' => 'dropout_risk'
            ]);

            // Add dynamic student intervention recommendation
            $st->studentInterventions()->create([
                'intervention_type' => 'Counseling',
                'details' => "Recommended for remedial tutoring in {$request->subject} due to scoring under 50% in standard exam.",
                'status' => 'Recommended',
                'cost' => 0.00
            ]);
        }

        return redirect()->back()->with('success', 'Grades logged successfully and synced with recommendation engine!');
    }

    /**
     * Secure Document Locker repository
     */
    public function documents()
    {
        $this->logActivity('view_documents', "Accessed student administrative document locker.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        $user = auth()->user();
        
        $docQuery = \App\Models\StudentDocument::with('student.school');
        $studQuery = Student::enrolled();

        if (in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $docQuery->whereHas('student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
            $studQuery->where('school_id', $user->school_id);
        }

        $documents = $docQuery->orderBy('created_at', 'desc')->get();
        $students = $studQuery->orderBy('name')->get();

        return view('documents.index', compact('documents', 'students'));
    }

    /**
     * Upload student document
     */
    public function uploadDocument(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:2048'
        ]);

        $st = Student::find($request->student_id);
        
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('private/documents');
            
            $this->logActivity('upload_document', "Uploaded secure certificate '{$request->document_type}' for '{$st->name}'.");

            \App\Models\StudentDocument::create([
                'student_id' => $request->student_id,
                'document_type' => $request->document_type,
                'file_path' => $path,
                'status' => 'Pending'
            ]);

            return redirect()->back()->with('success', 'Certificate uploaded to secure student locker and queued for audit review!');
        }

        return redirect()->back()->with('error', 'Failed to upload file.');
    }

    /**
     * Verify student document (Approve / Reject)
     */
    public function verifyDocument(\App\Models\StudentDocument $document, Request $request)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected'
        ]);

        $this->logActivity('verify_document', "Audited student document '{$document->document_type}' status to '{$request->status}'.");

        $document->update([
            'status' => $request->status,
            'notes' => $request->notes ?? "Verified by Principal on " . date('Y-m-d')
        ]);

        return redirect()->back()->with('success', "Document status updated to '{$request->status}' successfully!");
    }

    /**
     * Feedback & Complaint Desk resolution
     */
    public function complaints()
    {
        $this->logActivity('view_complaints', "Accessed complaints desk resolution pipeline.");
        view()->share('headerAlerts', $this->getSystemAlerts());

        $user = auth()->user();
        
        $compQuery = \App\Models\FeedbackComplaint::with('student');
        $studQuery = Student::enrolled();

        if (in_array($user->role, ['school_principal', 'teacher']) && $user->school_id) {
            $compQuery->whereHas('student', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
            $studQuery->where('school_id', $user->school_id);
        }

        $complaints = $compQuery->orderBy('created_at', 'desc')->get();
        $students = $studQuery->orderBy('name')->get();

        return view('complaints.index', compact('complaints', 'students'));
    }

    /**
     * Submit Support ticket complaint
     */
    public function storeComplaint(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'description' => 'required|string',
            'student_id' => 'nullable|exists:students,id'
        ]);

        $this->logActivity('file_complaint', "Filed support ticket '{$request->type}' regarding student logistics.");

        $comp = \App\Models\FeedbackComplaint::create([
            'student_id' => $request->student_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'status' => 'Pending'
        ]);

        // Notify school leads regarding new ticket
        \App\Models\Notification::create([
            'user_id' => auth()->id(),
            'title' => 'NEW COMPLAINT FILED',
            'message' => "Support request filed under '{$request->type}' by parent '{$request->name}'.",
            'type' => 'complaint'
        ]);

        return redirect()->back()->with('success', 'Support request logged successfully! School principal has been notified.');
    }

    /**
     * Respond / Resolve complaint
     */
    public function respondComplaint(\App\Models\FeedbackComplaint $complaint, Request $request)
    {
        $request->validate([
            'response' => 'required|string'
        ]);

        $this->logActivity('resolve_complaint', "Recorded support ticket resolution response.");

        $complaint->update([
            'response' => $request->response,
            'status' => 'Resolved'
        ]);

        return redirect()->back()->with('success', 'Complaint response saved and ticket status updated to Resolved!');
    }

    /**
     * Export dynamic CSV downloads
     */
    public function exportCSV()
    {
        $this->logActivity('export_csv', "Generated and downloaded official filtered dropout CSV spreadsheet.");

        $fileName = 'SmartEdu_Dropout_Report_' . date('Y-m-d_H-i') . '.csv';
        
        $query = Student::with('school');
        $query = $this->applyFilters($query);
        $students = $query->orderBy('name')->get();
        
        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array(
            'Student Name', 
            'Gender', 
            'Age', 
            'Standard/Class', 
            'Caste Category', 
            'School Name', 
            'Area/Village/City', 
            'Parent Income (INR)', 
            'Dropout Status', 
            'Dropout Reason', 
            'Academic Year'
        );

        $callback = function() use($students, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($students as $student) {
                fputcsv($file, array(
                    $student->name,
                    $student->gender,
                    $student->age,
                    'Class ' . $student->standard,
                    $student->caste,
                    $student->school->name,
                    $student->area_village_city,
                    $student->parent_income,
                    $student->status,
                    $student->dropout_reason ?? 'N/A',
                    $student->academic_year
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

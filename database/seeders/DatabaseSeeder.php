<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Student;
use App\Models\Intervention;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 2. Define Indian Names for realistic seeding
        $boyNames = [
            'Aarav', 'Kabir', 'Vivaan', 'Aditya', 'Vihaan', 'Arjun', 'Sai', 'Reyansh', 'Krishna', 'Aryan',
            'Shaurya', 'Atharva', 'Ishan', 'Dhruv', 'Dev', 'Ansh', 'Rohan', 'Karan', 'Ajay', 'Sanjay',
            'Vinod', 'Rajesh', 'Suresh', 'Sunil', 'Ramesh', 'Vijay', 'Deepak', 'Amit', 'Anil', 'Manoj',
            'Rahul', 'Vikram', 'Manish', 'Sandeep', 'Pankaj', 'Abhishek', 'Gaurav', 'Harish', 'Dinesh', 'Sachin'
        ];

        $girlNames = [
            'Saanvi', 'Anya', 'Aadhya', 'Aaradhya', 'Ananya', 'Pari', 'Diya', 'Sneha', 'Avni', 'Priya',
            'Pooja', 'Neha', 'Ritu', 'Anjali', 'Geeta', 'Babita', 'Pinky', 'Kavita', 'Sunita', 'Mamta',
            'Kiran', 'Seema', 'Rekha', 'Jyoti', 'Aarti', 'Divya', 'Nisha', 'Meena', 'Preeti', 'Kajal',
            'Shweta', 'Priyanka', 'Renu', 'Suman', 'Lata', 'Usha', 'Radha', 'Kusum', 'Sarita', 'Asha'
        ];

        $lastNames = [
            'Sharma', 'Verma', 'Gupta', 'Yadav', 'Singh', 'Kumar', 'Patel', 'Joshi', 'Mehta', 'Chaudhary',
            'Rathore', 'Rajput', 'Soni', 'Sen', 'Prajapat', 'Jangid', 'Solanki', 'Gehlot', 'Garg', 'Bansal',
            'Agarwal', 'Jain', 'Khatri', 'Bishnoi', 'Lal', 'Ram', 'Prasad', 'Mina', 'Bairwa', 'Meghwal'
        ];

        // 3. Create 15 diverse schools
        $schoolsData = [
            ['name' => 'Govt Boys Sr. Sec. School, Jaipur City', 'type' => 'Government', 'area_type' => 'Urban', 'district' => 'Jaipur', 'pincode' => '302001'],
            ['name' => 'Govt Girls Sr. Sec. School, Jaipur Rural', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Jaipur', 'pincode' => '303001'],
            ['name' => 'Mahatma Gandhi English Medium School, Udaipur', 'type' => 'Government', 'area_type' => 'Urban', 'district' => 'Udaipur', 'pincode' => '313001'],
            ['name' => 'Govt Senior Secondary School, Kelwara', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Udaipur', 'pincode' => '313315'],
            ['name' => 'Adarsh Vidya Mandir, Jodhpur', 'type' => 'Semi-Government', 'area_type' => 'Urban', 'district' => 'Jodhpur', 'pincode' => '342001'],
            ['name' => 'Govt Girls Middle School, Luni', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Jodhpur', 'pincode' => '342802'],
            ['name' => 'Bright Minds Academy, Kota', 'type' => 'Private', 'area_type' => 'Urban', 'district' => 'Kota', 'pincode' => '324001'],
            ['name' => 'Govt Secondary School, Sangod', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Kota', 'pincode' => '325601'],
            ['name' => 'Govt Senior Secondary School, Pushkar', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Ajmer', 'pincode' => '305022'],
            ['name' => 'Sophia Girls School, Ajmer', 'type' => 'Private', 'area_type' => 'Urban', 'district' => 'Ajmer', 'pincode' => '305001'],
            ['name' => 'Govt Secondary School, Deshnok', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Bikaner', 'pincode' => '334801'],
            ['name' => 'Bikaner Public School, Bikaner', 'type' => 'Private', 'area_type' => 'Urban', 'district' => 'Bikaner', 'pincode' => '334001'],
            ['name' => 'Govt Girls Secondary School, Tonk', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Tonk', 'pincode' => '304001'],
            ['name' => 'Vivekananda Model School, Sikar', 'type' => 'Semi-Government', 'area_type' => 'Urban', 'district' => 'Sikar', 'pincode' => '332001'],
            ['name' => 'Govt Upper Primary School, Laxmangarh', 'type' => 'Government', 'area_type' => 'Rural', 'district' => 'Sikar', 'pincode' => '332311']
        ];

        $schools = [];
        foreach ($schoolsData as $s) {
            $schools[] = School::create($s);
        }

        // Create default users for all 4 roles
        User::create([
            'name' => 'EduKeep Super Administrator',
            'email' => 'admin@edukeep.gov.in',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
        ]);

        User::create([
            'name' => 'Dr. A. K. Verma (Govt Officer)',
            'email' => 'officer@edukeep.gov.in',
            'password' => Hash::make('password123'),
            'role' => 'government_officer',
        ]);

        User::create([
            'name' => 'Smt. Preeti Sharma (Principal)',
            'email' => 'principal@edukeep.gov.in',
            'password' => Hash::make('password123'),
            'role' => 'school_principal',
            'school_id' => $schools[0]->id,
        ]);

        User::create([
            'name' => 'Shri Rajesh Joshi (Class Teacher)',
            'email' => 'teacher@edukeep.gov.in',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
            'school_id' => $schools[0]->id,
        ]);

        // 4. Create Students with Weighted Dropout Rates
        // To showcase proper analytics, we'll write logic that assigns dropout flags based on logical factors:
        // - Government schools have higher dropout rates (~22%) than Private schools (~4%).
        // - Rural schools have higher dropout rates (~25%) than Urban schools (~10%).
        // - Grade/Standard-wise transitions: high spikes in standards 8th, 9th, and 10th.
        // - Caste factor: SC/ST have higher dropout rates due to poverty/migration.
        // - Gender factor: Girls in rural areas drop out more in high school (lack of toilets/sanitation, distance). Boys drop out due to child labor/economic reasons.

        $totalStudents = 1600;
        
        for ($i = 0; $i < $totalStudents; $i++) {
            // Pick a random school
            $school = $schools[array_rand($schools)];
            
            // Random gender
            $genderVal = rand(1, 100);
            if ($genderVal <= 48) {
                $gender = 'Male';
                $name = $boyNames[array_rand($boyNames)] . ' ' . $lastNames[array_rand($lastNames)];
            } elseif ($genderVal <= 96) {
                $gender = 'Female';
                $name = $girlNames[array_rand($girlNames)] . ' ' . $lastNames[array_rand($lastNames)];
            } else {
                $gender = 'Transgender';
                $name = (rand(0, 1) ? $boyNames[array_rand($boyNames)] : $girlNames[array_rand($girlNames)]) . ' ' . $lastNames[array_rand($lastNames)];
            }

            // If it's a boys school, override to Male
            if (str_contains($school->name, 'Boys')) {
                $gender = 'Male';
                $name = $boyNames[array_rand($boyNames)] . ' ' . $lastNames[array_rand($lastNames)];
            }
            // If it's a girls school, override to Female
            if (str_contains($school->name, 'Girls') || str_contains($school->name, 'Sophia')) {
                $gender = 'Female';
                $name = $girlNames[array_rand($girlNames)] . ' ' . $lastNames[array_rand($lastNames)];
            }

            // Caste distribution
            $casteVal = rand(1, 100);
            if ($casteVal <= 25) {
                $caste = 'General';
            } elseif ($casteVal <= 65) {
                $caste = 'OBC';
            } elseif ($casteVal <= 85) {
                $caste = 'SC';
            } else {
                $caste = 'ST';
            }

            // Standard (Grade) 1 to 12
            $standard = rand(1, 12);
            
            // Birth date matching standard (standard + 5 years old, with minor variation)
            $age = $standard + 5 + rand(0, 1);
            $dob = Carbon::now()->subYears($age)->subMonths(rand(0, 11))->subDays(rand(0, 27));

            // Calculate Dropout Probability (base probability)
            $dropoutProb = 5; // Base 5%

            // Factor 1: School Type
            if ($school->type === 'Government') {
                $dropoutProb += 10;
            } elseif ($school->type === 'Semi-Government') {
                $dropoutProb += 5;
            } else {
                $dropoutProb -= 3; // Private has lower dropout
            }

            // Factor 2: Area Type
            if ($school->area_type === 'Rural') {
                $dropoutProb += 12;
            } else {
                $dropoutProb -= 2;
            }

            // Factor 3: Standard (Transition Points)
            if ($standard >= 8 && $standard <= 10) {
                $dropoutProb += 18; // Huge transition spike
            } elseif ($standard > 10) {
                $dropoutProb += 10;
            }

            // Factor 4: Caste
            if ($caste === 'SC' || $caste === 'ST') {
                $dropoutProb += 8;
            }

            // Factor 5: Gender in Rural context
            if ($gender === 'Female' && $school->area_type === 'Rural' && $standard >= 7) {
                $dropoutProb += 15; // Rural teen girls drop out at very high rates
            }

            // Final decision on status
            $status = 'Enrolled';
            $reason = null;
            $dropoutDate = null;

            if (rand(1, 100) <= $dropoutProb) {
                $status = 'Dropped Out';
                $dropoutDate = Carbon::now()->subDays(rand(10, 450))->toDateString();
                
                // Select a realistic reason based on demographics
                $reasons = [];
                
                // Base reasons
                $reasons[] = 'Poverty';
                $reasons[] = 'Lack of Interest';

                // Gender specific reasons
                if ($gender === 'Female') {
                    $reasons[] = 'Marriage';
                    $reasons[] = 'Household Work';
                    if ($school->area_type === 'Rural') {
                        $reasons[] = 'Distance to School';
                        $reasons[] = 'Sanitation/Social';
                    }
                } else {
                    $reasons[] = 'Child Labor';
                    $reasons[] = 'Family Migration';
                }

                // Caste specific reasons (SC/ST more prone to migration/poverty)
                if ($caste === 'SC' || $caste === 'ST') {
                    $reasons[] = 'Poverty';
                    $reasons[] = 'Family Migration';
                    $reasons[] = 'Child Labor';
                }

                // Standard specific (Marriage in higher standards, Distance in middle/higher)
                if ($standard >= 9 && $gender === 'Female') {
                    $reasons[] = 'Marriage';
                }
                if ($standard >= 8 && $gender === 'Male') {
                    $reasons[] = 'Child Labor';
                }

                // Randomly select from available reasons
                $reason = $reasons[array_rand($reasons)];
            }

            // Generate area/village/city based on school area type
            if ($school->area_type === 'Rural') {
                $ruralAreas = ['Kelwara Hamlet', 'Luni Village', 'Sangod Rural', 'Pushkar Hamlet', 'Deshnok Village', 'Laxmangarh Village', 'Lalsot Rural', 'Bassi Hamlet', 'Luni Outer'];
                $areaVillageCity = $ruralAreas[array_rand($ruralAreas)];
            } else {
                $urbanAreas = ['Jaipur City Ward 4', 'Udaipur Sector 11', 'Jodhpur Town Square', 'Kota Center', 'Ajmer Outer', 'Bikaner Colony', 'Sikar Town Ward 12', 'Tonk Bazar Area'];
                $areaVillageCity = $urbanAreas[array_rand($urbanAreas)];
            }

            // Generate parent income based on dropout status (dropped out are poorer, enrolled have wider distribution)
            if ($status === 'Dropped Out') {
                $parentIncome = rand(24000, 85000); // Socio-economically vulnerable range
            } else {
                $parentIncome = rand(85000, 380000); // Regular/higher range
            }

            // Academic Year distribution
            $academicYears = ['2023-2024', '2024-2025', '2025-2026'];
            $academicYear = $academicYears[array_rand($academicYears)];

            Student::create([
                'school_id' => $school->id,
                'name' => $name,
                'gender' => $gender,
                'caste' => $caste,
                'date_of_birth' => $dob->toDateString(),
                'standard' => $standard,
                'status' => $status,
                'dropout_reason' => $reason,
                'dropout_date' => $dropoutDate,
                'area_village_city' => $areaVillageCity,
                'parent_income' => $parentIncome,
                'academic_year' => $academicYear,
            ]);
        }

        // 5. Create default policy interventions
        $interventions = [
            [
                'name' => 'Mid-Day Daily Meals Program',
                'target_type' => 'All',
                'target_value' => 'All Students',
                'type' => 'Meal',
                'description' => 'Providing free nutritious cooked lunch daily to students of primary and upper primary classes in government schools to improve health and attendance.',
                'budget_allocated' => 1500000.00,
                'status' => 'Active',
                'expected_reduction_rate' => 15
            ],
            [
                'name' => 'Sharda Girls Bicycle Distribution Scheme',
                'target_type' => 'Gender',
                'target_value' => 'Female',
                'type' => 'Transport',
                'description' => 'Providing free bicycles to girls in rural areas studying in classes 9 to 12 to reduce dropouts caused by long commute distances and safety concerns.',
                'budget_allocated' => 850000.00,
                'status' => 'Active',
                'expected_reduction_rate' => 25
            ],
            [
                'name' => 'Saraswati SC/ST Financial Scholarship',
                'target_type' => 'Caste',
                'target_value' => 'SC',
                'type' => 'Scholarship',
                'description' => 'Direct annual financial aid of Rs. 6,000 to SC and ST students to cover learning materials and offset poverty-driven dropout rates.',
                'budget_allocated' => 1200000.00,
                'status' => 'Active',
                'expected_reduction_rate' => 30
            ],
            [
                'name' => 'Rajiv Gandhi Rural School Bus Service',
                'target_type' => 'Area',
                'target_value' => 'Rural',
                'type' => 'Transport',
                'description' => 'Shuttle van/bus service linking isolated rural hamlets to secondary schools, ensuring safe, zero-cost transit for both boys and girls.',
                'budget_allocated' => 2000000.00,
                'status' => 'Planned',
                'expected_reduction_rate' => 20
            ],
            [
                'name' => 'Swachh Vidyalaya Girls Sanitation Project',
                'target_type' => 'School',
                'target_value' => 'Govt Girls Sr. Sec. School, Jaipur Rural',
                'type' => 'Infrastructure',
                'description' => 'Construction and maintenance of hygienic, running-water toilets for girls to address sanitation-related absenteeism and dropouts.',
                'budget_allocated' => 450000.00,
                'status' => 'Active',
                'expected_reduction_rate' => 20
            ],
            [
                'name' => 'Udaan Youth Career Counseling & Guidance',
                'target_type' => 'Standard',
                'target_value' => 'Standard 9',
                'type' => 'Counseling',
                'description' => 'Dedicated counseling sessions focusing on students in Class 9 and 10 to highlight educational benefits, counter lack of interest, and build vocational aspirations.',
                'budget_allocated' => 300000.00,
                'status' => 'Active',
                'expected_reduction_rate' => 15
            ]
        ];

        foreach ($interventions as $int) {
            Intervention::create($int);
        }

        // 6. Seed student attendance, marks, and documents
        $students = Student::all();
        $subjects = ['Mathematics', 'Science', 'Social Studies', 'English', 'Regional Language'];

        foreach ($students->take(300) as $student) { // Seed first 300 students to keep seeder very fast and lightweight
            // Seed 10 days of attendance
            $baseAttendance = $student->status === 'Dropped Out' ? 60 : 88;
            for ($d = 1; $d <= 10; $d++) {
                $status = (rand(1, 100) <= $baseAttendance) ? 'Present' : (rand(0, 1) ? 'Absent' : 'Late');
                \App\Models\Attendance::create([
                    'student_id' => $student->id,
                    'date' => Carbon::now()->subDays($d)->toDateString(),
                    'status' => $status,
                    'remarks' => $status === 'Absent' ? 'Family travel constraint' : null
                ]);
            }

            // Seed exam marks for core subjects
            foreach (['Mathematics', 'Science', 'English'] as $sub) {
                $baseMark = $student->status === 'Dropped Out' ? rand(30, 48) : rand(55, 95);
                \App\Models\StudentPerformance::create([
                    'student_id' => $student->id,
                    'subject' => $sub,
                    'marks_obtained' => $baseMark,
                    'max_marks' => 100,
                    'term' => 'Term 1',
                    'academic_year' => '2025-2026'
                ]);
            }

            // Seed some locker documents
            if (rand(1, 10) <= 3) {
                \App\Models\StudentDocument::create([
                    'student_id' => $student->id,
                    'document_type' => rand(0, 1) ? 'Aadhaar' : 'Income Certificate',
                    'file_path' => 'private/documents/seeded_doc_' . $student->id . '.pdf',
                    'status' => rand(0, 1) ? 'Approved' : 'Pending'
                ]);
            }

            // Seed some student specific interventions
            if ($student->status === 'Dropped Out' || rand(1, 10) <= 2) {
                \App\Models\StudentIntervention::create([
                    'student_id' => $student->id,
                    'intervention_type' => rand(0, 1) ? 'Counseling' : 'Scholarship',
                    'details' => 'Fitted with standard government direct support actions.',
                    'status' => rand(0, 1) ? 'Initiated' : 'Completed',
                    'cost' => rand(0, 1) ? 5000.00 : 0.00,
                    'date_implemented' => Carbon::now()->subDays(rand(5, 30))->toDateString()
                ]);
            }
        }

        // 7. Seed feedback complaints
        $complaintData = [
            [
                'name' => 'Ramesh Kumar (Parent)',
                'type' => 'Transport Issue',
                'description' => 'The bus service connecting our hamlet to the secondary school is lagging by 30 mins, causing students to be marked late.',
                'status' => 'Pending'
            ],
            [
                'name' => 'Sita Devi (Parent)',
                'type' => 'Financial Issue',
                'description' => 'Requesting textbook and stationery support grants under regional backward class aid lists.',
                'status' => 'Resolved',
                'response' => 'Scholarship textbook packs provided on 2026-05-10 by principal.'
            ],
            [
                'name' => 'Sunil Verma (Teacher)',
                'type' => 'School Issue',
                'description' => 'Hygienic running-water sanitation locks in Govt School A require immediate repairs before summer break.',
                'status' => 'Pending'
            ]
        ];

        foreach ($complaintData as $c) {
            \App\Models\FeedbackComplaint::create(array_merge($c, [
                'student_id' => Student::first()->id,
                'user_id' => User::where('role', 'teacher')->first()->id
            ]));
        }
    }
}


<?php

namespace App\Services;

use App\Models\Student;

class DropoutPredictionService
{
    /**
     * Run predictions on a single student and output risk analysis.
     *
     * @param Student $student
     * @return array
     */
    public function analyzeStudent(Student $student): array
    {
        if ($student->status === 'Dropped Out') {
            return [
                'score' => 100,
                'level' => 'High Risk',
                'badge_color' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
                'factors' => ['Student is already registered as dropped out.'],
                'recommendations' => ['Urgent re-enrollment support required.']
            ];
        }

        $score = 10; // Baseline start
        $factors = [];
        $recommendations = [];

        // 1. Attendance checks
        $attendance = $student->attendance_rate;
        if ($attendance < 75) {
            $score += 35;
            $factors[] = "Critical Attendance: {$attendance}% is below 75% limit.";
            $recommendations[] = "Implement transportation assistance or dynamic commute counseling.";
        } elseif ($attendance < 85) {
            $score += 15;
            $factors[] = "Sub-optimal Attendance: {$attendance}% is below ideal 85%.";
            $recommendations[] = "Follow up with parents regarding student absenteeism.";
        }

        // 2. Exam marks checks
        $marks = $student->academic_average;
        if ($marks < 50) {
            $score += 30;
            $factors[] = "Failing Exam Average: overall subject performance is {$marks}%.";
            $recommendations[] = "Enroll student in standard subject-specific remedial learning bootcamps.";
        } elseif ($marks < 65) {
            $score += 15;
            $factors[] = "Average Academic Performance: overall score is {$marks}%.";
            $recommendations[] = "Recommend mentorship and guidance sessions to boost learning capacity.";
        }

        // 3. Parent income checks
        $income = (float) $student->parent_income;
        if ($income < 60000) {
            $score += 15;
            $factors[] = "Extreme Socio-Economic Stress: parental income (₹" . number_format($income) . "/yr) is under safety limit.";
            $recommendations[] = "Prioritize allocation of secondary SC/ST or post-matric government scholarships.";
        } elseif ($income < 100000) {
            $score += 5;
            $factors[] = "Low Household Income: parental income is ₹" . number_format($income) . "/yr.";
            $recommendations[] = "Identify local corporate scholarship or textbook support schemes.";
        }

        // 4. Critical transition standards
        if ($student->standard >= 8 && $student->standard <= 10) {
            $score += 10;
            $factors[] = "Vulnerable Class Shift: student is in standard {$student->standard} (critical secondary dropout peak).";
            $recommendations[] = "Register student for career-planning guidance workshops.";
        }

        // 5. Gender & Location intersections
        if ($student->gender === 'Female' && $student->school && $student->school->area_type === 'Rural' && $student->standard >= 7) {
            $score += 10;
            $factors[] = "Rural Girl High School Shift: teen girls face higher travel and sanitation barriers in rural centers.";
            $recommendations[] = "Verify local safe transit accessibility and verify girls-specific sanitation support.";
        }

        // Final score boundaries (caps at 95% if not dropped out)
        $score = min($score, 95);

        if ($score >= 65) {
            $level = 'High Risk';
            $color = 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
        } elseif ($score >= 35) {
            $level = 'Medium Risk';
            $color = 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
        } else {
            $level = 'Low Risk';
            $color = 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
        }

        // Fallbacks if empty
        if (empty($factors)) {
            $factors[] = "All educational, financial, and attendance parameters are stable.";
        }
        if (empty($recommendations)) {
            $recommendations[] = "Maintain standard retention rewards and continuous performance monitoring.";
        }

        return [
            'score' => $score,
            'level' => $level,
            'badge_color' => $color,
            'factors' => $factors,
            'recommendations' => $recommendations
        ];
    }
}

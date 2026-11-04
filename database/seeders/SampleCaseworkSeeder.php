<?php

namespace Database\Seeders;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Enums\CaseStatus;
use App\Enums\ConcernCategory;
use App\Enums\ContactMethod;
use App\Enums\ReferralStatus;
use App\Models\Appointment;
use App\Models\CounselingSession;
use App\Models\FollowUp;
use App\Models\GuidanceCase;
use App\Models\ParentCoordination;
use App\Models\Referral;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only synthetic casework so the desk, cases and follow-ups have
 * something to show. Every note is marked as sample data.
 */
class SampleCaseworkSeeder extends Seeder
{
    public function run(): void
    {
        $counselors = User::whereRelation('role', 'slug', 'guidance_counselor')->orderBy('id')->get();
        $students = Student::orderBy('id')->limit(8)->get();

        if ($counselors->count() < 2 || $students->count() < 8) {
            $this->command?->warn('Skipping sample casework: seed the sample accounts and students first.');

            return;
        }

        [$santos, $ramos] = [$counselors[0], $counselors[1]];
        $signedBy = fn (User $user): array => ['created_by' => $user->id, 'updated_by' => $user->id];

        $referral = Referral::factory()->for($students[0])->create([
            'received_on' => today()->subDays(14),
            'category' => ConcernCategory::Academic,
            'status' => ReferralStatus::UnderReview,
            ...$signedBy($ramos),
        ]);

        $academicCase = GuidanceCase::factory()->for($students[0])->create([
            'referral_id' => $referral->id,
            'category' => ConcernCategory::Academic,
            'status' => CaseStatus::InProgress,
            'opened_on' => today()->subDays(14),
            ...$signedBy($ramos),
        ]);

        CounselingSession::factory()->for($academicCase)->create(['session_at' => today()->subDays(7)->setTime(10, 0), ...$signedBy($ramos)]);
        CounselingSession::factory()->for($academicCase)->create(['session_at' => now()->subHours(2), ...$signedBy($santos)]);
        FollowUp::factory()->for($academicCase)->overdue(3)->create(['purpose' => 'Check-in with adviser', ...$signedBy($ramos)]);
        ParentCoordination::factory()->for($students[0])->create([
            'guidance_case_id' => $academicCase->id,
            'relationship' => 'Mother',
            'method' => ContactMethod::Phone,
            'purpose' => 'Invite to a meeting next week',
            ...$signedBy($ramos),
        ]);

        $attendanceCase = GuidanceCase::factory()->for($students[1])->create(['category' => ConcernCategory::Attendance, ...$signedBy($santos)]);
        FollowUp::factory()->for($attendanceCase)->create(['due_on' => today()->addDays(4), 'purpose' => 'Attendance review', ...$signedBy($santos)]);

        GuidanceCase::factory()->for($students[2])->closed()->create(['category' => ConcernCategory::PersonalSocial, ...$signedBy($ramos)]);

        foreach ([3, 4, 5] as $index) {
            Referral::factory()->for($students[$index])->create(['received_on' => today()->subDays($index - 3), ...$signedBy($index % 2 ? $santos : $ramos)]);
        }

        $todaySlots = [
            [8, 30, AppointmentStatus::Completed, AppointmentPurpose::Counseling, 0, $academicCase->id],
            [10, 0, AppointmentStatus::NoShow, AppointmentPurpose::FollowUp, 1, $attendanceCase->id],
            [13, 0, AppointmentStatus::Scheduled, AppointmentPurpose::CareerPlanning, 6, null],
            [15, 30, AppointmentStatus::Scheduled, AppointmentPurpose::ReferralReview, 3, null],
        ];

        foreach ($todaySlots as [$hour, $minute, $status, $purpose, $studentIndex, $caseId]) {
            Appointment::factory()->for($students[$studentIndex])->create([
                'scheduled_at' => today()->setTime($hour, $minute),
                'status' => $status,
                'purpose' => $purpose,
                'guidance_case_id' => $caseId,
                ...$signedBy($santos),
            ]);
        }

        Appointment::factory()->for($students[7])->create(['scheduled_at' => today()->addDays(2)->setTime(9, 0), ...$signedBy($ramos)]);
    }
}

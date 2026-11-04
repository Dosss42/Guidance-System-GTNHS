<?php

namespace App\Http\Requests\Counselor;

use App\Enums\AppointmentPurpose;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Books or updates an appointment. Blocks a scheduled appointment that
 * overlaps another scheduled appointment for the same student.
 */
class AppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            ? ($this->user()?->can('update', $appointment) ?? false)
            : ($this->user()?->can('create', Appointment::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'guidance_case_id' => ['nullable', 'integer', Rule::exists('guidance_cases', 'id')->where('student_id', $this->integer('student_id'))],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'purpose' => ['required', Rule::enum(AppointmentPurpose::class)],
            'status' => [$this->route('appointment') ? 'required' : 'nullable', Rule::enum(AppointmentStatus::class)],
            'remarks' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $status = $this->input('status', AppointmentStatus::Scheduled->value);

                if ($status !== AppointmentStatus::Scheduled->value) {
                    return;
                }

                $conflict = Appointment::conflictFor(
                    $this->integer('student_id'),
                    Carbon::parse($this->input('scheduled_at')),
                    $this->integer('duration_minutes'),
                    $this->route('appointment')?->getKey(),
                );

                if ($conflict !== null) {
                    $validator->errors()->add(
                        'scheduled_at',
                        "This student already has an appointment at {$conflict->scheduled_at->format('g:i A')} on {$conflict->scheduled_at->format('M j')}.",
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'student',
            'guidance_case_id' => 'guidance case',
            'scheduled_at' => 'date and time',
            'duration_minutes' => 'length',
        ];
    }
}

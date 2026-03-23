<?php

namespace App\Http\Requests;


use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'clock_in_at' => ['nullable', 'date_format:H:i'],
            'clock_out_at' => ['nullable', 'date_format:H:i'],
            'remark' => ['required', 'string'],
            'breaks' => ['array'],
            'breaks.*.break_start_at' => ['nullable', 'date_format:H:i'],
            'breaks.*.break_end_at' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages()
    {
        return [
            'remark.required' => '備考を記入してください'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInAt = $this->input('clock_in_at');
            $clockOutAt = $this->input('clock_out_at');
            $breaks = $this->input('breaks', []);

            if ($clockInAt && $clockOutAt) {
                $clockIn = Carbon::createFromFormat('H:i', $clockInAt);
                $clockOut = Carbon::createFromFormat('H:i', $clockOutAt);

                if ($clockIn->gt($clockOut)) {
                    $validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
                }
            }

            foreach ($breaks as $index => $break) {
                $breakStartAt = $break['break_start_at'] ?? null;
                $breakEndAt = $break['break_end_at'] ?? null;

                if (!$breakStartAt && !$breakEndAt) {
                    continue;
                }

                if ($breakStartAt && $clockInAt) {
                    $breakStart = Carbon::createFromFormat('H:i', $breakStartAt);
                    $clockIn = Carbon::createFromFormat('H:i', $clockInAt);

                    if ($breakStart->lt($clockIn)) {
                        $validator->errors()->add('breaks' . $index . 'break_start_at', '休憩時間が不適切な値です');
                    }
                }

                if ($breakStartAt && $clockOutAt) {
                    $breakStart = Carbon::createFromFormat('H:i', $breakStartAt);
                    $clockOut = Carbon::createFromFormat('H:i', $clockOutAt);

                    if ($breakStart->gt($clockOut)) {
                        $validator->errors()->add('breaks' . $index . 'break_start_at', '休憩時間が不適切な値です');
                    }
                }

                if ($breakEndAt && $clockOutAt) {
                    $breakEnd = Carbon::createFromFormat('H:i', $breakEndAt);
                    $clockOut = Carbon::createFromFormat('H:i', $clockOutAt);

                    if ($breakEnd->gt($clockOut)) {
                        $validator->errors()->add('breaks' . $index . 'break_end_at', '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}

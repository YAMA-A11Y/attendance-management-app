<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
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
            'clock_in_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out_at.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
            'remark.required' => '備考を記入してください',
            'breaks.*.break_start_at.date_format' => '休憩時間が不適切な値です',
            'breaks.*.break_end_at.date_format' => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockInAt = $this->input('clock_in_at');
            $clockOutAt = $this->input('clock_out_at');
            $breaks = $this->input('breaks', []);

            if ($clockInAt && $clockOutAt && $clockInAt >= $clockOutAt) {
                $validator->errors()->add('clock_in_at', '出勤時間もしくは退勤時間が不適切な値です');
            }

            foreach ($breaks as $break) {
                $breakStartAt = $break['break_start_at'] ?? null;
                $breakEndAt = $break['break_end_at'] ?? null;

                if (!$breakStartAt && !$breakEndAt) {
                    continue;
                }

                if ($breakStartAt && $clockInAt && $breakStartAt < $clockInAt) {
                    $validator->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }

                if ($breakStartAt && $clockOutAt && $breakStartAt > $clockOutAt) {
                    $validator->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }

                if ($breakEndAt && $clockOutAt && $breakEndAt > $clockOutAt) {
                    $validator->errors()->add('breaks', '休憩時間もしくは退勤時間が不適切な値です');
                    break;
                }

                if ($breakStartAt && $breakEndAt && $breakStartAt >= $breakEndAt) {
                    $validator->errors()->add('breaks', '休憩時間が不適切な値です');
                    break;
                }
            }
        });
    }
}

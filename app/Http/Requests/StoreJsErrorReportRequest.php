<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreJsErrorReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_note' => ['required', 'string', 'min:8', 'max:1500'],
            'errors' => ['required', 'array', 'min:1', 'max:15'],
            'errors.*.type' => ['required', 'string', 'in:error,promise'],
            'errors.*.time' => ['nullable', 'date'],
            'errors.*.message' => ['required', 'string', 'max:1000'],
            'errors.*.source' => ['nullable', 'string', 'max:2048'],
            'errors.*.line' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'errors.*.column' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'errors.*.stack' => ['nullable', 'string', 'max:50000'],
            'context' => ['nullable', 'array'],
            'context.url' => ['nullable', 'string', 'max:2048'],
            'context.user_agent' => ['nullable', 'string', 'max:1000'],
            'context.language' => ['nullable', 'string', 'max:32'],
            'context.platform' => ['nullable', 'string', 'max:64'],
            'context.breakpoint' => ['nullable', 'string', 'in:base,sm,md,lg,xl,2xl'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_note.required' => 'يرجى كتابة وصف مختصر لما حدث.',
            'user_note.max' => 'وصف المشكلة طويل جدًا.',
            'errors.required' => 'لا توجد أخطاء مرسلة للتبليغ.',
            'errors.array' => 'صيغة الأخطاء المرسلة غير صحيحة.',
            'errors.max' => 'تم تجاوز الحد الأعلى لعدد الأخطاء المرسلة.',
            'errors.*.message.required' => 'رسالة الخطأ التقنية مطلوبة.',
            'errors.*.message.max' => 'رسالة الخطأ التقنية طويلة جدًا.',
            'errors.*.stack.max' => 'تفاصيل التتبع البرمجي طويلة جدًا.',
        ];
    }
}

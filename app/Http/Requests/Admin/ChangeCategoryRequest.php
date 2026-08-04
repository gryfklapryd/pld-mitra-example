<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\DTOs\ActionRequiredData;
use App\Enums\TrackingCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class ChangeCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::enum(TrackingCategory::class)],
            'status_label' => ['required', 'string', 'max:120'],

            'action_instruction' => ['nullable', 'string', 'max:500'],
            'action_due_at' => ['nullable', 'date'],
            'action_url' => ['nullable', 'url', 'max:500'],

            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Aturan dua arah kontrak §4.3 ditegakkan di lapisan validasi, bukan cuma di
     * Action. Ditangkap di sini, operator melihat pesan yang bisa ditindaklanjuti;
     * ditangkap di Action saja, ia muncul sebagai galat 500.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $category = TrackingCategory::tryFrom((string) $this->input('category'));

            if ($category === null) {
                return;
            }

            $instruction = trim((string) $this->input('action_instruction', ''));

            if ($category->requiresAction() && $instruction === '') {
                $validator->errors()->add(
                    'action_instruction',
                    'Kategori ACTION_REQUIRED wajib menyertakan instruksi tindakan — kontrak PLD menolak item yang tidak menyertakannya.',
                );
            }

            if (! $category->requiresAction() && $instruction !== '') {
                $validator->errors()->add(
                    'action_instruction',
                    'Instruksi tindakan hanya boleh untuk kategori ACTION_REQUIRED. Mengirimnya pada kategori lain membuat seluruh item ditolak PLD.',
                );
            }
        });
    }

    public function category(): TrackingCategory
    {
        return TrackingCategory::from((string) $this->validated('category'));
    }

    public function actionRequired(): ?ActionRequiredData
    {
        if (! $this->category()->requiresAction()) {
            return null;
        }

        $dueAt = $this->validated('action_due_at');
        $url = $this->validated('action_url');

        return new ActionRequiredData(
            instruction: (string) $this->validated('action_instruction'),
            dueAt: is_string($dueAt) && $dueAt !== '' ? Carbon::parse($dueAt) : null,
            actionUrl: is_string($url) && $url !== '' ? $url : null,
        );
    }
}

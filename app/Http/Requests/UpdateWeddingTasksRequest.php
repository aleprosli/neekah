<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeddingTasksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * A checklist is worked through in handfuls, so the page sends everything
     * that changed at once rather than a request per tick.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'done' => ['nullable', 'array', 'max:500'],
            'done.*' => ['integer'],
            'undone' => ['nullable', 'array', 'max:500'],
            'undone.*' => ['integer'],
        ];
    }

    /**
     * The task ids to tick, and the ones to untick. Ids that belong to another
     * wedding are dropped by the controller, not trusted from here.
     *
     * @return array{done: array<int, int>, undone: array<int, int>}
     */
    public function changes(): array
    {
        $done = collect($this->validated('done') ?? [])->map(fn ($id): int => (int) $id)->unique();
        $undone = collect($this->validated('undone') ?? [])->map(fn ($id): int => (int) $id)->unique()->diff($done);

        return ['done' => $done->values()->all(), 'undone' => $undone->values()->all()];
    }
}

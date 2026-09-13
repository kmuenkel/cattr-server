<?php

namespace App\Http\Requests\Interval;

use App\Http\Requests\AuthorizesAfterValidation;
use App\Http\Requests\CattrFormRequest;
use App\Models\TimeInterval;

class EditTimeIntervalRequest extends CattrFormRequest
{
    use AuthorizesAfterValidation;

    public function authorizeValidated(): bool
    {
        return $this->user()->can('update', TimeInterval::find(request('id')));
    }

    public function _rules(): array
    {
        return [
            'id' => 'required|int|exists:time_intervals,id',
            'start_at' => 'date',
            'end_at' => 'date',
            'task_id' => 'integer|exists:tasks,id',
            'user_id' => 'integer|exists:users,id',
            'mouse_fill' => 'integer',
            'keyboard_fill' => 'integer',
            'activity_fill' => 'integer',
            'has_screenshot' => 'boolean'
        ];
    }
}

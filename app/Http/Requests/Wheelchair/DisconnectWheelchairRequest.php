<?php

namespace App\Http\Requests\Wheelchair;

use App\Models\Wheelchair;
use Illuminate\Foundation\Http\FormRequest;

class DisconnectWheelchairRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $wheelchair = $this->route('wheelchair') ?? Wheelchair::find($this->route('id'));
        return $wheelchair && $this->user()->can('disconnect', $wheelchair);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [];
    }
}

<?php

namespace App\Http\Requests\Map;

use Illuminate\Foundation\Http\FormRequest;

class StoreMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'map_file'          => 'required|file|max:20480', // removed mimes restrict as pgm might not map to common mime types
            'yaml_file'         => 'required_without:width,height,resolution,origin|file|max:5120',
            'width'             => 'required_without:yaml_file|numeric',
            'height'            => 'required_without:yaml_file|numeric',
            'resolution'        => 'required_without:yaml_file|numeric',
            'origin'            => 'required_without:yaml_file|array',
            'mode'              => 'nullable|string',
            'negate'            => 'nullable|numeric',
            'occupied_thresh'   => 'nullable|numeric',
            'free_thresh'       => 'nullable|numeric',
        ];
    }
}
<?php

namespace App\Http\Requests;

use App\Models\VehicleUsage;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateVehicleUsageRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('vehicle_usage_edit');
    }

    public function rules()
    {
        return [
            'driver_id' => [
                'required',
                'integer',
            ],
            'vehicle_item_id' => [
                'required',
                'integer',
            ],
            'start_date' => [
                'required',
                'date_format:' . config('panel.date_format'),
            ],
            'end_date' => [
                'date_format:' . config('panel.date_format'),
                'nullable',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataPointRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'campaignId' => 'required|exists:campaigns,id',
            'metricId' => 'required|exists:environmental_metrics,id',
            'value' => 'required|numeric',
            'notes' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'deviceModel' => 'nullable|string|max:100',
            'sensorType' => 'nullable|string|max:50',
            'calibrationDate' => 'nullable|date|before_or_equal:today',
            'status' => 'nullable|in:draft,pending,approved,rejected',
            'reviewNotes' => 'nullable|string|max:1000',
            'latitude' => 'nullable|numeric|min:-90|max:90',
            'longitude' => 'nullable|numeric|min:-180|max:180',
            'manualLatitude' => 'nullable|numeric|min:-90|max:90',
            'manualLongitude' => 'nullable|numeric|min:-180|max:180',
            'accuracy' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'campaignId.required' => 'Please select a campaign.',
            'campaignId.exists' => 'The selected campaign does not exist.',
            'metricId.required' => 'Please select an environmental metric.',
            'metricId.exists' => 'The selected metric does not exist.',
            'value.required' => 'Measurement value is required.',
            'value.numeric' => 'Measurement value must be a number.',
            'photo.image' => 'Photo must be an image file.',
            'photo.max' => 'Photo size cannot exceed 5MB.',
            'calibrationDate.before_or_equal' => 'Calibration date cannot be in the future.',
            'latitude.min' => 'Latitude must be between -90 and 90.',
            'latitude.max' => 'Latitude must be between -90 and 90.',
            'longitude.min' => 'Longitude must be between -180 and 180.',
            'longitude.max' => 'Longitude must be between -180 and 180.',
        ];
    }

    /**
     * Custom validation after standard rules pass
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Require coordinates for new submissions (not edits)
            if (! $this->has('dataPointId')) {
                if (! $this->filled('latitude') && ! $this->filled('manualLatitude')) {
                    $validator->errors()->add(
                        'location',
                        'Either capture GPS or enter coordinates manually'
                    );
                }
            }
        });
    }
}

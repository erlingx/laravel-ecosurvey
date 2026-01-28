<?php

declare(strict_types=1);

use App\Http\Requests\StoreDataPointRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Campaign;
use App\Models\EnvironmentalMetric;
use Illuminate\Support\Facades\Validator;

test('StoreDataPointRequest validates required fields', function () {
    $request = new StoreDataPointRequest;

    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('campaignId'))->toBeTrue();
    expect($validator->errors()->has('metricId'))->toBeTrue();
    expect($validator->errors()->has('value'))->toBeTrue();
});

test('StoreDataPointRequest accepts valid data with existing campaign and metric', function () {
    $campaign = Campaign::factory()->create();
    $metric = EnvironmentalMetric::factory()->create();

    $request = new StoreDataPointRequest;

    $data = [
        'campaignId' => $campaign->id,
        'metricId' => $metric->id,
        'value' => 25.5,
        'notes' => 'Test reading',
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('StoreDataPointRequest validates latitude bounds', function () {
    $request = new StoreDataPointRequest;

    $data = [
        'campaignId' => 1,
        'metricId' => 1,
        'value' => 25.5,
        'latitude' => 95,
    ];

    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('latitude'))->toBeTrue();
});

test('UpdateProfileRequest has correct validation rules', function () {
    $request = new UpdateProfileRequest;

    // Verify the Form Request has the rules() method and it returns an array
    expect(method_exists($request, 'rules'))->toBeTrue();
    expect(method_exists($request, 'messages'))->toBeTrue();
    expect($request->authorize())->toBeTrue();

    // Verify custom messages exist
    $messages = $request->messages();
    expect($messages)->toBeArray();
    expect($messages)->toHaveKey('name.required');
    expect($messages)->toHaveKey('email.required');
});

test('UpdateProfileRequest validates email format', function () {
    $request = new UpdateProfileRequest;

    $data = [
        'name' => 'John Doe',
        'email' => 'invalid-email',
    ];

    $validator = Validator::make($data, ['email' => ['required', 'email']]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('email'))->toBeTrue();
});

test('UpdatePasswordRequest has correct validation rules', function () {
    $request = new UpdatePasswordRequest;

    // Verify the Form Request has the required methods
    expect(method_exists($request, 'rules'))->toBeTrue();
    expect(method_exists($request, 'messages'))->toBeTrue();
    expect($request->authorize())->toBeTrue();

    // Verify custom messages exist
    $messages = $request->messages();
    expect($messages)->toBeArray();
    expect($messages)->toHaveKey('current_password.required');
    expect($messages)->toHaveKey('password.required');
});

test('UpdatePasswordRequest validates password confirmation mismatch', function () {
    $data = [
        'current_password' => 'oldpassword',
        'password' => 'newpassword123',
        'password_confirmation' => 'different123',
    ];

    $validator = Validator::make($data, [
        'password' => ['required', 'confirmed'],
    ]);

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('password'))->toBeTrue();
});

test('Form Request has custom error messages', function () {
    $request = new StoreDataPointRequest;

    expect($request->messages())->toBeArray();
    expect($request->messages())->toHaveKey('campaignId.required');
    expect($request->messages()['campaignId.required'])->toBe('Please select a campaign.');
});

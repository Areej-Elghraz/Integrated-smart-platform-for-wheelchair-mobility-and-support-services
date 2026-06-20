<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Profile\UpdateDataRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class UpdateDataController extends ApiController
{
    public function __invoke(UpdateDataRequest $request)
    {
        $user = User::findOrFail(auth('sanctum')->id());

        if ($request->hasFile('image')) {
            if ($user->image && Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                Storage::disk('public')->delete($user->getRawOriginal('image'));
            }
            $path = $request->file('image')->store('avatars', 'public');
        }

        $data = [
            'name'                 => $request->name ?? $user->name,
            'username'             => $request->username ?? $user->username,
            'phone'                => $request->phone ?? $user->phone,
            'gender'               => $request->gender ?? $user->gender,
            'birth_date'           => $request->birth_date ?? $user->birth_date,
            'weight'               => $request->weight ?? $user->weight,
            'height'               => $request->height ?? $user->height,
            'logout_other_devices' => $request->logout_other_devices ?? false,
        ];
        if (isset($path)) {
            $data['image'] = $path;
        }

        $user->update($data);

        $user->save();

        if ($request->has('medical_condition_ids')) {
            $user->medicalConditions()->sync($request->medical_condition_ids);
        }

        $user->refresh()->load([
            'medicalConditions',
            'friends',
            'organizations',
            'wheelchairs'
        ]);

        return $this->successResponse(message: __('auth.profile_data_changed_success'), parameters: ['data' => $user]);
    }
}
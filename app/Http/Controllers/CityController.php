<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Support\Facades\Cache;

class CityController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cities = Cache::remember('cities_all', 3600, function () {
            return City::with('country')->get();
        });
        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.city.plural')]),
            status: 200,
            parameters: $cities->toArray()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCityRequest $request)
    {
        $city = City::create($request->validated());
        Cache::forget('cities_all');
        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.city.singular')]),
            status: 201,
            parameters: $city->toArray()
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        $cityData = Cache::remember("city_{$city->id}", 3600, function () use ($city) {
            return $city->toArray();
        });
        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.city.singular')]),
            status: 200,
            parameters: $cityData
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, City $city)
    {
        $city->update($request->validated());
        Cache::forget('cities_all');
        Cache::forget("city_{$city->id}");
        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.city.singular')]),
            status: 200,
            parameters: $city->toArray()
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        $city->delete();
        Cache::forget('cities_all');
        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.city.singular')]),
            status: 200
        );
    }
}

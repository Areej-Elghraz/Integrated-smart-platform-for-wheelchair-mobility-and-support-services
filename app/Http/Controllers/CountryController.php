<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CountryController extends ApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Cache::remember('countries_all', 3600, function () {
            return Country::all();
        });
        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.country.plural')]),
            status: 200,
            parameters: $countries->toArray()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $country = Country::create($request->validated());
        Cache::forget('countries_all');
        return $this->successResponse(
            message: __('messages.actions.created_success', ['resource' => __('messages.resources.country.singular')]),
            status: 201,
            parameters: $country->toArray()
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        $countryData = Cache::remember("country_{$country->id}", 3600, function () use ($country) {
            return $country->toArray();
        });
        return $this->successResponse(
            message: __('messages.actions.retrieved_success', ['resource' => __('messages.resources.country.singular')]),
            status: 200,
            parameters: $countryData
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, Country $country)
    {
        $country->update($request->validated());
        Cache::forget('countries_all');
        Cache::forget("country_{$country->id}");
        return $this->successResponse(
            message: __('messages.actions.updated_success', ['resource' => __('messages.resources.country.singular')]),
            status: 200,
            parameters: $country->toArray()
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();
        Cache::forget('countries_all');
        Cache::forget("country_{$country->id}");
        return $this->successResponse(
            message: __('messages.actions.deleted_success', ['resource' => __('messages.resources.country.singular')]),
            status: 200
        );
    }
}

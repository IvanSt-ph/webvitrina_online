<?php

namespace App\Http\Middleware;

use App\Models\City;
use App\Models\Country;
use Closure;
use Illuminate\Http\Request;

class RememberLocation
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('clear_location')) {
            session()->forget(['country_id', 'city_id']);
            $request->query->remove('country_id');
            $request->query->remove('city_id');
            $request->request->remove('country_id');
            $request->request->remove('city_id');

            return $next($request);
        }

        $this->syncPositiveInteger($request, 'country_id');
        $this->syncPositiveInteger($request, 'city_id');
        $this->validateStoredLocation($request);

        return $next($request);
    }

    private function syncPositiveInteger(Request $request, string $key): void
    {
        if (! $request->has($key)) {
            if (session()->has($key)) {
                $value = (int) session($key);
                $request->query->set($key, $value);
                $request->request->set($key, $value);
            }

            return;
        }

        $value = filter_var($request->input($key), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($value === false) {
            session()->forget($key);
            $request->query->remove($key);
            $request->request->remove($key);

            return;
        }

        session([$key => $value]);
        $request->query->set($key, $value);
        $request->request->set($key, $value);
    }

    private function validateStoredLocation(Request $request): void
    {
        $countryId = $request->input('country_id');
        $cityId = $request->input('city_id');

        if ($countryId && ! Country::whereKey((int) $countryId)->exists()) {
            session()->forget(['country_id', 'city_id']);
            $request->query->remove('country_id');
            $request->query->remove('city_id');
            $request->request->remove('country_id');
            $request->request->remove('city_id');

            return;
        }

        if (! $cityId) {
            return;
        }

        $city = City::query()
            ->select('id', 'country_id')
            ->find((int) $cityId);

        if (! $city || ($countryId && (int) $city->country_id !== (int) $countryId)) {
            session()->forget('city_id');
            $request->query->remove('city_id');
            $request->request->remove('city_id');
        }
    }
}

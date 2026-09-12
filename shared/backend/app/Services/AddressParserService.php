<?php

namespace App\Services;

use App\Models\CoverageArea;
use App\Models\Hub;
use Illuminate\Support\Str;

class AddressParserService
{
    public function parse(string $address): Hub
    {
        $normalized = Str::lower($address);
        $area = CoverageArea::with('hub')->get()->sortByDesc(function (CoverageArea $area) use ($normalized) {
            $province = Str::lower($area->province);
            $city = Str::lower($area->city_municipality);
            return (Str::contains($normalized, $city) ? 2 : 0) + (Str::contains($normalized, $province) ? 1 : 0);
        })->first(function (CoverageArea $area) use ($normalized) {
            return Str::contains($normalized, Str::lower($area->province)) || Str::contains($normalized, Str::lower($area->city_municipality));
        });

        return $area?->hub ?? Hub::where('type', 'regional')->orderBy('id')->firstOrFail();
    }
}
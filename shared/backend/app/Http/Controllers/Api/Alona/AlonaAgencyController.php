<?php

namespace App\Http\Controllers\Api\Alona;

use App\Http\Controllers\Controller;
use App\Models\AlonaCourierAgency;
use Illuminate\Http\Request;

class AlonaAgencyController extends Controller
{
    use RequiresAlonaAdmin;

    public function index(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return AlonaCourierAgency::withCount('riders')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $this->requireAlonaAdmin($request);
        return response()->json(AlonaCourierAgency::create($request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'unique:alona_courier_agencies,code'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'api_base_url' => ['nullable', 'url'],
            'api_credentials' => ['prohibited'],
        ])), 201);
    }

    public function update(Request $request, AlonaCourierAgency $agency)
    {
        $this->requireAlonaAdmin($request);
        $agency->update($request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'api_base_url' => ['nullable', 'url'],
            'is_active' => ['sometimes', 'boolean'],
        ]));
        return $agency->fresh()->loadCount('riders');
    }

    public function destroy(Request $request, AlonaCourierAgency $agency)
    {
        $this->requireAlonaAdmin($request);
        abort_if($agency->riders()->exists(), 422, 'Agency still has assigned riders.');
        $agency->delete();
        return response()->noContent();
    }
}

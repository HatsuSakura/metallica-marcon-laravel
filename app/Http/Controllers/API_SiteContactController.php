<?php

namespace App\Http\Controllers;

use App\Enums\InternalContactRole;
use App\Models\InternalContact;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class API_SiteContactController extends Controller
{
    public function store(Request $request, Site $site)
    {
        Gate::authorize('update', $site);
        $contact = $site->internalContacts()->create($this->validated($request));

        return response()->json(['contact' => $contact], 201);
    }

    public function update(Request $request, Site $site, InternalContact $contact)
    {
        Gate::authorize('update', $site);
        abort_unless((int) $contact->site_id === (int) $site->id, 404);
        $contact->update($this->validated($request));

        return response()->json(['contact' => $contact->fresh()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'surname' => ['nullable', 'string', 'max:191'],
            'role' => ['required', Rule::enum(InternalContactRole::class)],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9 ()\.\-\/]+$/'],
            'mobile' => ['nullable', 'string', 'max:50', 'regex:/^\+?[0-9 ()\.\-\/]+$/'],
            'email' => ['nullable', 'email', 'max:191'],
        ]);
    }
}

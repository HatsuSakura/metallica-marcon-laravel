<?php

namespace App\Http\Controllers;

use App\Models\CerCode;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CerCodeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive,all',
        ]);
        $search = trim($filters['search'] ?? '');
        $status = $filters['status'] ?? 'active';
        $codes = CerCode::query()
            ->when($status !== 'all', fn ($query) => $query->where('is_active', $status === 'active'))
            ->when($search !== '', function ($query) use ($search) {
                $plain = preg_replace('/[\s*]+/u', '', $search);
                $query->where(function ($query) use ($search, $plain) {
                    $query->where('description', 'like', '%'.addcslashes($search, '%_\\').'%')
                        ->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '*', '') LIKE ?", ['%'.addcslashes($plain, '%_\\').'%']);
                });
            })
            ->orderBy('code')->orderBy('id')->paginate(25)->withQueryString();

        return inertia('CerCode/Index', ['codes' => $codes, 'filters' => ['search' => $search, 'status' => $status]]);
    }

    public function create()
    {
        return inertia('CerCode/Form');
    }

    public function store(Request $request)
    {
        $code = CerCode::create($this->validated($request));

        return to_route('cer-code.index')->with('success', "Codice CER {$code->code} creato.");
    }

    public function edit(CerCode $cerCode)
    {
        return inertia('CerCode/Form', ['cerCode' => $cerCode]);
    }

    public function update(Request $request, CerCode $cerCode)
    {
        $cerCode->update($this->validated($request, $cerCode));

        return to_route('cer-code.index')->with('success', "Codice CER {$cerCode->code} aggiornato.");
    }

    private function validated(Request $request, ?CerCode $current = null): array
    {
        $request->merge([
            'code' => preg_replace('/[\s*]+/u', '', (string) $request->input('code')),
            'description' => trim((string) $request->input('description')),
        ]);
        $data = $request->validate([
            'code' => ['required', 'regex:/^[0-9]{6}$/'],
            'description' => 'required|string|max:255',
            'is_dangerous' => 'required|boolean',
            'is_active' => 'required|boolean',
        ], ['code.regex' => 'Inserisci un codice CER di 6 cifre.']);
        // Existing imports may use spaces and/or an asterisk: compare their canonical form.
        $duplicate = CerCode::query()->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->whereRaw("REPLACE(REPLACE(code, ' ', ''), '*', '') = ?", [$data['code']])->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'Questo codice CER è già presente, anche tra quelli disattivati.']);
        }

        return $data;
    }
}

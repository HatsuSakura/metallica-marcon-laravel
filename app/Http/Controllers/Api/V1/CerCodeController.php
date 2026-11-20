<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CerCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CerCodeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:200',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $search = trim($filters['search'] ?? '');
        $plain = preg_replace('/[\s*]+/u', '', $search);

        return $this->query()->when($search !== '', function ($query) use ($search, $plain) {
            $query->where(function ($query) use ($search, $plain) {
                $query->where('description', 'like', '%'.addcslashes($search, '%_\\').'%')
                    ->orWhereRaw("REPLACE(REPLACE(code, ' ', ''), '*', '') LIKE ?", ['%'.addcslashes($plain, '%_\\').'%']);
            });
        })->orderBy('code')->orderBy('id')->paginate($filters['per_page'] ?? 25)->withQueryString();
    }

    public function show(int $cerCode)
    {
        return response()->json(['data' => $this->query()->findOrFail($cerCode)]);
    }

    private function query(): Builder
    {
        return CerCode::query()->whereNull('deleted_at')->select('id', 'code', 'description', 'is_dangerous', 'is_active');
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferenceController extends Controller
{
    public const SOURCES = [
        'customers' => ['customers', ['id', 'company_name']],
        'sites' => ['sites', ['id', 'customer_id', 'name', 'address']],
        'cer-codes' => ['cer_codes', ['id', 'code', 'description', 'is_dangerous']],
        'holders' => ['holders', ['id', 'name', 'is_custom']],
        'vehicles' => ['vehicles', ['id', 'name', 'type', 'has_trailer', 'load_capacity']],
        'trailers' => ['trailers', ['id', 'name', 'load_capacity']],
        'cargos' => ['cargos', ['id', 'name', 'is_long']],
        'warehouses' => ['warehouses', ['id', 'name']],
        'drivers' => ['users', ['id', 'name', 'surname']],
        'technical-actions' => ['journey_stop_actions', ['id', 'label']],
    ];

    public function index(Request $request, string $resource)
    {
        abort_unless(isset(self::SOURCES[$resource]), 404);
        [$table, $fields] = self::SOURCES[$resource];
        $f = $request->validate(['search' => 'nullable|string|max:200', 'customer_id' => 'nullable|integer', 'per_page' => 'nullable|integer|min:1|max:100']);
        $query = DB::table($table)->select($fields);
        if (in_array($table, ['customers', 'sites', 'cer_codes'], true)) {
            $query->whereNull('deleted_at');
        }
        if ($resource === 'drivers') {
            $query->where('role', 'driver');
        }
        if ($resource === 'technical-actions') {
            $query->where('is_active', true);
        }
        if ($resource === 'sites' && isset($f['customer_id'])) {
            $query->where('customer_id', $f['customer_id']);
        }
        if (! empty($f['search'])) {
            $searchFields = array_values(array_intersect($fields, ['company_name', 'name', 'code', 'description', 'label', 'surname']));
            $query->where(function ($q) use ($searchFields, $f) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', '%'.$f['search'].'%');
                }
            });
        }

        return $query->orderBy('id')->paginate($f['per_page'] ?? 25);
    }
}

<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Str;

/** Same name-only, punctuation-insensitive matching used by the journey picker. */
class CustomerNameSearch
{
    public function matchingIds(string $search): array
    {
        $terms = array_values(array_filter(array_map($this->normalize(...), preg_split('/\s+/u', trim($search))), fn ($term) => $term !== ''));
        $ids = [];
        // Portable across MySQL and SQLite; only names/IDs are read, never order payloads.
        foreach (Customer::query()->select(['id', 'company_name'])->cursor() as $customer) {
            $name = $this->normalize($customer->company_name ?? '');
            if (collect($terms)->every(fn ($term) => str_contains($name, $term))) {
                $ids[] = $customer->id;
            }
        }

        return $ids;
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^\p{L}\p{N}]/u', '', mb_strtolower(Str::ascii($value)));
    }
}

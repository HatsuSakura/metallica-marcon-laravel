<?php

namespace App\Models;

use App\Enums\CustomerJobType;
use App\Models\Concerns\HasDomainAudit;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Customer extends Model implements AuditableContract
{
    use SoftDeletes, HasDomainAudit;

    private static ?bool $hasCanonicalFulltextIndex = null;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'is_occasional_customer',
        'company_name',
        'vat_number',
        'tax_code',
        'seller_id',
        'legal_address',
        'sdi_code',
        'business_type',
        'sales_email',
        'administrative_email',
        'certified_email',
        'notes',
    ];

    protected $auditInclude = [
        'is_occasional_customer',
        'company_name',
        'vat_number',
        'tax_code',
        'seller_id',
        'legal_address',
        'sdi_code',
        'business_type',
        'sales_email',
        'administrative_email',
        'certified_email',
        'notes',
    ];

    protected $casts = [
        'is_occasional_customer' => 'boolean',
        'business_type' => CustomerJobType::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Customer $customer) {
            if ($customer->isForceDeleting()) {
                $customer->sites()->withTrashed()->get()->each->forceDelete();
                return;
            }

            $customer->sites()
                ->whereNull('deleted_at')
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        static::restoring(function (Customer $customer) {
            $customer->sites()
                ->onlyTrashed()
                ->update([
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
        });
    }

    public function seller(): BelongsTo{
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function sites(): HasMany{
        return $this->hasMany(Site::class, 'customer_id');
    }

    public function orders(): HasMany{
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function mainSite(): HasOne{
        return $this->hasOne(Site::class, 'customer_id') ->where('is_main', true); 
    }

    public function scopeAlphabetic(Builder $query): Builder{
        return $query->orderByRaw(
            "TRIM(REPLACE(REPLACE(REPLACE(company_name, '\t', ''), '\r', ''), '\n', '')) asc"
        );
    }

    public function scopeClienteContinuativo(Builder $query): Builder {
        return $query->whereNull('sold_at');
    }

    public function scopeCustomerOccasionale(Builder $query): Builder {
        return $query->whereNotNull('sold_at');
    }

    public function scopeFilter(Builder $query, array $filters): Builder{
        return $query
        ->when(
            isset($filters['occasionale']) && isset($filters['continuativo']),
            function ($query) use ($filters) {
                // If both occasionale and continuativo are true, return all customers
                if ($filters['occasionale'] && $filters['continuativo']) {
                    return $query;
                }

                // If occasionale is true and continuativo is false, return only "occasionale" customers
                if ($filters['occasionale'] && !$filters['continuativo']) {
                    return $query->where('is_occasional_customer', '1');
                }

                // If continuativo is true and occasionale is false, return only "continuativo" customers
                if (!$filters['occasionale'] && $filters['continuativo']) {
                    return $query->where('is_occasional_customer', '0');
                }

                // If both occasionale and continuativo are false, return an empty set
                return $query->whereRaw('1 = 0'); // This will return an empty result set
            }
        )
        ->when(
            $filters['deleted'] ?? false,
            fn ($query, $value) => $query->withTrashed()
        )
        ->when(
            $filters['chiave'] ?? false,
            function ($query, $value) {
                $term = trim((string) $value);
                if ($term === '') {
                    return $query;
                }

                // FULLTEXT minimum token length is 3 (InnoDB default).
                // If any token is shorter, the engine silently ignores it.
                // Fall back to LIKE for terms with short tokens (e.g. "AC s.p.a.").
                $tokens = collect(preg_split('/\s+/', $term))->filter()->values();
                $fulltextViable = self::hasCanonicalFulltextIndex()
                    && $tokens->every(fn (string $t) => mb_strlen($t) >= 3);

                if ($fulltextViable) {
                    $booleanTerm = $tokens
                        ->map(fn (string $token) => "{$token}*")
                        ->implode(' ');

                    return $query->whereRaw(
                        "MATCH(
                            company_name, vat_number, tax_code, legal_address, sales_email, administrative_email, certified_email
                        )
                        AGAINST(? IN BOOLEAN MODE)",
                        [$booleanTerm]
                    );
                }

                $likeTerm = '%' . $term . '%';

                return $query->where(function (Builder $searchQuery) use ($likeTerm) {
                    $searchQuery
                        ->orWhere('company_name', 'like', $likeTerm)
                        ->orWhere('vat_number', 'like', $likeTerm)
                        ->orWhere('tax_code', 'like', $likeTerm)
                        ->orWhere('legal_address', 'like', $likeTerm)
                        ->orWhere('sales_email', 'like', $likeTerm)
                        ->orWhere('administrative_email', 'like', $likeTerm)
                        ->orWhere('certified_email', 'like', $likeTerm);
                });
            }
        )
        /*
        ->when(
            $filters['by'] ?? false,
            fn ($query, $value) => 
            !in_array($value, $this->sortable) ? $query : // if not in array sortable, return just $query
            $query->orderBy($value, $filters['order'] ?? 'desc') // otherwise order query by sortable fields
        )*/
        ;
    }
    
    public function setSalesEmailAttribute($salesEmail): void
    {
        $this->attributes['sales_email'] = $this->normalizeLowercaseEmail($salesEmail);
    }

    public function setAdministrativeEmailAttribute($administrativeEmail): void
    {
        $this->attributes['administrative_email'] = $this->normalizeLowercaseEmail($administrativeEmail);
    }

    public function setCertifiedEmailAttribute($certifiedEmail): void
    {
        $this->attributes['certified_email'] = $this->normalizeLowercaseEmail($certifiedEmail);
    }

    public function setCompanyNameAttribute($value): void
    {
        $this->attributes['company_name'] = $this->normalizeTrimmedString($value);
    }

    public function setVatNumberAttribute($value): void
    {
        $this->attributes['vat_number'] = $this->normalizeTrimmedString($value);
    }

    public function setTaxCodeAttribute($value): void
    {
        $this->attributes['tax_code'] = $this->normalizeTrimmedString($value);
    }

    public function setLegalAddressAttribute($value): void
    {
        $this->attributes['legal_address'] = $this->normalizeTrimmedString($value);
    }

    public function setSdiCodeAttribute($value): void
    {
        $this->attributes['sdi_code'] = $this->normalizeTrimmedString($value);
    }

    public function setNotesAttribute($value): void
    {
        $this->attributes['notes'] = $this->normalizeTrimmedString($value);
    }

    public function setEmailCommercialeAttribute($value): void
    {
        $this->setSalesEmailAttribute($value);
    }

    public function setEmailAmministrativaAttribute($value): void
    {
        $this->setAdministrativeEmailAttribute($value);
    }

    public function setPecAttribute($value): void
    {
        $this->setCertifiedEmailAttribute($value);
    }

    private function normalizeTrimmedString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = preg_replace('/^\s+|\s+$/u', '', (string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeLowercaseEmail($value): ?string
    {
        $normalized = $this->normalizeTrimmedString($value);

        return $normalized === null ? null : strtolower($normalized);
    }

    private static function hasCanonicalFulltextIndex(): bool
    {
        if (self::$hasCanonicalFulltextIndex !== null) {
            return self::$hasCanonicalFulltextIndex;
        }

        self::$hasCanonicalFulltextIndex = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'customers')
            ->where('index_name', 'customers_fulltext_index')
            ->exists();

        return self::$hasCanonicalFulltextIndex;
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customers';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;
}

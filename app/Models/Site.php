<?php

namespace App\Models;

use App\Enums\SiteTipologia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'customer_id',
        'name',
        'is_main',
        'site_type',
        'address',
        'latitude',
        'longitude',
        'calculated_risk_factor',
        'days_until_next_withdraw',
        'has_muletto',
        'has_electric_pallet_truck',
        'has_manual_pallet_truck',
        'other_machines',
        'has_adr_consultant'
    ];

    /*
    Accessing `site_type` returns a SiteTipologia enum instance.
    Example: if ($site->site_type === SiteTipologia::FULLY_OPERATIVE)
    */
    protected $casts = [
        'site_type' => SiteTipologia::class,
    ];


    /*
    Default site type for new records.
    */
    protected $attributes = [
        'site_type' => SiteTipologia::FULLY_OPERATIVE,
    ];

    public function scopeFilter(Builder $query, array $filters): Builder{
        return $query
        // Group the rischio conditions with parentheses
        ->when(
            $filters['rischioBasso'] || $filters['rischioMedio'] || $filters['rischioAlto'] || $filters['rischioCritico'],
            function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->when($filters['rischioBasso'] ?? false, fn ($q) => $q->orWhere('calculated_risk_factor', '<', 0.50))
                          ->when($filters['rischioMedio'] ?? false, fn ($q) => $q->orWhereBetween('calculated_risk_factor', [0.50, 0.75]))
                          ->when($filters['rischioAlto'] ?? false, fn ($q) => $q->orWhereBetween('calculated_risk_factor', [0.75, 0.85]))
                          ->when($filters['rischioCritico'] ?? false, fn ($q) => $q->orWhere('calculated_risk_factor', '>', 0.85));
                });
            }
        ) 
        ->when(
            isset($filters['occasionale']) && isset($filters['continuativo']),
            function ($query) use ($filters) {
                // If both occasionale and continuativo are true, return all customers
                if ($filters['occasionale'] && $filters['continuativo']) {
                    return $query;
                }

                // Apply `occasionale` or `continuativo` filters on the `customer` relationship
                if ($filters['occasionale'] && !$filters['continuativo']) {
                    return $query->whereHas('customer', function ($q) {
                        $q->where('is_occasional_customer', '1');
                    });
                }

                if (!$filters['occasionale'] && $filters['continuativo']) {
                    return $query->whereHas('customer', function ($q) {
                        $q->where('is_occasional_customer', '0');
                    });
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
            fn ($query, $value) => $query->whereHas('customer', function ($q) use ($value) {
                $q->where('company_name', 'LIKE', "%{$value}%");
            })
        )
        /*
        Optional full-text search example:
        MATCH(company_name, vat_number, tax_code, legal_address, sales_email, administrative_email, certified_email)
        */
        ;
    }
    
    public function customer(): BelongsTo{
        return $this->belongsTo(Customer::class, 'customer_id')->with('seller');
    }

    public function orders(): HasMany{
        return $this->hasMany(Order::class, 'site_id')->orderBy('requested_at', 'desc');
    }

    public function withdraws(): HasMany{
        return $this->hasMany(Withdraw::class, 'site_id')->orderBy('withdrawn_at', 'desc');
    }

    public function timetable(): HasOne{
        return $this->hasOne(Timetable::class, 'site_id');
    }

    public function internalContacts(): HasMany{
        return $this->hasMany(InternalContact::class, 'site_id');
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class)
                    ->withPivot('is_preferred') // Access pivot column
                    ->withTimestamps(); // Auto-manage timestamps
    }

    // A helper to get the preferred area(s)
    public function preferredArea()
    {
        return $this->areas()->wherePivot('is_preferred', true);
    }

}

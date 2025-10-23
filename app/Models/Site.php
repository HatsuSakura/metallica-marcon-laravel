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
        'denominazione',
        'is_main',
        'tipologia',
        'indirizzo',
        'lat',
        'lng',
        'fattore_rischioCalcolato',
        'giorniProssimoRitiro',
        'has_muletto',
        'has_transpallet_el',
        'has_transpallet_ma',
        'other_machines',
        'has_adr_consultant'
    ];

    /*
    It ensures that whenever you access the tipologia field, it will return an instance of the SiteTipologia enum rather than just a string (e.g., 'fully_operative').
    if ($site->tipologia === SiteTipologia::FULLY_OPERATIVE)
    */
    protected $casts = [
        'tipologia' => SiteTipologia::class,
    ];


    /*
    It ensures that when you create a new instance of the Site model, 
    if you do not set the tipologia field, 
    it will automatically be assigned the value SiteTipologia::FULLY_OPERATIVE.
    */
    protected $attributes = [
        'tipologia' => SiteTipologia::FULLY_OPERATIVE,
    ];

    public function scopeFilter(Builder $query, array $filters): Builder{
        return $query
        // Group the rischio conditions with parentheses
        ->when(
            $filters['rischioBasso'] || $filters['rischioMedio'] || $filters['rischioAlto'] || $filters['rischioCritico'],
            function ($query) use ($filters) {
                $query->where(function ($query) use ($filters) {
                    $query->when($filters['rischioBasso'] ?? false, fn ($q) => $q->orWhere('fattore_rischio_calcolato', '<', 0.50))
                          ->when($filters['rischioMedio'] ?? false, fn ($q) => $q->orWhereBetween('fattore_rischio_calcolato', [0.50, 0.75]))
                          ->when($filters['rischioAlto'] ?? false, fn ($q) => $q->orWhereBetween('fattore_rischio_calcolato', [0.75, 0.85]))
                          ->when($filters['rischioCritico'] ?? false, fn ($q) => $q->orWhere('fattore_rischio_calcolato', '>', 0.85));
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
                        $q->where('customer_occasionale', '1');
                    });
                }

                if (!$filters['occasionale'] && $filters['continuativo']) {
                    return $query->whereHas('customer', function ($q) {
                        $q->where('customer_occasionale', '0');
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
                $q->where('ragione_sociale', 'LIKE', "%{$value}%");
            })
        )
        /*
        ->when(
            $filters['chiave'] ?? false,
            fn ($query, $value) => $query->whereHas('customer', function ($q) use ($value) {
                $q->whereRaw(
                    "MATCH(
                        ragione_sociale, partita_iva, codice_fiscale, indirizzo_legale, email_commerciale, email_amministrativa, pec
                    ) 
                    AGAINST(? IN BOOLEAN MODE)", 
                    ["$value*"]
                );
            })
        ) 
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
        return $this->hasMany(Withdraw::class, 'site_id')->orderBy('withdraw_date', 'desc');
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

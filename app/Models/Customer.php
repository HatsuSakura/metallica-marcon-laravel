<?php

namespace App\Models;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = [
        'customerOccasionale',
        'ragioneSociale',
        'partitaIva',
        'codiceFiscale',
        'seller_id',
        'indirizzoLegale',
        'codiceSdi',
        'jobType',
        'emailCommerciale',
        'emailAmministrativa',
        'pec',
        'responsabileSmaltimenti',
        'telefonoPrincipale'
    ];

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
        return $this->hasOne(Site::class, 'customer_id')->where('denominazione', 'Principale');
    }

    public function scopeAlphabetic(Builder $query): Builder{
        return $query->orderBy('ragione_sociale', 'asc');
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
                    return $query->where('customer_occasionale', '1');
                }

                // If continuativo is true and occasionale is false, return only "continuativo" customers
                if (!$filters['occasionale'] && $filters['continuativo']) {
                    return $query->where('customer_occasionale', '0');
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
            fn ($query, $value) => $query->whereRaw(
                "MATCH(
                    ragione_sociale, partita_iva, codice_fiscale, indirizzo_legale, email_commerciale, email_amministrativa, pec
                ) 
                AGAINST(? IN BOOLEAN MODE)", 
                ["$value*"]
            )
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
    
    public function setEmailCommercialeAttribute($emailCommerciale){
        $this->attributes['emailCommerciale'] = strtolower($emailCommerciale);
    }
    public function setEmailAmministrativaAttribute($emailAmministrativa){
        $this->attributes['emailAmministrativa'] = strtolower($emailAmministrativa);
    }
    public function setPecAttribute($pec){
        $this->attributes['pec'] = strtolower($pec);
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

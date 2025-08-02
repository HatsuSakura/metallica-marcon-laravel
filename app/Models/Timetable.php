<?php

namespace App\Models;

use App\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'site_id',
        'hours_array',
    ];
    //

    public function site()
    {
        return $this->belongsTo(Site::class);
    }


        /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'timetables';

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

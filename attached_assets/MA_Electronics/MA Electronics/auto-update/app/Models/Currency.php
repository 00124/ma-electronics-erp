<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Scopes\CompanyScope;

class Currency extends BaseModel
{
    protected $table = 'currencies';

    protected $default = ['xid', 'name', 'symbol', 'decimal_separator', 'thousand_separator', 'remove_decimal_with_zero', 'space_between_price_and_price_symbol'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id'];

    protected $appends = ['xid'];

    protected $filterable = ['name'];

    protected $casts = [
        'is_deletable' => 'integer',
        'remove_decimal_with_zero' => 'integer',
        'space_between_price_and_price_symbol' => 'integer'
    ];


    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CompanyScope);
    }
}

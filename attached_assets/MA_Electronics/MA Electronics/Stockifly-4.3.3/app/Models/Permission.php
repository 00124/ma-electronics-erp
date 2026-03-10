<?php

namespace App\Models;

use App\Traits\Entrust\EntrustPermissionTrait;

class Permission extends BaseModel
{
    use EntrustPermissionTrait;

    protected $table = 'permissions';

    protected $default = ['xid'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'pivot'];

    protected $appends = ['xid'];
}

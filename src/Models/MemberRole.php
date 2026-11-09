<?php

namespace KraftInk\Plugins\Staff\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class MemberRole extends Pivot
{
    protected $table = 'staff__member_roles';

    public $incrementing = true;

    public $timestamps = false;
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    const SUPER_ADMINISTRATOR = 1;
    const ADMINISTRATOR = 2;
    const BASIC = 3;
}

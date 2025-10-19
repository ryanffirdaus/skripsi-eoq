<?php

namespace App\Http\Controllers;

use App\Http\Traits\RoleAccess;

abstract class Controller
{
    use RoleAccess;
}

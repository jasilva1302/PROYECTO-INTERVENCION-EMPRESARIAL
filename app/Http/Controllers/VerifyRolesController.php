<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class VerifyRolesController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        dd($roles->pluck('name')->toArray());
    }
} 
<?php

namespace App\Http\Controllers;

use App\Models\User;

class HustleController extends Controller
{
    public function index()
    {
        return view('hustle.index');
    }

    public function promote(User $profileOwner)
    {
        return view('hustle.promote', compact('profileOwner'));
    }
}
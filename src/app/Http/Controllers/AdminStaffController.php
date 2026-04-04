<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staffMembers = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', compact('staffMembers'));
    }
}

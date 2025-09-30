<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $query = User::query();

        if (request()->filled('q')) {
            $q = request('q');
            $query->where('name', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%");
        }

        if (request()->filled('role')) {
            $query->where('role', request('role'));
        }

        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users'));
    }
}

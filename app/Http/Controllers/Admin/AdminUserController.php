<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminUser;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = AdminUser::paginate(20);
        return view('admin.admin_users.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admin_users.create');
    }

    public function store(Request $request)
    {
        AdminUser::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password),
            'role'=>$request->role
        ]);
        return redirect()->route('admin.admin-users.index');
    }
}

?>
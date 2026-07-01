<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            setting([$key => $value])->save();
        }
        return back()->with('success','Settings updated');
    }
}
?> 
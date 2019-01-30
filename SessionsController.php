<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class SessionsController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth:web');
    }

    public function create()
    {
        return view('admin.sessions.create');
    }

    public function store()
    {
        if(!Auth::guard('web')->attempt(request(['email','password'])))
        {
            Request()->session()->flash('error_msg', 'Please check your credentials and try again.');
            return back()->withErrors([
                'message' => 'Please check your credentials and try again.'
            ]);
        }

        return redirect()->home();
    }

    public function destroy()
    {
        Auth::guard('web')->logout();
        return redirect()->home();
    }
}

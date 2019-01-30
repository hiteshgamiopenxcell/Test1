<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Admin;
use App\User;
use App\Content;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {
        $admins = Admin::selectRaw('count(*) as cnt')
        ->get()
        ->toArray();

        $contents = Content::selectRaw('count(*) as cnt')
        ->get()
        ->toArray();

        $users = User::selectRaw('count(*) as cnt')
        ->where('eIsDeleted','=',"no")
        ->get()
        ->toArray();

        $data = array(
            'title'=>'Dashboard',
            'admincnt'=> $admins[0]["cnt"],
            'usercnt'=> $users[0]['cnt'],
        );
        return view('admin.home')->with($data);
    }
}

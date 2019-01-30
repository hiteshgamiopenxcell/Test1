<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class ChangepasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {
        $data = array(
            'title'=>'Admin management',
        );
        return view('changepassword.index')->with($data);
    }

    public function store(Request $request)
    {
        $this->validate(request(),[
    	    'old_password' => 'required|string|min:6',
            'password' => 'required|string|min:6',
     	]);

        $admins = Admin::selectRaw('password')
            ->where('id', '=', auth()->User()->id)
            ->get();

        if(count($admins) > 0)
        {
            if (Hash::check($request->get('old_password'), $admins[0]->password))
            {
                $data = Admin::where('id', auth()->User()->id)
                    ->limit(1)
                    ->update(array('password' => bcrypt($request->get('password'))));

                if($data > 0)
                {
                    $request->session()->flash('success_msg', 'Password changed successfully!');
                }
                else
                {
                    $request->session()->flash('error_msg', 'Problem in change password');
                }
            }
            else
            {
                $request->session()->flash('error_msg', 'Current password wrong');
            }
        }
        else
        {
            $request->session()->flash('error_msg', 'Problem in change password');
        }
        return redirect('admin/changepassword');
    }
}

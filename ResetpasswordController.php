<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Admin;
use Illuminate\Http\Request;

class ResetpasswordController extends Controller
{
    public function __construct() 
    {
        $this->middleware('guest');
    }
    
    public function index($id)
    {
        $admin = Admin::find($id);        
        return view('resetpassword.create', compact('admin','id'));
    }
    
    public function update(Request $request, $id)
    {
        $this->validate(request(),[
            'otp' => 'bail|required|string|min:6|max:6',
            'password' => 'required|string|min:6|confirmed',
     	]);        
        $admin = Admin::where('otp', '=', $request->get('otp'))->where('id', '=', $id)->get();
        if(count($admin) > 0)
        {            
            $data = Admin::where('id', $id)
                ->limit(1)  
                ->update(array('otp' => null, 'password' => bcrypt($request->get('password'))));            
            
            if($data > 0)
            {            
                Request()->session()->flash('success_msg', 'Password changed successfully.');
                return redirect(route('login'));
            }
            else
            {
                Request()->session()->flash('error_msg', 'Problem in change password, Please try again.');
                return back();
            }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Please enter correct OTP.');
            return back();
        }
    }
}

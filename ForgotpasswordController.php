<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\RetrieveOTP;
use App\Admin;
use Illuminate\Http\Request;

class ForgotpasswordController extends Controller
{
    public function __construct() 
    {
        $this->middleware('guest');
    }
    
    public function index()
    {
        return view('forgotpassword.index');
    }
    
    public function store(Request $request)
    {
        $this->validate(request(),[
            'email' => 'required|string|email|max:255',
     	]);
        
        $admin = Admin::where('email', '=', $request->get('email'))->get()->first();
        if(count($admin) > 0)
        {                        
            $random = rand(100000,999999);            
                        
            $data = Admin::where('id', $admin->id)
                ->limit(1)  
                ->update(array('otp' => $random));
            
            if($data > 0)
            {            
                Mail::to($admin)->send(new RetrieveOTP($admin, $random));
                Request()->session()->flash('success_msg', 'Please check your inbox/spam folder for an email to reset your password.');
                return redirect(route('resetpassword',['id'=>$admin->id]));
            }
            else
            {
                Request()->session()->flash('error_msg', 'Problem in sending password reset link, Please try again.');
                return back();
            }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Email Address not found.');
            return back();
        }        
    }
}

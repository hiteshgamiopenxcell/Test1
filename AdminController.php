<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Admin;
use App\Settings;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function datatable()
    {
        $data = array(
            'title'=>'Admin management',
        );
        return view('admin.admin.index')->with($data);
    }

    public function getadmins()
    {
        $admins = Admin::selectRaw('name,email,created_at,id,DATE_FORMAT(created_at,"%d/%m/%Y %H:%i:%s") as created_at_dmy, CASE WHEN id = '.auth()->User()->id.' THEN "1" ELSE "0" END AS is_current_admin')
            ->where('type','=',1)
            ->get()
            ->toArray();

        $datatables = Datatables::of($admins);
    	return $datatables->make(true);
    }

    public function create()
    {
        $data = array(
            'title'=>'Admin management',
        );
        return view('admin.admin.create')->with($data);
    }

    public function store(Request $request)
    {
        $this->validate(request(),[
            'name' => 'bail|required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:6|confirmed',
     	]);

        $admin = new Admin([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => bcrypt($request->get('password')),
            'type' => 1
        ]);
        if($admin->save())
        {
            Request()->session()->flash('success_msg', 'Admin details added successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in adding admin details, please try again.');
        }
        return redirect('admin/admin');
    }

    public function destroy(Request $request,Admin $admin)
    {
        if($request->ajax())
        {
            $admin = $admin->find($request->input('data'));
            if ($admin->delete())
            {
                Request()->session()->flash('success_msg', 'Admin details deleted successfully.');
                return 1;
            }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in deleting admin details, please try again.');
            return 0;
        }
    }

    public function edit($id)
    {
        $data = array(
            'title'=>'Admin management',
        );
        $admin = Admin::find($id);
        return view('admin.admin.edit', compact('admin','id'))->with($data);
    }

    public function update(Request $request, $id)
    {
        $validateArray = array(
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins,email,'.$id,
        );

        if($request->has('password')){
            $validateArray['password'] = 'required|string|min:6|confirmed';
    	}

        $this->validate(request(), $validateArray);
        $admin = Admin::find($id);
        $admin->name = $request->get('name');
        $admin->email = $request->get('email');
        if($request->has('password'))
        {
            $admin->password = bcrypt($request->get('password'));
        }
        if($admin->save())
        {
            Request()->session()->flash('success_msg', 'Admin details updated successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating admin details, please try again.');
        }
        return redirect('admin/admin');
    }
    public function settings()
    {
        $data = array(
            'title'=>'Default Settings',
        );
        $setting = Settings::find(1);
        return view('admin.settings', compact('setting'))->with($data);
    }
    public function updatesettings(Request $request)
    {
        $validateArray = array(
            'vAdministrationFee' => 'required|string|max:255',
            'tPriceDisclaimer' => 'required|string',
        );
        $this->validate(request(), $validateArray);
        $setting = Settings::find(1);
        $setting->vAdministrationFee = str_replace(' ','',$request->get('vAdministrationFee'));
        $setting->tPriceDisclaimer = $request->get('tPriceDisclaimer');
        if($setting->save())
        {
            Request()->session()->flash('success_msg', 'Settings updated successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating default Settings, please try again.');
        }
        return redirect('admin/settings');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\ReportAbuse;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Support\Facades\File;
/*use \Folklore\Image\Facades\Image;*/
use Illuminate\Http\Request;
use Image;
use Storage;
use Config; //constant file
use Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function datatable()
    {
        $data = array(
            'title'=>'User management',
        );
        return view('admin.user.index')->with($data);
    }

    public function getusers()
    {
        $users = User::selectRaw('vFullName, email, vPhoneNumber, vProfileImage, created_at, eStatus,eIsVerified, id, DATE_FORMAT(created_at,"%d/%m/%Y %H:%i:%s") as created_at_dmy')
        ->where('eIsDeleted','=',"no")
        ->get()
        ->toArray();

        $datatables = Datatables::of($users);
        return $datatables->make(true);
    }

    public function destroy(Request $request,User $user)
    {
        if($request->ajax())
        {
            $user = $user->find($request->input('data'));
            $user->eIsDeleted = "1";
            if ($user->save())
            {
                Request()->session()->flash('success_msg', 'User details updated successfully.');
                return 1;
            }
            // $user = $user->find($request->input('data'));
            // if($user->image != "default.png")
            // {
            //     $thumbPath = public_path('images/user/thumb');
            //     $originalPath = public_path('images/user/original');
            //     File::delete($thumbPath."/".$user->image);
            //     File::delete($originalPath."/".$user->image);
            // }
            // if ($user->delete())
            // {
            //     Request()->session()->flash('success_msg', 'User details deleted successfully.');
            //     return 1;
            // }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in deleting User details, please try again.');
            return 0;
        }
    }

    public function changestatus(Request $request,User $user)
    {
        if($request->ajax())
        {
            $user = $user->find($request->input('data'));
            $user->eStatus = ($user->eStatus == "Active") ? 'Inactive' : 'Active';
            if ($user->save())
            {
                Request()->session()->flash('success_msg', 'User details updated successfully.');
                return 1;
            }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating User details, please try again.');
            return 0;
        }
    }

    public function sendemail(Request $request)
    {
        $request->iUserID;
        if($request->ajax())
        {
            $user = User::find($request->iUserID);
            $email = $user->email;
            $fromemail =  Config::get('constants.general.FROM_EMAIL');
            $fullname = $user->vFirstName.' '.$user->vLastName;
            $verifytoken = $user->tVerificationToken;
            $verifytoken = url('user/verify', $verifytoken);
            Mail::send('mail.welcomemail', ['vName' => $fullname,'verifytoken'=>$verifytoken], function ($message) use ($fromemail,$email,$fullname)
            {
                $message->from($fromemail, 'SkyDog');
                $message->to($email);
                $message->subject('Welcome'.' '.$fullname.' to SkyDog');
            });
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating User details, please try again.');
            return 0;
        }
    }

    public function changeprofilestatus(Request $request,User $user)
    {
        if($request->ajax())
        {
            $user = $user->find($request->input('data'));
            $user->profile_status = $request->input('status');
            if ($user->save())
            {
                Request()->session()->flash('success_msg', 'User details updated successfully.');
                return 1;
            }
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating User details, please try again.');
            return 0;
        }
    }

    public function create()
    {
        $data = array(
            'title'=>'User management',
        );
        // dd($data);
        return view('admin.user.create')->with($data);
    }

    public function test()
    {
        $data = array(
            'title'=>'User management',
        );
        dd($data);
    }

    public function store(Request $request)
    {
        $this->validate(request(),[
            'vFullName' => 'bail|required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        /*if($request->hasFile('vProfileImage'))
        {
            $fileName = time() .'_'. md5(request('vProfileImage')->getClientOriginalName()) .'.'. request('vProfileImage')->getClientOriginalExtension();
            // echo $fileName;
            $image = $request->file('vProfileImage');
            // dd($image);
            $thumbPath = public_path('images/user/thumb');
            $img = Image::make($image->getRealPath(),array(
                'width' => 100,
                'height' => 100,
                'grayscale' => false
            ));
            $img->save($thumbPath.'/'.$fileName);

            $originalPath = public_path('images/user/original');
            $image->move($originalPath, $fileName);
        }
        else
        {
            $fileName = 'default.png';
        }*/

        $fileName = '';
        if($request->hasFile('vProfileImage'))
        {
            try
            {
                //----- Upload post image on s3 ---------
                $image = $request->file('vProfileImage');
                $s3 = \Storage::disk('s3');
                $fileName = time() .'_'. md5(request('vProfileImage')->getClientOriginalName()) .'.'. request('vProfileImage')->getClientOriginalExtension();
                $filePath = '/user/original/' . $fileName;
                $fileThumbPath = '/user/thumb/' . $fileName;

                if($s3->put($filePath,  file_get_contents($image),'public')) {
                    if(Storage::disk('s3')->exists($filePath)) {
                        $size = Storage::disk('s3')->size($filePath);
                        list($width, $height) = getimagesize(Config::get('constants.URL.USER_ORIGINAL').$fileName);
                        if($width>150 && $height>150)
                        {

                            $img = Image::make(Config::get('constants.URL.USER_ORIGINAL').$fileName);
                            $img->resize(150, 150, function ($constraint) {
                                $constraint->aspectRatio();
                            });
                            $resource = $img->stream()->detach();
                            $path = Storage::disk('s3')->put($fileThumbPath,
                                $resource,'public'
                            );
                        }
                        else
                        {
                            $result = $s3->put($fileThumbPath, $fileContents,'public');
                        }
                    }
                }
                //---------------------------------------
            }
            catch(Exception $e)
            {
                return $this->respondInternalError($e->getMessage());
            }
        }

        $usercreate = new User([
            'vFullName' => $request->get('vFullName'),
            'email' => $request->get('email'),
            'vPhoneNumber' => $request->get('vPhoneNumber'),
            'vProfileImage'  => $fileName,
            'password' => bcrypt($request->get('password'))
        ]);
        if($usercreate->save())
        {
            Request()->session()->flash('success_msg', 'User details added successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in adding user details, please try again.');
        }
        return redirect('admin/user');
    }

    public function edit($id)
    {
        $data = array(
            'title'=>'User management',
        );
        $admin = User::find($id);
        return view('admin.user.edit', compact('admin','id'))->with($data);
    }

    public function update(Request $request, $id)
    {
        $validateArray = array(
            'vFullName' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id.',id'
        );

        // dd($validateArray);

        if($request->has('password')){
            $validateArray['password'] = 'required|string|min:6';
        }

        $this->validate(request(), $validateArray);
        $user = User::find($id);
        // dd($user);
        $user->vFullName = $request->get('vFullName');
        $user->email = $request->get('email');
        $user->vPhoneNumber = $request->get('vPhoneNumber');

        if($request->has('password'))
        {
            $user->password = bcrypt($request->get('password'));
        }

        if($request->hasFile('vProfileImage'))
        {
            $thumbPath = public_path('images/user/thumb');
            $originalPath = public_path('images/user/original');

            if($user->vProfileImage != "default.png")
            {
                $deleteOriginalFile = $originalPath."/".$user->vProfileImage;
                $deleteThumbFile = $thumbPath."/".$user->vProfileImage;
                if(File::exists($deleteOriginalFile) && File::exists($deleteThumbFile)) {
                    File::delete([
                        $deleteOriginalFile,
                        $deleteThumbFile
                    ]);
                }
            }

            $fileName = time() .'_'. md5(request('vProfileImage')->getClientOriginalName()) .'.'. request('vProfileImage')->getClientOriginalExtension();
            $image = $request->file('vProfileImage');
            $img = Image::make($image->getRealPath(),array(
                'width' => 100,
                'height' => 100,
                'grayscale' => false
            ));
            $img->save($thumbPath.'/'.$fileName);
            $image->move($originalPath, $fileName);
            $user->vProfileImage = $fileName;
        }

        if($user->save())
        {
            Request()->session()->flash('success_msg', 'User details updated successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating User details, please try again.');
        }
        return redirect('admin/user');
    }

    public function more($id){
        $user = User::find($id);
        $data = array(
            'title'=>'User Details',
            'id'=>$id,
            'user'=>$user
        );
        return view('admin.user.more')->with($data);
    }

    public function show(Request $request, User $user)
    {
        $user = $user->find($request->input('data'));
        $str = '<div class="table-responsive"><table class="table table-striped">';
        $str .= "<tbody>";

        $str .= "<tr>";
        $str .= "<th>Full Name</th>";
        $str .= "<th> : </th>";
        $str .= "<td>".$user->vFullName."</td>";
        $str .= "</tr>";

        $str .= "<tr>";
        $str .= "<th>Email address</th>";
        $str .= "<th> : </th>";
        $str .= "<td>".$user->email."</td>";
        $str .= "</tr>";

        $str .= "<tr>";
        $str .= "<th>Profile picture</th>";
        $str .= "<th> : </th>";
        $str .= "<td><img style='width:100px;height:100px;' src=".Config::get("constants.URL.USER_THUMB").$user->vProfileImage."></td>";
        $str .= "</tr>";

        $str .= "<tr>";
        $str .= "<th>Status</th>";
        $str .= "<th> : </th>";
        $str .= "<td>".$user->eStatus."</td>";
        $str .= "</tr>";

        $str .= "</tbody></table></div>";
        return $str;
    }

    public function reportabuse(){
        $data = array(
            'title'=>'Report Management'
        );
        return view('admin.reportabuse')->with($data);
    }

    public function getreports()
    {
        $reports = ReportAbuse::selectRaw('iReportID,iEntryID,iReportUserID,tDescription,  created_at, DATE_FORMAT(created_at,"%d/%m/%Y %H:%i:%s") as created_at_dmy')
        ->with(['user','entries','entries.user'])
        ->get()
        ->toArray();

        $datatables = Datatables::of($reports);
        return $datatables->make(true);
    }
}

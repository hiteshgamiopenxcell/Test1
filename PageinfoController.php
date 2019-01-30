<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PageInfo;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;

class PageinfoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function datatable()
    {
        $data = array(
            'title'=>'Page Content management',
        );
        return view('admin.pageinfo.index')->with($data);
    }

    public function getpageinfos()
    {
        $contents = PageInfo::selectRaw('vTitle,CONCAT(LEFT(tContent, 200),"...") as tContent,created_at,DATE_FORMAT(created_at,"%d/%m/%Y %H:%i:%s") as created_at_dmy,iPageID');
        $datatables = Datatables::eloquent($contents)->rawColumns(['vTitle','tContent','created_at','created_at_dmy','iPageID']);
    	return $datatables->make(true);
    }

    public function edit($id)
    {
        $data = array(
            'title'=>'Page Content management',
        );
        $content = PageInfo::find($id);
        return view('admin.pageinfo.edit', compact('content','id'))->with($data);
    }

    public function create()
    {
        $data = array(
            'title'=>'Page Content management',
        );
        // dd($data);
        return view('admin.pageinfo.create')->with($data);
    }

    public function store(Request $request)
    {
        $validateArray = array(
            'vTitle' => 'required|string|max:255',
            'tContent' => 'required|string|',
        );

        $pageinfocreate = new PageInfo([
            'vTitle' => $request->get('vTitle'),
            'tContent' => $request->get('tContent')
        ]);

        if($pageinfocreate->save())
        {
            Request()->session()->flash('success_msg', 'Page Info added successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in adding Page Info, please try again.');
        }
        return redirect('admin/pageinfo');
    }

    public function update(Request $request, $id)
    {
        $validateArray = array(
            'vTitle' => 'required|string|max:255',
            'tContent' => 'required|string|',
        );

        $this->validate(request(), $validateArray);
        $content = PageInfo::find($id);
        /*dd($content);*/
        /*dd($request->get('vTitle'));*/
        $content->vTitle = $request->get('vTitle');
        $content->tContent = $request->get('tContent');

        if($content->save())
        {
            Request()->session()->flash('success_msg', 'Page Info updated successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating content, please try again.');
        }
        return redirect('admin/pageinfo');
    }
}

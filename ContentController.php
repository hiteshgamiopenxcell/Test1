<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Content;
use Yajra\Datatables\Facades\Datatables;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function datatable()
    {
        $data = array(
            'title'=>'Content management',
        );
        return view('admin.content.index')->with($data);
    }

    public function getcontents()
    {
        $contents = Content::selectRaw('title,CONCAT(LEFT(body, 200),"...") as body,created_at,DATE_FORMAT(created_at,"%d/%m/%Y %H:%i:%s") as created_at_dmy,id');
        $datatables = Datatables::eloquent($contents)->rawColumns(['title','body','created_at','created_at_dmy','id']);
    	return $datatables->make(true);
    }

    public function edit($id)
    {
        $data = array(
            'title'=>'Content management',
        );
        $content = Content::find($id);
        return view('admin.content.edit', compact('content','id'))->with($data);
    }

    public function update(Request $request, $id)
    {
        $validateArray = array(
            'title' => 'required|string|max:255',
            'body' => 'required|string|',
        );

        $this->validate(request(), $validateArray);
        $content = Content::find($id);
        $content->title = $request->get('title');
        $content->body = $request->get('body');

        if($content->save())
        {
            Request()->session()->flash('success_msg', 'Content updated successfully.');
        }
        else
        {
            Request()->session()->flash('error_msg', 'Problem in updating content, please try again.');
        }
        return redirect('admin/content');
    }
}

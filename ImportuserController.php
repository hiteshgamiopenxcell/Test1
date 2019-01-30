<?php
//use Input;
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Importuser;
use App\Positionlist;
use \DB;
use \Excel;
use Illuminate\Http\Request;

class ImportuserController extends Controller
{
	public function importExport()
	{
		$data = array(
			'title'=>'Import management',
		);
		return view('importuser.importExport')->with($data);
	}
	public function downloadExcel($type)
	{
		$data = Importuser::get()->toArray();
		return Excel::create('playerlist', function($excel) use ($data) {
			$excel->sheet('mySheet', function($sheet) use ($data)
			{
				$sheet->fromArray($data);
			});
		})->download($type);
	}
	public function importExcel(Request $request)
	{
		$currentDateTime = date('Y-m-d H:i:s');
		if($request->hasFile('import_file')){
			$path = $request->file('import_file')->getRealPath();
			// dd($path);
			$data = Excel::load($path, function($reader) {
			})->get();
			//dd($data);
			if(!empty($data) && $data->count()){
				foreach ($data as $key => $value) {
					
					$position = Positionlist::selectRaw('iPositionID')
					->where('vPositionName', '=', trim(strtoupper($value->position
)))                        
					->get();
					if(count($position) > 0)
					{
						$iPositionID = $position[0]->iPositionID;
					}else{
						$cnt = Positionlist::create([
							'vPositionName' => trim(strtoupper($value->position
)),							
							'created_at' => $currentDateTime,
							'updated_at' => $currentDateTime
						]);
						$iPositionID = $cnt->iPositionID;
					}

					$insert[] = ['iRanking'=>$value->ranking,'vPlayerName' => $value->playername, 'vCollegeName' => $value->college,'iPositionID'=>$iPositionID,'vHeight'=>$value->height,'vWeight'=>$value->weight,'vYear'=>$value->year];
				}
				if(!empty($insert)){
					DB::table('tbl_player')->insert($insert);
					Request()->session()->flash('success_msg', 'Players imported successfully.');
					return redirect('admin/player');
				}
			}
		}
		return back();
	}
}
?>
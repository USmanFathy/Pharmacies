<?php

namespace App\Http\Controllers\API;

use App\Exports\ProfilersExport;
use App\Http\Controllers\Controller;
use App\Models\Profiler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel as ed;
use Rap2hpoutre\FastExcel\FastExcel;

class ProfilerController extends Controller
{
	public function index(Request $request)
	{
		$options = Profiler::where('status', 'Active')
		->where('contact_no','LIKE','%'.$request->keyword.'%')
		->limit(20)
		->offset($request->start)
		->orderBy('id','DESC')
		->get();

		$totalRecords  = Profiler::where('status','Active')
		->where('contact_no','LIKE','%'.$request->keyword.'%')
		->count();

		return [
			'records' => $options,
			'limit' => 20,
			'totalRecords' => $totalRecords,
			'currentUserID' => Auth::user()->id
		];
	}

	public function store(Request $request)
	{
		$request->validate([
			'account_title'    => ['required'],
			'contact_no'       => ['required','unique:profilers'],
			'status'           => ['required']
		]);

		$item = new Profiler($request->all());
		$item->save();

		return response()->json([
			'alert' 		  =>'info',
			'msg'   		  =>'Profiler Created Successfully',
			'profileDetail'   => $item,
		]);
	}


	public function show($id)
	{
		$option = Profiler::find($id);
		return response()->json([$option]);
	}


	public function update(Request $request)
	{
		$request->validate([
			'id'               => ['required'],
			'account_title'    => ['required'],
			'contact_no'       => ['required',"unique:profilers,contact_no,$request->id"]
		]);

		$product = Profiler::find($request->id);
		$product->update($request->all());

		return response()->json([
			'alert' =>'info',
			'msg'=>'Profiler Updated Successfully'
		]);
	}
    public function downloadCsv()
    {
        return (new ProfilersExport())->download('sampleData.csv', ed::CSV, ['Content-Type' => 'text/csv']);
    }
    public static function export()
    {
        $columnHeaders = [     'Account Title',
            'Email Address',
            'Contact No',
            'National Id',
            'Address',
            'Description',
            'Account Type',
            'Status',];

        $data = Profiler::all();

        $exportData = [];

        $exportData[] = $columnHeaders;

        // Add data rows to the export data
        foreach ($data as $row) {

            $rowData = [
                $row->account_title ??'',
                $row->email_address??'',
                $row->contact_no??'',
                $row->national_id??'',
                $row->address??'',
                $row->description??'',
                $row->status??'',
            ];
            $exportData[] = $rowData;
        }

        // Generate a filename for the exported file
        $filename = 'exported_data_' . time() . '.xlsx';

        // Export the data to an Excel file
        (new FastExcel($exportData))->withoutHeaders()->export(public_path($filename));

        return response()->download($filename);
    }
    public function import(Request $request)
    {

        $file = $request->file('file');
        $rules = [
            'account_title'    => ['required'],
            'contact_no'       => ['required','unique:profilers'],
        ];

        $columnHeaders = [   'Account Title',
            'Email Address',
            'Contact No',
            'National Id',
            'Address',
            'Description',
            'Account Type',
            'Status',];
        $needed_columns = [  'Account Title' => 'account_title',
            'Email Address' => 'email_address',
            'Contact No' => 'contact_no',
            'National Id' => 'national_id',
            'Address' => 'address',
            'Description' => 'description',
            'Account Type' => 'account_type',
            'Status' => 'status',];
        $relationNames=[];
        $relationMany = [];

        try{
            $this->excelService->import($file, $rules,$needed_columns ,new Profiler(),$columnHeaders,$relationNames,$relationMany );
            return response()->json(['message' => "data imported successfully"]);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 406);
        }
    }
	public function delete(Request $request)
	{
		$product = Profiler::find($request->id);
		$product->update($request->all());

		return response()->json([
			'alert' =>'info',
			'msg'=>'Profiler Deleted Successfully'
		]);
	}

	public function searchProfile(Request $request)
	{
		$keyword = $request['keyword'];

		$options = Profiler::where('status', 'Active')
		->where('contact_no','=',$request->keyword)
		->orWhere('account_title','LIKE','%'.$request->keyword.'%')
		->orWhere('email_address','LIKE','%'.$request->keyword.'%')
		->limit(20)
		->offset($request->start)
		->orderBy('id','DESC')
		->get();

		return [
			'records' => $options
		];
	}
}

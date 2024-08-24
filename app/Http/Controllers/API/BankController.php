<?php

namespace App\Http\Controllers\API;

use App\Exports\BankExport;
use App\Http\Controllers\Controller;
use App\Models\Banks;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel as ed;
use Rap2hpoutre\FastExcel\FastExcel;


class BankController extends Controller
{

    public function index(Request $request)
    {
        if($request->storeID == 0)
        {
            $request->storeID  = Auth::user()->branch_id;
        }

        $options = Banks::where('status','Active')
        ->limit(20)
        ->offset($request->start)
        ->orderBy('id','DESC')
        ->get();

        $totalRecords  = Banks::where('status','Active')
        ->count();

        $user =  new User();
        $stores = $user->getUserStores();

        return [
            'stores' => $stores,
            'records' => $options,
            'limit' => 20,
            'totalRecords' => $totalRecords,
            'currentStoreID' => Auth::user()->branch_id
        ];
    }

    public function get_all_bank()
    {
        $user =  new User();
		$banks = $user->getUserBanks();

        return [
            'records' => $banks,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
			'bank'      => ['required'],
			'branch'    => ['required'],
			'code'      => ['required'],
			'title'     => ['required'],
			'number'    => ['required'],
			'type'      => ['required'],
			'status'    => ['required']
		]);

        $item = new Banks($request->all());
        $item->save();

        return response()->json([
            'alert' =>'info',
            'msg'   =>'Bank Created Successfully'
        ]);
    }
    public function downloadCsv()
    {
        return (new BankExport())->download('sampleData.csv', ed::CSV, ['Content-Type' => 'text/csv']);
    }
    public static function export()
    {
        $columnHeaders =  ['Bank', 'Branch Name','Branch Code', 'Account Title', 'Account Number' , 'Account Type','Ending Date' ,'Account Balance (USD)'];

        $data = Banks::all();

        $exportData = [];

        $exportData[] = $columnHeaders;

        // Add data rows to the export data
        foreach ($data as $row) {

            $rowData = [
                $row->bank??'',
                $row->branch??'',
                $row->code??'',
                $row->title??'',
                $row->number??'',
                $row->type??'',
                $row->ending_date??'',
                $row->balance??'',
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
            'bank'      => ['required'],
            'branch'    => ['required'],
            'code'      => ['required'],
            'title'     => ['required'],
            'number'    => ['required'],
            'type'      => ['required'],
        ];

        $columnHeaders =  ['Bank', 'Branch Name','Branch Code', 'Account Title', 'Account Number' , 'Account Type','Ending Date' ,'Account Balance (USD)'];
        $needed_columns = ['Bank'=>'bank', 'Branch Name'=>'branch','Branch Code'=>'code'
            , 'Account Title'=>'title', 'Account Number'=>'number' ,'Account Type' => 'type','Ending Date'=>'ending_date' ,'Account Balance (USD)' => 'balance'];
        $relationNames=[];
        $relationMany = [];

        try{
            $this->excelService->import($file, $rules,$needed_columns ,new Banks(),$columnHeaders,$relationNames,$relationMany );
            return response()->json(['message' => "data imported successfully"]);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 406);
        }
    }

    public function show($id)
    {
        $option = Banks::find($id);
        return response()->json([$option]);
    }


    public function update(Request $request)
    {
        $request->validate([
			'id'     => ['required'],
			'bank'   => ['required'],
			'branch' => ['required'],
			'code'   => ['required'],
			'title'  => ['required'],
			'number' => ['required'],
			'type'   => ['required']
		]);

        $product = Banks::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Bank Updated Successfully'
        ]);
    }


    public function delete(Request $request)
    {
        $product = Banks::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Bank Deleted Successfully'
        ]);
    }
}

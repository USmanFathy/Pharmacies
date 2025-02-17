<?php

namespace App\Http\Controllers\API;

use App\Exports\ChartAccountExport;
use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ed;
use Rap2hpoutre\FastExcel\FastExcel;

class ChartAccountController extends Controller
{
    public function index(Request $request)
    {
        $options = ChartAccount::where('status', 'Active')
        ->where('account_name','LIKE','%'.$request->keyword.'%')
        ->limit(20)
        ->offset($request->start)
        ->orderBy('id','DESC')
        ->get();

        $totalRecords  = ChartAccount::where('status','Active')
        ->count();

        return [
            'records' => $options,
            'limit' => 20,
            'totalRecords' => $totalRecords
        ];
    }
    public function downloadCsv()
    {
        return (new ChartAccountExport())->download('sampleData.csv', ed::CSV, ['Content-Type' => 'text/csv']);
    }
    public static function export()
    {
        $columnHeaders =  ['Account name','Account Code', 'Account nature', 'Account type', 'Expense type'];

        $data = ChartAccount::with('branches')
            ->get();

        $exportData = [];

        $exportData[] = $columnHeaders;

        // Add data rows to the export data
        foreach ($data as $row) {

            $rowData = [
                $row->account_name??'',
                $row->account_code??'',
                $row->account_nature??'',
                $row->account_type??'',
                $row->expense_type??'',
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
            'account_code'      => ['required'],
            'account_name'      => ['required'],
            'account_nature'    => ['required'],
            'account_type'      => ['required'],
            'expense_type'      => ['required_if:account_type,==,Expense'],
        ];

        $columnHeaders =  ['Account name', 'Account Code','Account nature', 'Account type', 'Expense type'];
        $needed_columns = ['Account name'=>'account_name', 'Account Code'=>'account_code','Account nature'=>'account_nature'
            , 'Account type'=>'account_type', 'Expense type'=>'expense_type'];
        $relationNames=[];
        $relationMany = [];

        try{
            $this->excelService->import($file, $rules,$needed_columns ,new ChartAccount(),$columnHeaders,$relationNames,$relationMany );
            return response()->json(['message' => "data imported successfully"]);
        } catch (\Exception $e) {

            return response()->json(['message' => $e->getMessage()], 406);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'account_code'      => ['required'],
            'account_name'      => ['required'],
            'account_nature'    => ['required'],
            'account_type'      => ['required'],
            'expense_type'      => ['required'],
            'status'            => ['required'],
        ]);

        $item = new ChartAccount($request->all());
        $item->save();

        return response()->json([
            'alert' =>'info',
            'msg'   =>'Account Created Successfully'
        ]);
    }


    public function show($id)
    {
        $option = ChartAccount::find($id);
        return response()->json([$option]);
    }


    public function update(Request $request)
    {
        $request->validate([
            'id'                => ['required'],
            'account_code'      => ['required',"unique:chart_accounts,account_code,$request->id"],
            'account_name'      => ['required'],
            'account_nature'    => ['required'],
            'account_type'      => ['required'],
            'expense_type'      => ['required'],
        ]);

        $product = ChartAccount::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Account Updated Successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $product = ChartAccount::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Account Deleted Successfully'
        ]);
    }

    public function searchAccountHeads(Request $request)
	{
		$keyword = $request['keyword'];

		$options = ChartAccount::where('status', 'Active')
		->where('account_code','=',$request->keyword)
		->orWhere('account_name','LIKE','%'.$request->keyword.'%')
		->orWhere('account_nature','LIKE','%'.$request->keyword.'%')
		->limit(20)
		->offset($request->start)
		->orderBy('id','DESC')
		->get();

		return [
			'records' => $options
		];
	}
}

<?php

namespace App\Http\Controllers\API;

use App\Exports\OptionTagsExport;
use App\Http\Controllers\Controller;
use App\Models\OptionTags;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ed;
use Rap2hpoutre\FastExcel\FastExcel;

class OptionTagsController extends Controller
{
    public function index(Request $request)
    {
        $options = OptionTags::where('status', 'Active')
        ->where('option_type',$request->tag)
        ->limit(20)
        ->offset($request->start)
        ->orderBy('id','DESC')
        ->get();

        $totalRecords  = OptionTags::where('status','Active')
        ->where('option_type',$request->tag)
        ->count();

        return [
            'records' => $options,
            'limit' => 20,
            'totalRecords' => $totalRecords
        ];
    }
    public function downloadCsv()
    {
        return (new OptionTagsExport())->download('sampleData.csv', ed::CSV, ['Content-Type' => 'text/csv']);
    }
    public static function export()
    {
        $columnHeaders =  ['Option Name','Option Type','Description' , 'Status'];

        $data = OptionTags::all();

        $exportData = [];

        $exportData[] = $columnHeaders;

        // Add data rows to the export data
        foreach ($data as $row) {

            $rowData = [
                $row->option_name??'',
                $row->option_type??'',
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
            'optionName'   => ['required'],
        ];

        $columnHeaders =  ['Option Name','Option Type','Description' ,];
        $needed_columns = ['Option Name' => 'option_name',
            'Option Type' => 'option_type',
            'Description' => 'description',];
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
			'optionName'   => ['required'],
		]);

        $item = new OptionTags([
            'option_name' => $request->optionName,
            'description' => $request->description,
            'option_type' => $request->optionType,
            'status'      => $request->status,
        ]);

        $item->save();

        return response()->json([
            'alert' =>'info',
            'msg'   =>'Option Created Successfully'
        ]);
    }


    public function show($id)
    {
        $option = OptionTags::find($id);
        return response()->json([$option]);
    }


    public function update(Request $request)
    {
        $request->validate([
			'id'            => ['required'],
			'option_name'   => ['required'],
		]);

        $product = OptionTags::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Options Updated Successfully'
        ]);
    }

    public function delete(Request $request)
    {
        $product = OptionTags::find($request->id);
        $product->update($request->all());

        return response()->json([
            'alert' =>'info',
            'msg'=>'Options Deleted Successfully'
        ]);
    }

    public function getRiskFactorQuestions()
    {
        $options = OptionTags::where('status', 'Active')
        ->where(function ($query) {
            $query->where('option_type','=','Previous Pregnancy')
            ->orWhere('option_type','=','Previous Child Birth')
            ->orWhere('option_type','=','Current Pregnancy')
            ->orWhere('option_type','=','Previous Operation');
        })
        ->get();

        return [
            'records' => $options
        ];
    }


}

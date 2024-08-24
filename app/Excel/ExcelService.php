<?php
namespace App\Excel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;

class ExcelService
{
    public function import(UploadedFile $file, array $rules, array $needed_columns, Model $model, array $columnHeaders = [], array $relationNames = [], array $relationMany = []): bool
    {


        // Read the data from the Excel file
        DB::beginTransaction();
        try {
            $data = (new FastExcel)->import($file->getRealPath(), function ($line) use ($model, $needed_columns, $relationNames, $rules, $columnHeaders,$relationMany) {

                if (empty($line[$columnHeaders[0]])) {
                    return;
                }
                $validation = $this->validateData($line, $rules);

                if ($validation->fails()) {
                    throw new \Exception($validation->errors()->first());
                }

                $newModel = new $model;
                foreach ($needed_columns as $key => $column) {
                    if ($column == 'date') {

                        $line[$key] = $line[$key]->format('Y-m-d');
                    }
                    $newModel->{$column} = $line[$key];
                }
                foreach ($relationNames as $relation) {
                    $row = $relation['model']::where($relation['column'], trim($line[$relation['display']]))->first();
                    if ($row ) {
                        $value = $row->id;
                        $newModel->{$relation['foreign_key']} = $value;
                    }
                }

                $newModel->save();
                $newModels=[];
                $branchIds=[];
                foreach ($needed_columns as $key => $column) {
                    $modelCondition =  $model->where($column,$line[$key])->first();
                    $newModels[]=$modelCondition;

                }


                $i = 0;
                foreach ($relationMany as $key => $relation) {
                    $row = $relation['model']::where($relation['column'], trim($line[$relation['display']]))->first();

                    if ($row) {
                        $newModels[$i]->{$key}()->attach([$row->id]);
                    }

                    // Increment index
                    $i++;
                }

            });



            DB::commit(); // Commit the transaction if all queries are successful

            // //Check if the data array is empty
            // if ($data->isEmpty()) {
            //     throw new \Exception('The Excel file is empty.', 201);
            // }

            return true;
        } catch (\Exception $e) {
            DB::rollback(); // Rollback the transaction if any query fails
            throw $e;
        }
    }
    private function validateData(array $data, array $rules): \Illuminate\Contracts\Validation\Validator
    {
        //  dd($data,$rules);
        $validator = Validator::make($data, $rules);

        return $validator;
    }
}

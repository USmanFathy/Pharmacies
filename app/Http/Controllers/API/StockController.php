<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OptionTags;
use App\Models\Branch;
use App\Models\Stock;
use App\Models\User;
use App\Models\Products;
use App\Exports\StocksExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

//IMPORT

use App\Imports\StocksImport;



class StockController extends Controller
{
	public function index()
	{
		$brand = OptionTags::where('status', 'Active')
		->where('option_type','Brands')
		->get();

		$brandSector = OptionTags::where('status', 'Active')
		->where('option_type','Brand Sectors')
		->get();

		$category = OptionTags::where('status', 'Active')
		->where('option_type','Category')
		->get();

		$productType = OptionTags::where('status', 'Active')
		->where('option_type','Products Type')
		->get();


		$storeTaxes = Branch::with([
			'taxName1:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName2:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName3:chart_accounts.id,chart_accounts.account_name as chartName',
		])
		->where('id',Auth::user()->branch_id)
		->get();

		return [
			'storeTaxes'   	=> $storeTaxes,
			'productType'   => $productType,
			'brand'         => $brand,
			'brandSector'   => $brandSector,
			'category'      => $category
		];
	}


	public function stockList(Request $request)
	{
		$keyword = $request->keyword;

		if($request->storeID == 0)
		{
			$request->storeID  = Auth::user()->branch_id;
		}

		$stocks = DB::table('stocks')
		->where(function ($query) use ($keyword) {
			$query
			->orWhere('stocks.product_name','LIKE','%'.$keyword.'%')
			->orWhere('stocks.generic','LIKE', '%'.$keyword.'%')
			->orWhere('stocks.batch_no','LIKE', '%'.$keyword.'%')
			->orWhere('stocks.status','LIKE', '%'.$keyword.'%');
		})
		->where('stocks.branch_id',$request->storeID)
		->select('stocks.*')
		->limit(20)
		->offset($request->start)
		->orderBy('id','DESC')
		->get();

		$totalRecords = DB::table('stocks')
		->where(function ($query) use ($keyword) {
			$query
			->orWhere('stocks.product_name','LIKE','%'.$keyword.'%')
			->orWhere('stocks.generic','LIKE', '%'.$keyword.'%')
			->orWhere('stocks.batch_no','LIKE', '%'.$keyword.'%')
			->orWhere('stocks.status','LIKE', '%'.$keyword.'%');
		})
		->where('stocks.branch_id',$request->storeID)
		->count();

		$brand = OptionTags::where('status', 'Active')
		->where('option_type','Brands')
		->get();

		$brandSector = OptionTags::where('status', 'Active')
		->where('option_type','Brand Sectors')
		->get();

		$category = OptionTags::where('status', 'Active')
		->where('option_type','Category')
		->get();

		$productType = OptionTags::where('status', 'Active')
		->where('option_type','Products Type')
		->get();

		$user =  new User();
		$stores = $user->getUserStores();

		$storeTaxes = Branch::with([
			'taxName1:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName2:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName3:chart_accounts.id,chart_accounts.account_name as chartName',
		])
		->where('id',Auth::user()->branch_id)
		->get();

		return [
			'stores' => $stores,
			'records' => $stocks,
			'limit' => 20,
			'totalRecords' => $totalRecords,
			'storeTaxes' => $storeTaxes,
			'currentStoreID' => Auth::user()->branch_id,
			'productType'   => $productType,
			'brand'         => $brand,
			'brandSector'   => $brandSector,
			'category'      => $category
		];
	}


	public function export()
	{
		return (new StocksExport)->download('sampleData.csv', ed::CSV, ['Content-Type' => 'text/csv']);
	}

	public function importStock(Request $request)
	{
		$request->validate([
			'image' => 'required',
		]);

		$fileRecord = Excel::toArray(null, $request->image);

		return response()->json($fileRecord[0]);
	}

	public function saveStock(Request $request)
	{
		$request->validate([
			'item_list' => 'required',
		]);

		DB::beginTransaction();

		try
		{
			$itemLists = json_decode($request->item_list);

			if($itemLists != NULL)
			{
				foreach($itemLists as $item)
				{
					$product = Stock::where('product_name',$item->productName)
					->where('batch_no',$item->batchNo)
					->first();

					if($product != NULL)
					{
						$response = response()->json([
							'alert' =>'danger',
							'msg'   =>'Product name and Batch No already found'
						]);
					}
					else
					{
						$stock = new Stock([
							'product_name'    	  => strtoupper($item->productName),
							'generic'     	  	  => strtoupper($item->genericName),
							'barcode'	 	 	  => $item->barcode,
							'type'      	  	  => $item->productType,
							'description'         => $item->description,
							'image'        		  => 'default.jpg',
							'brand'      		  => $item->brandName,
							'brand_sector'        => $item->brandSector,
							'category'      	  => $item->category,
							'side_effects'        => $item->sideEffects,
							'expiry_date'     	  => date('Y-m-d',strtotime($item->expiryDate)),
							'qty'	 	 		  => $item->quantity,
							'strip_size'      	  => $item->stripSize,
							'pack_size'      	  => $item->packSize,
							'sale_price'      	  => $item->packSellingPrice,
							'purchase_price'      => $item->packPurchasePrice,
							'mrp'      		 	  => $item->mRP,
							'batch_no'      	  => $item->batchNo,
							'tax_1'      		  => $item->tax_1,
							'tax_2'      		  => $item->tax_2,
							'tax_3'      		  => $item->tax_3,
							'discount_percentage' => $item->discountPercentage,
							'min_stock'      	  => $item->minimumStock,
							'item_location'       => ($item->storeLocations == "" ? 'None' : $item->storeLocations),
							'created_by'      	  => Auth::user()->id,
							'status'      		  => 'Active',
							'branch_id'      	  => Auth::user()->branch_id,
						]);

						$stock->save();

						$response = response()->json([
							'alert' =>'info',
							'msg'   =>'Stock saved Successfully'
						]);
					}
				}
			}
			else
			{
				$response = response()->json([
					'alert' =>'danger',
					'msg'   =>'Stock list is empty cannot upload'
				]);
			}

			DB::commit();
		}
		catch (\Exception $e)
		{
			DB::rollBack();

			$response = response()->json([
				'alert' =>'danger',
				'msg'   => $e
			]);

			throw $e;
		}


		return $response;

	}

	public function update(Request $request)
	{
		$request->validate([
			'id'     				=> ['required'],
			'product_name'          => ['required'],
			'generic'               => ['required'],
			'type'                  => ['required'],
			'brand'                 => ['required'],
			'brand_sector'          => ['required'],
			'category'              => ['required'],
			'pack_size'             => ['required'],
			'strip_size'            => ['required'],
			'expiry_date'     		=> ['required'],
			'qty'             		=> ['required'],
			'sale_price'         	=> ['required'],
			'pack_size'        		=> ['required'],
			'mrp'       			=> ['required'],
			'batch_no' 			    => ['required'],
			'tax_1' 			    => ['required'],
			'tax_2' 			    => ['required'],
			'tax_3' 			    => ['required'],
			'discount_percentage' 	=> ['required'],
			'min_stock' 			=> ['required'],
			'item_location' 		=> ['required']
		]);

		$stock = Stock::find($request->id);
		$stock->update($request->all());

		return response()->json([
			'alert' =>'info',
			'msg'=>'Stock Item Updated Successfully'
		]);
	}

	public function show($id)
	{
		$stocks = DB::table('stocks')
		->where('stocks.id',$id)
		->select('stocks.*')
		->first();

		return response()->json($stocks);
	}
	
	public function searchItems(Request $request)
	{
		$keyword = $request['keyword'];

		$stocks = DB::table('stocks')
		->join('option_tags as brand', 'brand.id', '=', 'stocks.brand')
		->join('option_tags as brand_sector', 'brand_sector.id', '=', 'stocks.brand_sector')
		->join('option_tags as category', 'category.id', '=', 'stocks.category')
		->join('option_tags as type', 'type.id', '=', 'stocks.type')
		->where('stocks.branch_id',Auth::user()->branch_id)
		->where(function($query) use ($keyword)
		{
			$query->where('stocks.product_name','LIKE','%'.$keyword.'%')
			->orWhere('stocks.generic', 'LIKE', '%'.$keyword.'%')
			->orWhere('stocks.batch_no', '=', $keyword)
			->orWhere('stocks.barcode', '=', $keyword);
		})
		->select(
			'stocks.*',
			'stocks.product_name',
			'stocks.generic',
			'stocks.description',
			'brand.option_name as bName',
			'brand_sector.option_name as bSector',
			'category.option_name as cName',
			'type.option_name as pType',
			)
		->limit(20)
		->orderBy('id','DESC')
		->get();

		return [
			'records' => $stocks
		];
	}


}

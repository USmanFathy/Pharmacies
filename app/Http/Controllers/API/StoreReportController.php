<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\DateFilters;
use App\Models\AccountStatement;
use App\Models\OptionTags;
use App\Models\Branch;
use App\Models\Transaction;
use App\Models\BankTransaction;
use App\Models\Stock;
use App\Models\ActivityLog;
use App\Models\Banks;
use App\Models\Profiler;

class StoreReportController extends Controller
{
	public function index()
	{
		$dt = new DateFilters();
		$datesList = $dt->get('filterList');

		$user =  new User(); 
		$stores = $user->getUserStores();

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
			'datesList'    => $datesList,
			'productTypes' => $productType,
			'brands'       => $brand,
			'brandSector'  => $brandSector,
			'categories'   => $category,
			'stores' 	   => $stores,
			'storeTaxes'   => $storeTaxes,
		];
	}

	public function saleReturn(Request $request)
	{

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		if($filters->type == 'Sales')
		{
			$reportType = 'INE';
		}
		else
		{
			$reportType = 'RFD';
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$record = DB::table('pos_receipts')
        ->join('pos_sub_receipts', 'pos_receipts.id', '=', 'pos_sub_receipts.pos_receipt_id')
        ->join('profilers', 'profilers.id', '=', 'pos_receipts.profile_id')
        ->join('users', 'users.id', '=', 'pos_receipts.created_by')
        ->join('branches', 'branches.id', '=', 'pos_receipts.branch_id')
        ->whereDate('pos_receipts.created_at','>=',$date1)
        ->whereDate('pos_receipts.created_at','<=',$date2)
        ->where('pos_receipts.type','=',$reportType)
        ->where('pos_receipts.branch_id','=',$filters->storeID)
		->where(function ($query) use($filters) {
			if($filters->brandName != 'All')
			{
				$query->where('pos_sub_receipts.brand_name','=',$filters->brandName);
			}

			if($filters->sectorName != 'All')
			{
				$query->where('pos_sub_receipts.sector_name','=',$filters->sectorName);
			}

			if($filters->categoryName != 'All')
			{
				$query->where('pos_sub_receipts.category_name','=',$filters->categoryName);
			}
			
			if($filters->productType != 'All')
			{
				$query->where('pos_sub_receipts.product_type','=',$filters->productType);
			}

			if($filters->customerID != 0)
			{
				$query->where('pos_receipts.profile_id','=',$filters->customerID);
			}
			
			if($filters->userID != 0)
			{
				$query->where('pos_receipts.created_by','=',$filters->userID);
			}

			if($filters->batchNo != '')
			{
				$query->where('pos_sub_receipts.batch_no','=',$filters->batchNo);
			}

		})
        ->select(DB::raw(
			'branches.name as branch_name,
			 branches.code as branch_code,
			 profilers.account_title as customer_name,
			 profilers.contact_no as customer_contact,
			 users.name as user_name,
			 users.contact as user_contact,
			 pos_receipts.receipt_no,
			 pos_receipts.receipt_date,
			 pos_sub_receipts.*
			'))
        ->get();

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => $record,
		];
	}
	
	public function taxReport(Request $request)
	{

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		if($filters->type == 'Sales')
		{
			$reportType = 'INE';
		}
		else if($filters->type == 'Refund')
		{
			$reportType = 'RFD';
		}
		else if($filters->type == 'Transfer')
		{
			$reportType = 'TRN';
		}
		else if($filters->type == 'Purchase')
		{
			$reportType = 'PUR';
		}
		else if($filters->type == 'Purchase Return')
		{
			$reportType = 'RPU';
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$record = DB::table('pos_receipts')
		->join('pos_sub_receipts', 'pos_receipts.id', '=', 'pos_sub_receipts.pos_receipt_id')
		->join('profilers', 'profilers.id', '=', 'pos_receipts.profile_id')
		->join('users', 'users.id', '=', 'pos_receipts.created_by')
		->join('branches', 'branches.id', '=', 'pos_receipts.branch_id')
		->whereDate('pos_receipts.created_at','>=',$date1)
		->whereDate('pos_receipts.created_at','<=',$date2)
		->where('pos_receipts.type','=',$reportType)
		->where('pos_receipts.branch_id','=',$filters->storeID)
		->where(function ($query) use($filters) {
			if($filters->brandName != 'All')
			{
				$query->where('pos_sub_receipts.brand_name','=',$filters->brandName);
			}

			if($filters->sectorName != 'All')
			{
				$query->where('pos_sub_receipts.sector_name','=',$filters->sectorName);
			}

			if($filters->categoryName != 'All')
			{
				$query->where('pos_sub_receipts.category_name','=',$filters->categoryName);
			}
			
			if($filters->productType != 'All')
			{
				$query->where('pos_sub_receipts.product_type','=',$filters->productType);
			}

			if($filters->customerID != 0)
			{
				$query->where('pos_receipts.profile_id','=',$filters->customerID);
			}
			
			if($filters->userID != 0)
			{
				$query->where('pos_receipts.created_by','=',$filters->userID);
			}

			if($filters->batchNo != '')
			{
				$query->where('pos_sub_receipts.batch_no','=',$filters->batchNo);
			}

		})
		->select(DB::raw(
			'branches.name as branch_name,
			branches.code as branch_code,
			profilers.account_title as customer_name,
			profilers.contact_no as customer_contact,
			users.name as user_name,
			users.contact as user_contact,
			pos_receipts.receipt_no,
			pos_receipts.receipt_date,
			pos_sub_receipts.*
			'))
		->get();

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => $record,
		];
	}
	
	public function purchasing(Request $request)
	{

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		if($filters->type == 'Purchase')
		{
			$reportType = 'PUR';
		}
		else
		{
			$reportType = 'RPU';
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$record = DB::table('pos_receipts')
        ->join('pos_sub_receipts', 'pos_receipts.id', '=', 'pos_sub_receipts.pos_receipt_id')
        ->join('profilers', 'profilers.id', '=', 'pos_receipts.profile_id')
        ->join('users', 'users.id', '=', 'pos_receipts.created_by')
        ->join('branches', 'branches.id', '=', 'pos_receipts.branch_id')
        ->whereDate('pos_receipts.created_at','>=',$date1)
        ->whereDate('pos_receipts.created_at','<=',$date2)
        ->where('pos_receipts.type','=',$reportType)
        ->where('pos_receipts.branch_id','=',$filters->storeID)
		->where(function ($query) use($filters) {
			if($filters->brandName != 'All')
			{
				$query->where('pos_sub_receipts.brand_name','=',$filters->brandName);
			}

			if($filters->sectorName != 'All')
			{
				$query->where('pos_sub_receipts.sector_name','=',$filters->sectorName);
			}

			if($filters->categoryName != 'All')
			{
				$query->where('pos_sub_receipts.category_name','=',$filters->categoryName);
			}
			
			if($filters->productType != 'All')
			{
				$query->where('pos_sub_receipts.product_type','=',$filters->productType);
			}

			if($filters->customerID != 0)
			{
				$query->where('pos_receipts.profile_id','=',$filters->customerID);
			}
			
			if($filters->userID != 0)
			{
				$query->where('pos_receipts.created_by','=',$filters->userID);
			}

			if($filters->batchNo != '')
			{
				$query->where('pos_sub_receipts.batch_no','=',$filters->batchNo);
			}

		})
        ->select(DB::raw(
			'branches.name as branch_name,
			 branches.code as branch_code,
			 profilers.account_title as customer_name,
			 profilers.contact_no as customer_contact,
			 users.name as user_name,
			 users.contact as user_contact,
			 pos_receipts.receipt_no,
			 pos_receipts.bill_no,
			 pos_receipts.receipt_date,
			 pos_sub_receipts.*
			'))
        ->get();

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => $record,
		];
	}
	
	public function transfer(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		if($filters->type == 'Transfer')
		{
			$reportType = 'TRN';
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$record = DB::table('pos_receipts')
        ->join('pos_sub_receipts', 'pos_receipts.id', '=', 'pos_sub_receipts.pos_receipt_id')
        ->join('profilers', 'profilers.id', '=', 'pos_receipts.profile_id')
        ->join('users', 'users.id', '=', 'pos_receipts.created_by')
        ->join('branches', 'branches.id', '=', 'pos_receipts.branch_id')
        ->join('transfer_stores', 'transfer_stores.receipt_id', '=', 'pos_receipts.id')
        ->join('branches as tb', 'transfer_stores.branch_id', '=', 'tb.id')
        ->whereDate('pos_receipts.created_at','>=',$date1)
        ->whereDate('pos_receipts.created_at','<=',$date2)
        ->where('pos_receipts.type','=',$reportType)
        ->where('pos_receipts.branch_id','=',$filters->storeID)
		->where(function ($query) use($filters) {
			if($filters->customerID != 0)
			{
				$query->where('pos_receipts.profile_id','=',$filters->customerID);
			}
			
			if($filters->userID != 0)
			{
				$query->where('pos_receipts.created_by','=',$filters->userID);
			}
		})
        ->select(DB::raw(
			'branches.name as branch_name,
			 branches.code as branch_code,
			 tb.name as tb_name,
			 tb.code as tb_code,
			 profilers.account_title as customer_name,
			 profilers.contact_no as customer_contact,
			 users.name as user_name,
			 users.contact as user_contact,
			 pos_receipts.receipt_no,
			 pos_receipts.receipt_date,
			 pos_sub_receipts.*
			'))
        ->get();

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => $record,
		];
	}

	public function performance(Request $request)
	{

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		if($filters->dimension == 'Purchase')
		{
			$reportType = 'PUR';
		}
		else
		{
			$reportType = 'RPU';
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		if($filters->dimension == 'Customer')
		{
			$res = DB::table('pos_receipts')
			->join('profilers', 'profilers.id', '=','pos_receipts.profile_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_receipts.total_bill) as total_amount,
				profilers.account_title as customer_name,
				profilers.contact_no as customer_contact,
				profilers.email_address as email_address'
			))
			//->having(SUM('pos_receipts.total_bill'),$filters->condition,$filters->amountValue)
			->havingRaw("SUM(pos_receipts.total_bill) $filters->condition $filters->amountValue")
			->groupBy('profilers.id')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'customer_name' 		=> $r->customer_name,
						'customer_contact' 		=> $r->customer_contact,
						'email_address'  		=> $r->email_address,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'Stores')
		{
			$res = DB::table('pos_receipts')
			->join('branches', 'branches.id', '=','pos_receipts.branch_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_receipts.total_bill) as total_amount,
				branches.name as name,
				branches.code as code,
				branches.email as email,
				branches.contact as contact'
			))
			->havingRaw("SUM(pos_receipts.total_bill) $filters->condition $filters->amountValue")
			->groupBy('branches.id')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'code' 				    => $r->code,
						'name' 				    => $r->name,
						'contact' 				=> $r->contact,
						'email'  				=> $r->email,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'User')
		{
			$res = DB::table('pos_receipts')
			->join('users', 'users.id', '=','pos_receipts.created_by')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_receipts.total_bill) as total_amount,
				users.name as name,
				users.email as email,
				users.contact as contact'
			))
			->havingRaw("SUM(pos_receipts.total_bill) $filters->condition $filters->amountValue")
			->groupBy('users.id')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'name' 				    => $r->name,
						'contact' 				=> $r->contact,
						'email'  				=> $r->email,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'Category')
		{
			$res = DB::table('pos_receipts')
			->join('pos_sub_receipts', 'pos_receipts.id', '=','pos_sub_receipts.pos_receipt_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_sub_receipts.sub_total) as total_amount,
				SUM(pos_sub_receipts.total_unit) as total_qty,
				pos_sub_receipts.category_name as name'
			))
			->havingRaw("SUM(pos_sub_receipts.sub_total) $filters->condition $filters->amountValue")
			->groupBy('name')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'name' 				    => $r->name,
						'total_qty'  			=> $r->total_qty,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'Brand Sector')
		{
			$res = DB::table('pos_receipts')
			->join('pos_sub_receipts', 'pos_receipts.id', '=','pos_sub_receipts.pos_receipt_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_sub_receipts.sub_total) as total_amount,
				SUM(pos_sub_receipts.total_unit) as total_qty,
				pos_sub_receipts.sector_name as name'
			))
			->havingRaw("SUM(pos_sub_receipts.sub_total) $filters->condition $filters->amountValue")
			->groupBy('name')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'name' 				    => $r->name,
						'total_qty'  			=> $r->total_qty,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'Brand')
		{
			$res = DB::table('pos_receipts')
			->join('pos_sub_receipts', 'pos_receipts.id', '=','pos_sub_receipts.pos_receipt_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_sub_receipts.sub_total) as total_amount,
				SUM(pos_sub_receipts.total_unit) as total_qty,
				pos_sub_receipts.brand_name as name'
			))
			->havingRaw("SUM(pos_sub_receipts.sub_total) $filters->condition $filters->amountValue")
			->groupBy('name')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'name' 				    => $r->name,
						'total_qty'  			=> $r->total_qty,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else if($filters->dimension == 'Product Type')
		{
			$res = DB::table('pos_receipts')
			->join('pos_sub_receipts', 'pos_receipts.id', '=','pos_sub_receipts.pos_receipt_id')
			->whereDate('pos_receipts.created_at','>=',$date1)
			->whereDate('pos_receipts.created_at','<=',$date2)
			->where('pos_receipts.type','=',$filters->reportType->value)
			->where('pos_receipts.branch_id','=',$filters->storeID)
			->select(DB::raw(
				'SUM(pos_sub_receipts.sub_total) as total_amount,
				SUM(pos_sub_receipts.total_unit) as total_qty,
				pos_sub_receipts.product_type as name'
			))
			->havingRaw("SUM(pos_sub_receipts.sub_total) $filters->condition $filters->amountValue")
			->groupBy('name')  	
			->limit($filters->limit)  	
			->orderBy('total_amount', $filters->sort)
			->get();

			$list = [];
			if($res != NULL)
			{
				foreach($res as $k => $r)
				{
					$list [] = array(
						'ctr' 		 		    => $k+1,
						'name' 				    => $r->name,
						'total_qty'  			=> $r->total_qty,
						'total_amount' 			=> $r->total_amount
					);
				}
			}
		}
		else
		{
			$res = [];	
		}

		return [
			'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
			'record' => $list,
		];

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => [],
		];
	}

	public function generalJournal(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}


		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$storeName = "";

		if($filters->storeType == 'single_store')
		{
			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;

			$record = Transaction::with([
				'transactionEntries:sub_transactions.transaction_id,sub_transactions.account_name,sub_transactions.amount,sub_transactions.type',
				'branchName:id,name,code'
			])
			->where('branch_id',$filters->storeID)
			->whereDate('created_at','>=', $date1)
			->whereDate('created_at','<=', $date2)
			->orderBy('id','DESC')
			->get();
		}
		else
		{
			$record = Transaction::with([
				'transactionEntries:sub_transactions.transaction_id,sub_transactions.account_name,sub_transactions.amount,sub_transactions.type',
				'branchName:id,name,code'
			])
			->whereDate('created_at','>=', $date1)
			->whereDate('created_at','<=', $date2)
			->orderBy('id','DESC')
			->get();
		}


		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'storeName' => $storeName,
            'record' => $record,
		];
	}
	
	public function incomeStatement(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$storeName = "";

		if($filters->storeType == 'single_store')
		{

			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;

			$record = DB::select(
				DB::raw("SELECT MIN(sub_transactions.account_name) AS account_name,MIN(chart_accounts.account_nature) AS account_nature, 
				(SUM(CASE WHEN sub_transactions.type = 'Credit' AND chart_accounts.account_nature = 'Revenue' THEN sub_transactions.amount ELSE 0  END) -
				SUM(CASE WHEN sub_transactions.type = 'Debit' AND chart_accounts.account_nature = 'Revenue' THEN sub_transactions.amount  ELSE 0  END)) as 
				'total_revenue',
				(SUM(CASE WHEN sub_transactions.type = 'Debit' AND chart_accounts.account_nature = 'Expense' THEN sub_transactions.amount ELSE 0  END) -
				SUM(CASE WHEN sub_transactions.type = 'Credit' AND chart_accounts.account_nature = 'Expense' THEN sub_transactions.amount  ELSE 0  END)) AS 
				'total_expense' 
				FROM sub_transactions 
				JOIN transactions ON transactions.id = sub_transactions.transaction_id
				JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
				WHERE transactions.branch_id = :store_id  AND  transactions.created_at BETWEEN :date1 AND  :date2 AND (chart_accounts.account_nature = 'Revenue' OR chart_accounts.account_nature = 'Expense')
				GROUP BY sub_transactions.account_id"), array(
					'date1' => $date1,
					'date2'  => $date2,
					'store_id'   => $filters->storeID,
				));
		}
		else
		{
			$record = DB::select(
			DB::raw("SELECT MIN(sub_transactions.account_name) AS account_name,MIN(chart_accounts.account_nature) AS account_nature, 
			(SUM(CASE WHEN sub_transactions.type = 'Credit' AND chart_accounts.account_nature = 'Revenue' THEN sub_transactions.amount ELSE 0  END) -
			SUM(CASE WHEN sub_transactions.type = 'Debit' AND chart_accounts.account_nature = 'Revenue' THEN sub_transactions.amount  ELSE 0  END)) as 
			'total_revenue',
			(SUM(CASE WHEN sub_transactions.type = 'Debit' AND chart_accounts.account_nature = 'Expense' THEN sub_transactions.amount ELSE 0  END) -
			SUM(CASE WHEN sub_transactions.type = 'Credit' AND chart_accounts.account_nature = 'Expense' THEN sub_transactions.amount  ELSE 0  END)) AS 
			'total_expense' 
			FROM sub_transactions 
			JOIN transactions ON transactions.id = sub_transactions.transaction_id
			JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
			WHERE  transactions.created_at BETWEEN :date1 AND  :date2 AND (chart_accounts.account_nature = 'Revenue' OR chart_accounts.account_nature = 'Expense')
			GROUP BY sub_transactions.account_id"), array(
				'date1' => $date1,
				'date2'  => $date2,
				));
		}	

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'storeName' => $storeName,
            'record' => $record,
		];
	}
	
	public function trialBalance(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}


		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$storeName = "";

		if($filters->storeType == 'single_store')
		{

			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;

			$record = DB::select(
				DB::raw("SELECT MIN(sub_transactions.account_name) AS account_name,MIN(chart_accounts.account_nature) AS account_nature, 
				SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) as 'total_debit',
				SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END) as 'total_credit'
				FROM sub_transactions 
				JOIN transactions ON transactions.id = sub_transactions.transaction_id
				JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
				WHERE transactions.branch_id = :store_id  AND  transactions.created_at BETWEEN :date1 AND  :date2
				GROUP BY sub_transactions.account_id"), array(
					'date1' 	 => $date1,
					'date2'  	 => $date2,
					'store_id'   => $filters->storeID,
				));
		}
		else
		{
			$record = DB::select(
				DB::raw("SELECT MIN(sub_transactions.account_name) AS account_name,MIN(chart_accounts.account_nature) AS account_nature, 
				SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) as 'total_debit',
				SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END) as 'total_credit'
				FROM sub_transactions 
				JOIN transactions ON transactions.id = sub_transactions.transaction_id
				JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
				WHERE  transactions.created_at BETWEEN :date1 AND  :date2
				GROUP BY sub_transactions.account_id"), array(
					'date1' 	 => $date1,
					'date2'  	 => $date2
				));
		}	

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'storeName'   => $storeName,
            'record' 	  => $record,
		];
	}
	
	public function ledgerStatement(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$storeName = "";

		if($filters->storeType == 'single_store')
		{

			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;

			$record = DB::table('transactions')
				->join('sub_transactions', 'transactions.id', '=','sub_transactions.transaction_id')
				->join('chart_accounts','chart_accounts.id', '=','sub_transactions.account_id')
				->whereDate('sub_transactions.created_at','>=',$date1)
				->whereDate('sub_transactions.created_at','<=',$date2)
				->where('transactions.branch_id','=',$filters->storeID)
				->where('chart_accounts.account_nature','=',$filters->reportType)
				->select(
					'transactions.narration',
					'transactions.generated_source',
					'chart_accounts.account_code',
					'sub_transactions.*',
				)
				->orderBy('transactions.id', 'ASC')
				->get();

			$previous_record = DB::select(
				DB::raw("SELECT MIN(sub_transactions.account_id) AS account_id, 
				SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) as 'total_debit',
				SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END) as 'total_credit'
				FROM sub_transactions 
				JOIN transactions ON transactions.id = sub_transactions.transaction_id
				JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
				WHERE transactions.branch_id = :store_id  AND  sub_transactions.created_at < :date1 AND  chart_accounts.account_nature = :nature
				GROUP BY sub_transactions.account_id"), array(
					'date1' 	 => $date1,
					'nature' 	 => $filters->reportType,
					'store_id'   => $filters->storeID,
				));
		}
		else
		{
			$record = DB::table('transactions')
			->join('sub_transactions', 'transactions.id', '=','sub_transactions.transaction_id')
			->join('chart_accounts','chart_accounts.id', '=','sub_transactions.account_id')
			->whereDate('sub_transactions.created_at','>=',$date1)
			->whereDate('sub_transactions.created_at','<=',$date2)
			->where('chart_accounts.account_nature','=',$filters->reportType)
			->select(
				'transactions.narration',
				'transactions.generated_source',
				'chart_accounts.account_code',
				'sub_transactions.*',
			)
			->orderBy('transactions.id', 'ASC')
			->get();

		$previous_record = DB::select(
			DB::raw("SELECT MIN(sub_transactions.account_id) AS account_id, 
			SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) as 'total_debit',
			SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END) as 'total_credit'
			FROM sub_transactions 
			JOIN transactions ON transactions.id = sub_transactions.transaction_id
			JOIN chart_accounts ON chart_accounts.id = sub_transactions.account_id 
			WHERE  sub_transactions.created_at < :date1 AND  chart_accounts.account_nature = :nature
			GROUP BY sub_transactions.account_id"), array(
				'date1' 	 => $date1,
				'nature' 	 => $filters->reportType
			));
		}
		

		return [
            'resultTitle' 		=> 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'previousDate'   	=> date('D, d M  Y',strtotime($date1)),
            'storeName'   		=> $storeName,
            'record' 	  		=> $record,
            'previous_record' 	=> $previous_record
		];
	}

	public function bankStatement(Request $request)
	{

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$m = $dt->getTheMonthDates();
		$date1 = $m[0];
		$date2 = $m[1];

		$storeName = "";

		//BANK INFO
		$bankInfo = Banks::where('id',$filters->bank->id)->first();

		if($filters->storeType == 'single_store')
		{

			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;
			
			$record = BankTransaction::with([
				'profileName:profilers.id,profilers.account_title as profileName',
			])
			->where('bank_id',$filters->bank->id)
			->where('branch_id',$filters->storeID)
			->whereDate('receipt_date','>=', $date1)
			->whereDate('receipt_date','<=', $date2)
			->orderBy('id','DESC')
			->get();

			$previous_bank = DB::select(
				DB::raw("SELECT (SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) - 
				SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END)) as 'total_bank'
				FROM sub_transactions 
				JOIN transactions ON transactions.id = sub_transactions.transaction_id
				WHERE transactions.branch_id = :store_id  AND  transactions.created_at < :date1 AND  sub_transactions.account_id = :account_id
				GROUP BY sub_transactions.account_id"), array(
					'date1' 	 => $date1,
					'store_id'   => $filters->storeID,
					':account_id' => 8, 
				));
		}
		else
		{
			$record = BankTransaction::with([
				'profileName:profilers.id,profilers.account_title as profileName',
			])
			->where('bank_id',$filters->bank->id)
			->whereDate('receipt_date','>=', $date1)
			->whereDate('receipt_date','<=', $date2)
			->orderBy('id','DESC')
			->get();

			$previous_bank = DB::select(
			DB::raw("SELECT (SUM(CASE WHEN sub_transactions.type = 'Debit' THEN sub_transactions.amount ELSE 0  END) - 
			SUM(CASE WHEN sub_transactions.type = 'Credit' THEN sub_transactions.amount  ELSE 0  END)) as 'total_bank'
			FROM sub_transactions 
			JOIN transactions ON transactions.id = sub_transactions.transaction_id
			WHERE  transactions.created_at < :date1 AND  sub_transactions.account_id = :account_id
			GROUP BY sub_transactions.account_id"), array(
				'date1' 	 => $date1,
				':account_id' => 8, 
			));
		}	
		
		if($previous_bank != NULL)
		{
			$beforeStatementAmount = $previous_bank[0]->total_bank;
		}
		else
		{
			$beforeStatementAmount = 0;
		}	  

		return [
            'resultTitle' 	              => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'beforeStatement'             => date('D, d M  Y',strtotime($date1)),
            'endStatement' 	              => date('D, d M  Y',strtotime($date2)),
            'beforeStatementAmount' 	  => $beforeStatementAmount,
            'record' 		  			  => $record,
			'storeName'   				  => $storeName,
            'openingBalance' 		  	  => $bankInfo->balance,
            'openingBalanceDate' 		  => $bankInfo->ending_date,
		];
	}

	public function accountStatement(Request $request)
	{
		$account_holder = '';
		$account_holder_phone = '';

		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}
		
		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		$as = new AccountStatement();

		$previous_date  = date('Y-m-d', strtotime( $date1 . ' -1 day' ) );

		$as->set('date1','2021-01-01');
		$as->set('date2',$previous_date);
		$as->set('storeID',$filters->storeID);
		$as->set('profileID',$filters->profileID);

		$prev_list = $as->get_user_transactions();
		$previous_balance = $as->sum_user_list_balance($prev_list);

		$as->set('date1',$date1);
		$as->set('date2',$date2);
		$as->set('storeID',$filters->storeID);
		$as->set('profileID',$filters->profileID);
		$as->set('totalBalance',$previous_balance);

		$t = $as->get_user_transactions();
	

		$storeName = "";

		if($filters->profileID != 0)
		{
			$profilerInfo = Profiler::find($filters->profileID);
			$account_holder = $profilerInfo->account_title;
			$account_holder_phone = $profilerInfo->contact_no;


			$storeInfo = Branch::find($filters->storeID);
			$storeName = $storeInfo->name;

		}

		return [
            'resultTitle' 				  => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' 		  			  => $t,
            'account_holder' 		  	  => $account_holder,
            'account_holder_phone' 		  => $account_holder_phone,
            'store_name' 		  		  => $storeName,
            'before_balance' 		  	  => $previous_balance,
		];
	
		
	}

	public function stockExpiryReport(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$cMonth = date('Y-m').'-31';

		$record = Stock::with([
			'branchDetails'
		])
		->where('branch_id','=', $filters->storeID)
		->where('status','=', 'Active')
		->whereDate('expiry_date','<',$cMonth)
		->orderBy('id','DESC')
		->get();

		return [
            'resultTitle' => '',
            'record' => $record,
		];
	}
	
	public function userReport(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$record = User::join('branches','users.branch_id','=','branches.id')
		->join('roles', 'users.role', '=', 'roles.id')
		->where('users.status','Active')
		->where('users.branch_id',$filters->storeID)
		->orderBy('users.id','DESC')
		->get(['users.*','branches.name as branchName','branches.code as branchCode','roles.name as roleName']);

		return [
            'resultTitle' => '',
            'record' => $record,
		];
	}

	public function stockReport(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$record = Stock::with([
			'branchDetails'
		])
		->where('branch_id','=', $filters->storeID)
		->orderBy('id','DESC')
		->get();

		return [
            'resultTitle' => '',
            'record' => $record,
		];
	}
	
	public function stockAlertReport(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$record = Stock::with([
			'branchDetails'
		])
		->where('branch_id','=', $filters->storeID)
		->whereRaw('qty <= min_stock')
		->orderBy('id','DESC')
		->get();

		return [
            'resultTitle' => '',
            'record' => $record,
		];
	}

	public function userActivityReport(Request $request)
	{
		$filters = json_decode($request->filters);
	
		if($filters->storeID == 0)
		{
			$filters->storeID  = Auth::user()->branch_id;
		}

		$dt = new DateFilters();
		
		$dt->set('filter',$filters->filterType);
		$dt->set('date1',$filters->date1);
		$dt->set('date2',$filters->date2);
		$date1 = $dt->getTheDates()[0];
		$date2 = $dt->getTheDates()[1];

		
		$record = ActivityLog::with([
			'userDetails',
			'branchDetails'
		])
		->whereDate('created_at','>=', $date1)
		->whereDate('created_at','<=', $date2)
		->where('branch_id','=', $filters->storeID)
		->orderBy('id','DESC')
		->get();
		

		return [
            'resultTitle' => 'From '.date('D, d M  Y',strtotime($date1)).' -To- '.date('D, d M  Y',strtotime($date2)),
            'record' => $record,
		];
	}
}
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Branch;
use App\Models\PosReceipt;
use App\Models\ThermalReceipt;
use App\Models\Transaction;
use App\Models\SubTransaction;
use App\Models\PosSubReceipt;
use App\Models\ReceiptPayment;
use App\Models\Stock;
use App\Models\TransferStore;
use App\Models\Profiler;
use App\Models\DateFilters;
use App\Models\Printer;
use App\Models\PrinterReceipt;
use App\Models\SubReceipt;
use Illuminate\Support\Facades\Config;

class PosController extends Controller
{
    public function index()
    {

        $user =  new User(); 
        $stores = $user->getUserStores();
        
        $storeTaxes = Branch::with([
            'taxName1:chart_accounts.id,chart_accounts.account_name as chartName',
            'taxName2:chart_accounts.id,chart_accounts.account_name as chartName',
            'taxName3:chart_accounts.id,chart_accounts.account_name as chartName',
        ])
        ->where('id',Auth::user()->branch_id)
        ->get();

        $defaultCustomer = Profiler::where('account_type','Default Customer')
        ->first();
        
        return [
            'defaultCustomer' => $defaultCustomer,
            'storeTaxes' => $storeTaxes,
            'stores' => $stores,
            'currentUserID' => Auth::user()->id,
            'storeName' => $storeTaxes[0]->name
        ];
    } 

    public function saveReceipt(Request $request)
    {
        $request->validate([
            'profile_id'   		 => ['required'],
            'payment_list'   	 => ['required'],
            'discount'   		 => ['required'],
            'total_tendered'   	 => ['required'],
            'total_change'   	 => ['required'],
            'total_gross_amt'    => ['required'],
            'total_bill'   		 => ['required'],
            'total_tax1'   		 => ['required'],
            'total_tax2'   		 => ['required'],
            'total_tax3'   		 => ['required'],
            'total_tax'   		 => ['required'],
            'payment_method'   	 => ['required'],
            'status'  			 => ['required'],
            'type'   			 => ['required'],
            'item_list'   		 => ['required'],
            'counter_entry'      => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
            $itemLists = json_decode($request->item_list);
            $counterEntry = json_decode($request->counter_entry);
            $paymentLists = json_decode($request->payment_list);

            if($itemLists != NULL)
            {

                $narration = ($request->description == '' ? 'Transaction occurred from pos screen' : $request->description);
                    
                $transaction = new Transaction([
                    'narration'         => $narration,
                    'generated_source'  => $request->type,
                    'branch_id'         => Auth::user()->branch_id,
                ]);
                
                $transaction->save();

                foreach($counterEntry as $item)
                {
                    $subTransaction = new SubTransaction([
                        'transaction_id'     => $transaction->id,
                        'account_id'     	 => $item->accountID,
                        'account_name'	 	 => $item->accountHead,
                        'amount'      	     => $item->amount,
                        'type'      		 => $item->type,
                    ]);
                    
                    $subTransaction->save();
                }
                
                $t = new  PosReceipt();
                $receiptNo =  $t->generateID($request->type);
            
                $receiptItem 			= new PosReceipt([
                    'transaction_id' 	=> $transaction->id,
                    'receipt_no'     	=> $receiptNo,
                    'discount'   		=> $request->discount,
                    'profile_id' 		=> $request->profile_id,
                    'payment_method'    => $request->payment_method,
                    'total_gross_amt'   => $request->total_gross_amt,
                    'total_bill' 		=> $request->total_bill,
                    'total_tendered'   	=> $request->total_tendered,
                    'total_change'   	=> $request->total_change,
                    'total_tax1'   	 	=> $request->total_tax1,
                    'total_tax2'   	 	=> $request->total_tax2,
                    'total_tax3'   	 	=> $request->total_tax3,
                    'total_tax'   	 	=> $request->total_tax,
                    'description'   	=> $request->description,
                    'doctor_details'   	=> $request->doctor_details,
                    'patient_details'   => $request->patient_details,
                    'bill_no'           => '',
                    'created_by'      	=> Auth::user()->id,
                    'receipt_date'      => date('Y-m-d'),
                    'return_receipt'    => $request->search_receipt_no,
                    'type'      		=> $request->type,
                    'status'         	=> $request->status,
                    'branch_id'      	=> Auth::user()->branch_id,
                ]);

                $receiptItem->save();

                //IF USED CARDS
                $t->passBankTransaction($transaction->id,$receiptNo,$narration,$paymentLists,$request->profile_id);

                foreach($itemLists as $item)
                {
                    $PosSubReceipt = new PosSubReceipt([
                        'pos_receipt_id'    => $receiptItem->id,
                        'mode'    			=> $item->mode,
                        'stock_id'    		=> $item->stockID,
                        'item_name'       	=> $item->productName,
                        'generic_name'    	=> $item->generic,
                        'item_description'  => $item->itemDescription,
                        'unit'        		=> $item->unit,
                        'total_unit'        => $item->totalUnit,
                        'free_unit'   		=> $item->freeUnit,
                        'supplier_bonus'   	=> $item->supplierBonus,
                        'batch_no'   		=> $item->batchNo,
                        'pack_size'   		=> $item->packSize,
                        'sheet_size'   		=> $item->sheetSize,
                        'purchase_price'   	=> $item->purchasePrice,
                        'selling_price'   	=> $item->sellingPrice,
                        'mrp'   			=> $item->mrp,
                        'brand_name'   		=> $item->brandName,
                        'sector_name'   	=> $item->sectorName,
                        'category_name'   	=> $item->categoryName,
                        'product_type'   	=> $item->productType,
                        'expiry_date'   	=> Date('Y-m-d',strtotime($item->expiryDate)),
                        'item_disc'   		=> $item->itemDisc,
                        'purchase_disc'   	=> 0,
                        'after_disc'   	    => 0,
                        'tax_1'   			=> $item->tax1,
                        'tax_2'   			=> $item->tax2,
                        'tax_3'   			=> $item->tax3,
                        'sub_total'   	    => $item->subTotal,
                    ]);
                    
                    $PosSubReceipt->save();

                    //MANAGING STOCKS
                    $s = new Stock();
                    $s->addReduceStock($item->stockID,$item->totalUnit,$request->type);
                }
                
                if($paymentLists != NULL)
                {
                    foreach($paymentLists as $item)
                    {
                        $payments = new ReceiptPayment([
                            'transaction_id'    	=> $transaction->id,
                            'receipt_id'    		=> $receiptItem->id,
                            'account_no'    		=> $item->accountNo,
                            'auth_code'    			=> $item->authCode,
                            'card_balance'	 		=> $item->cardBalance,
                            'change'       			=> $item->change,
                            'entry_mode'    		=> $item->entryMode,
                            'gift_card_ref'  		=> $item->giftCardRef,
                            'host_response'        	=> $item->hostResponse,
                            'payment_type'        	=> $item->paymentType,
                            'round_off'   			=> $item->roundOff,
                            'tendered'   			=> $item->tendered,
                            'terminal_id'   		=> $item->terminalId,
                            'trans_amount'   		=> $item->transAmount,
                            'trans_date'   			=> date('Y-m-d'),
                            'trans_id'   			=> $item->transId,
                            'trans_ref'   			=> $item->transRef,
                            'trans_status'   		=> $item->transStatus,
                            'trans_time'   			=> date('H:i:s'),
                            'trans_total_amount'   	=> $item->transTotalAmount,
                            'trans_type'   			=> $item->transType,
                            'source_type'   	    => $request->type,
                            'description'   	    => $narration,
                            'receipt_no'   	    	=> $receiptNo,
                            'created_by'   	    	=> Auth::user()->id,
                            'branch_id'   	    	=> Auth::user()->branch_id,
                        ]);
                        
                        $payments->save();
                    }
                }

                //ADD TRANSFER STORE IF TRANSFER
                if($request->type == 'TRN')
                {
                    $stockStore = new TransferStore([
                        'receipt_id'   => $receiptItem->id,
                        'branch_id'   => $request->transfer_store_id,
                    ]);
                    
                    $stockStore->save();
                }


                //get the thermal receipt
                $thermal_printer = Config::get('constant.thermal_printer');
                
                if($thermal_printer == 'ON')
                {
                    $this->thermalReceipt($receiptItem->id);
                }
                
                $response = response()->json([
                    'alert' =>'info',
                    'msg'   =>'Receipt Created Successfully'
                ]);

            }
            else
            {
                $response = response()->json([
                    'alert' =>'danger',
                    'msg'   =>'Receipt cannot be created'
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

    public function thermalReceipt($receiptId)
    {
        //get store info
        $storeInfo = Branch::where('id',Auth::user()->branch_id)->first();

        
        $printerInfo = Printer::where('branch_id',Auth::user()->branch_id)
        ->where('default_printer','Yes')
        ->first();

        $receiptInfo = PosReceipt::where('id',$receiptId)->first();

        $customerInfo = Profiler::where('id',$receiptInfo->profile_id)->first();

        $paymentInfo = ReceiptPayment::where('receipt_id',$receiptId)->get();

        $statements  = PrinterReceipt::where('branch_id',Auth::user()->branch_id)
        ->where('status','Active')
        ->get();

        $itemList  = PosSubReceipt::where('pos_receipt_id',$receiptId)
        ->get();

        if($printerInfo != NULL)
        {
            //GENERATE RECEIPT
            $receipt_info = array(
                'printer_name' => $printerInfo->printer_name,
                'branch_name' => $storeInfo->name,
                'branch_code' => $storeInfo->code,
                'address' => $storeInfo->address,
                'license_no' => $storeInfo->license_no,
                'telephone' => $storeInfo->contact,
                'receipt_no' => $receiptInfo->receipt_no,
                'type' => $receiptInfo->type,
                'customer' => $customerInfo->account_title,
                'customer_tel' => $customerInfo->contact_no,
                'date_time' => date('d-m-Y H:i A',strtotime($receiptInfo->created_at)),
                'emp_id' => Auth::user()->name,
                'description' =>  $receiptInfo->description,
                'doctor_details' =>  $receiptInfo->doctor_details,
                'patient_details' =>  $receiptInfo->patient_details,
                'sub_total' => $receiptInfo->total_gross_amt,
                'total_discount' => $receiptInfo->discount,
                'total' => $receiptInfo->total_bill,
                'total_tax' => $receiptInfo->total_tax,
                'invoice_payment' => $paymentInfo,
                'tendered' => $receiptInfo->total_tendered,
                'change' => $receiptInfo->total_change,
                'items' => $itemList,
                'statements' => $statements,
            );

            $r = new  ThermalReceipt();
            $r->generate_print($receipt_info);
        }
    }

    public function searchReceipt(Request $request)
    {
        $request->validate([
            'receipt_no' => ['required'],
        ]);

        $receipt = PosReceipt::with([
            'profileName:profilers.id,profilers.account_title as accountName',
        ])
        ->where('receipt_no', $request->receipt_no)
        ->where('type','INE')
        ->first();
        
        $receiptItems = PosSubReceipt::with([
            'stockDetail:stocks.id,stocks.qty,stocks.sale_price',
        ])
        ->where('pos_receipt_id', $receipt->id)
        ->get();
        
        return [
        'receipt' => $receipt,
        'receiptItems' => $receiptItems
        ];
    }
    
    public function searchPurchaseReceipt(Request $request)
    {
        $request->validate([
            'receipt_no' => ['required'],
        ]);

        $receipt = PosReceipt::with([
            'profileName:profilers.id,profilers.account_title as accountName',
        ])
        ->where('receipt_no', $request->receipt_no)
        ->where('type','PUR')
        ->first();
        
        $receiptItems = PosSubReceipt::with([
            'stockDetail:stocks.id,stocks.qty,stocks.sale_price',
        ])
        ->where('pos_receipt_id', $receipt->id)
        ->get();
        
        return [
        'receipt' => $receipt,
        'receiptItems' => $receiptItems
        ];
    }

    public function savePurchaseReceipt(Request $request)
    {
        $request->validate([
            'profile_id'   		 => ['required'],
            'payment_list'   	 => ['required'],
            'discount'   		 => ['required'],
            'total_tendered'   	 => ['required'],
            'total_change'   	 => ['required'],
            'total_gross_amt'    => ['required'],
            'total_bill'   		 => ['required'],
            'total_tax1'   		 => ['required'],
            'total_tax2'   		 => ['required'],
            'total_tax3'   		 => ['required'],
            'total_tax'   		 => ['required'],
            'payment_method'   	 => ['required'],
            'status'  			 => ['required'],
            'type'   			 => ['required'],
            'item_list'   		 => ['required'],
            'counter_entry'   	 => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
            $itemLists = json_decode($request->item_list);
            $counterEntry = json_decode($request->counter_entry);
            $paymentLists = json_decode($request->payment_list);

            if($itemLists != NULL)
            {
                $narration =  ($request->description == '' ? 'Transaction occurred from purchase screen' : $request->description);

                $transaction = new Transaction([
                    'narration'     => $narration,
                    'generated_source' => $request->type,
                    'branch_id'      => Auth::user()->branch_id,
                ]);
                
                $transaction->save();

                foreach($counterEntry as $item)
                {
                	$subTransaction = new SubTransaction([
                		'transaction_id'     => $transaction->id,
                		'account_id'     	 => $item->accountID,
                		'account_name'	 	 => $item->accountHead,
                		'amount'      	     => $item->amount,
                		'type'      		 => $item->type,
                	]);
                    
                	$subTransaction->save();
                }
                
                
                $t = new  PosReceipt();
                $receiptNo =  $t->generateID($request->type);
            
                $receiptItem 			= new PosReceipt([
                    'transaction_id' 	=> $transaction->id,
                    'receipt_no'     	=> $receiptNo,
                    'discount'   		=> $request->discount,
                    'profile_id' 		=> $request->profile_id,
                    'payment_method'    => $request->payment_method,
                    'total_gross_amt'   => $request->total_gross_amt,
                    'total_bill' 		=> $request->total_bill,
                    'total_tendered'   	=> $request->total_tendered,
                    'total_change'   	=> $request->total_change,
                    'total_tax1'   	 	=> $request->total_tax1,
                    'total_tax2'   	 	=> $request->total_tax2,
                    'total_tax3'   	 	=> $request->total_tax3,
                    'total_tax'   	 	=> $request->total_tax,
                    'description'   	=> $request->description,
                    'doctor_details'   	=> '',
                    'patient_details'   => '',
                    'bill_no'           => $request->bill_no,
                    'created_by'      	=> Auth::user()->id,
                    'receipt_date'      => date('Y-m-d'),
                    'return_receipt'    => $request->search_receipt_no,
                    'type'      		=> $request->type,
                    'status'         	=> $request->status,
                    'branch_id'      	=> Auth::user()->branch_id,
                ]);

                $receiptItem->save();

                 //IF USED CARDS
                 $t->passBankTransaction($transaction->id,$receiptNo,$narration,$paymentLists,$request->profile_id);

                foreach($itemLists as $item)
                {
                    //MANAGING STOCKS
                    $s = new Stock();
                    $stock_id = $s->addReducePurchaseStock($item,$request->type);

                    $PosSubReceipt = new PosSubReceipt([
                        'pos_receipt_id'    => $receiptItem->id,
                        'mode'    			=> $item->mode,
                        'stock_id'    		=> $stock_id,
                        'item_name'       	=> $item->productName,
                        'generic_name'    	=> $item->generic,
                        'item_description'  => $item->itemDescription,
                        'unit'        		=> $item->unit,
                        'total_unit'        => $item->totalUnit,
                        'free_unit'   		=> $item->freeUnit,
                        'supplier_bonus'   	=> $item->supplierBonus,
                        'batch_no'   		=> $item->batchNo,
                        'pack_size'   		=> $item->packSize,
                        'sheet_size'   		=> $item->sheetSize,
                        'purchase_price'   	=> $item->purchasePrice,
                        'selling_price'   	=> $item->sellingPrice,
                        'mrp'   			=> $item->mrp,
                        'brand_name'   		=> $item->brandName,
                        'sector_name'   	=> $item->sectorName,
                        'category_name'   	=> $item->categoryName,
                        'product_type'   	=> $item->productType,
                        'expiry_date'   	=> Date('Y-m-d',strtotime($item->expiryDate)),
                        'item_disc'   		=> $item->cusDisc,
                        'purchase_disc'   	=> $item->itemDisc,
                        'after_disc'   	    => $item->purchaseAfterDisc,
                        'tax_1'   			=> $item->tax1,
                        'tax_2'   			=> $item->tax2,
                        'tax_3'   			=> $item->tax3,
                        'sub_total'   	    => $item->subTotal,
                    ]);
                    
                    $PosSubReceipt->save();
                }
                
                if($paymentLists != NULL)
                {
                    foreach($paymentLists as $item)
                    {
                        $payments = new ReceiptPayment([
                            'transaction_id'    	=> $transaction->id,
                            'receipt_id'    		=> $receiptItem->id,
                            'account_no'    		=> $item->accountNo,
                            'auth_code'    			=> $item->authCode,
                            'card_balance'	 		=> $item->cardBalance,
                            'change'       			=> $item->change,
                            'entry_mode'    		=> $item->entryMode,
                            'gift_card_ref'  		=> $item->giftCardRef,
                            'host_response'        	=> $item->hostResponse,
                            'payment_type'        	=> $item->paymentType,
                            'round_off'   			=> $item->roundOff,
                            'tendered'   			=> $item->tendered,
                            'terminal_id'   		=> $item->terminalId,
                            'trans_amount'   		=> $item->transAmount,
                            'trans_date'   			=> date('Y-m-d'),
                            'trans_id'   			=> $item->transId,
                            'trans_ref'   			=> $item->transRef,
                            'trans_status'   		=> $item->transStatus,
                            'trans_time'   			=> date('H:i:s'),
                            'trans_total_amount'   	=> $item->transTotalAmount,
                            'trans_type'   			=> $item->transType,
                            'source_type'   	    => $request->type,
                            'description'   	    => $narration,
                            'receipt_no'   	    	=> $receiptNo,
                            'created_by'   	    	=> Auth::user()->id,
                            'branch_id'   	    	=> Auth::user()->branch_id,
                        ]);
                        
                        $payments->save();
                    }
                }


                $response = response()->json([
                    'alert' =>'info',
                    'msg'   =>'Receipt Created Successfully'
                ]);

            }
            else
            {
                $response = response()->json([
                    'alert' =>'danger',
                    'msg'   =>'Receipt cannot be created'
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

    public function transactions(Request $request)
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

        if($filters->type != 'ASR')
        {
            
            $options = PosReceipt::with([
                'branch:id,name as branchName,code as branchCode',
                'userName:id,name as userName',
                'profileName:profilers.id,profilers.account_title as profileName',
                'receiptBalance'
            ])
            ->where('type', $filters->type)
            ->where('receipt_no','LIKE','%'.$filters->keyword.'%')
            ->where('branch_id',$filters->storeID)
            ->whereDate('receipt_date','>=', $date1)
            ->whereDate('receipt_date','<=', $date2)
            ->limit(20)
            ->offset($request->start)
            ->orderBy('id','DESC')
            ->get();
            
            
            $totalRecords = PosReceipt::where('type', $filters->type)
            ->where('receipt_no','LIKE','%'.$filters->keyword.'%')
            ->whereDate('receipt_date','>=', $date1)
            ->whereDate('receipt_date','<=', $date2)
            ->where('branch_id',$filters->storeID)
            ->count();
        }
        else
        {
            $options = PosReceipt::with([
                'branch:id,name as branchName,code as branchCode',
                'userName:id,name as userName',
                'profileName:profilers.id,profilers.account_title as profileName',
                'receiptBalance',
                'transferBranch'
            ])
            ->whereHas("transferBranch",function($q) use($filters){
                $q->where("branch_id","=",$filters->storeID);
            })
            ->where('receipt_no','LIKE','%'.$filters->keyword.'%')
            ->whereDate('receipt_date','>=', $date1)
            ->whereDate('receipt_date','<=', $date2)
            ->where('type', 'TRN')
            ->where(function ($query) {
                $query->where('status','=','Stock Left')
                ->orWhere('status','=','Stock Received');
            })
            ->limit(20)
            ->offset($request->start)
            ->orderBy('id','DESC')
            ->get();
            
            
            $totalRecords = PosReceipt::with([
                'transferBranch'
            ])
            ->whereHas("transferBranch",function($q) use($filters){
                $q->where("branch_id","=",$filters->storeID);
            })
            ->where('receipt_no','LIKE','%'.$filters->keyword.'%')
            ->whereDate('receipt_date','>=', $date1)
            ->whereDate('receipt_date','<=', $date2)
            ->where('type', 'TRN')
            ->where('status','Stock Left')
            ->count();
        }
		

		
		
		return [
			'records' => $options,
			'limit' => 20,
			'totalRecords' => $totalRecords,
			'statement' => 'Transactions between '.date('d-m-Y',strtotime($date1)).'-TO-'.date('d-m-Y',strtotime($date2)),
		];
	} 

    public function posPayments(Request $request)
    {
        $request->validate([
            'receipt_id'   		 => ['required'],
            'payment_list'   	 => ['required'],
            'counter_list'   	 => ['required'],
            'type'   	         => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
           
            $paymentLists = json_decode($request->payment_list);
            $counterEntry = json_decode($request->counter_list);
            $receipt      = PosReceipt::find($request->receipt_id);

            if($request->type == 'INE')
			{
				$narration = 'Received pos invoice payment';
			}
			else if($request->type == 'RFD')
			{
				$narration = 'Paid pos refund payment';
			}
			else if($request->type == 'TRN')
			{
				$narration = 'Received pos transfer payment';
			}
            else if($request->type == 'PUR')
			{
				$narration = 'Paid purchase stock payment';
			}
            else if($request->type == 'RPU')
			{
				$narration = 'Received return purchase stock payment';
			}

            $transaction = new Transaction([
				'narration'         => $narration,
				'generated_source'  => $request->type,
				'branch_id'         => Auth::user()->branch_id,
			]);

			$transaction->save();

			foreach($counterEntry as $item)
			{
				$subTransaction = new SubTransaction([
					'transaction_id'     => $transaction->id,
					'account_id'     	 => $item->accountID,
					'account_name'	 	 => $item->accountHead,
					'amount'      	     => $item->amount,
					'type'      		 => $item->type,
				]);

				$subTransaction->save();
			}


			//IF USED CARDS
			$t = new  PosReceipt();
			$t->passBankTransaction($transaction->id,$receipt->receipt_no,$narration,$paymentLists,$receipt->profile_id);

            if($paymentLists != NULL)
            {
                foreach($paymentLists as $item)
                {
                    $payments = new ReceiptPayment([
                        'transaction_id'    	=> $transaction->id,
                        'receipt_id'    		=> $request->receipt_id,
                        'account_no'    		=> $item->accountNo,
                        'auth_code'    			=> $item->authCode,
                        'card_balance'	 		=> $item->cardBalance,
                        'change'       			=> $item->change,
                        'entry_mode'    		=> $item->entryMode,
                        'gift_card_ref'  		=> $item->giftCardRef,
                        'host_response'        	=> $item->hostResponse,
                        'payment_type'        	=> $item->paymentType,
                        'round_off'   			=> $item->roundOff,
                        'tendered'   			=> $item->tendered,
                        'terminal_id'   		=> $item->terminalId,
                        'trans_amount'   		=> $item->transAmount,
                        'trans_date'   			=> date('Y-m-d'),
                        'trans_id'   			=> $item->transId,
                        'trans_ref'   			=> $item->transRef,
                        'trans_status'   		=> 'Active',
                        'trans_time'   			=> date('H:i:s'),
                        'trans_total_amount'   	=> $item->transTotalAmount,
                        'trans_type'   			=> $item->transType,
                        'source_type'   	    => $request->type,
                        'description'   	    => $narration,
                        'receipt_no'   	    	=> $receipt->receipt_no,
                        'created_by'   	    	=> Auth::user()->id,
                        'branch_id'   	    	=> Auth::user()->branch_id,
                    ]);
                    
                    $payments->save();
                }
            }


            $response = response()->json([
                'alert' =>'info',
                'msg'   =>'Payment Created Successfully'
            ]);


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
    
    public function stockLeft(Request $request)
    {
        $request->validate([
            'receipt_id'   => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
           
            $receipt = PosReceipt::find($request->receipt_id);
            $receipt->status    = 'Stock Left';
            $receipt->update();

            $response = response()->json([
                'alert' =>'info',
                'msg'   =>'Stock Status Changed Successfully'
            ]);

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
    
    public function voidStock(Request $request)
    {
        $request->validate([
            'receipt_id'   => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
           
            $receipt = PosReceipt::find($request->receipt_id);
            $receipt->status    = 'Void';
            $receipt->payment_method    = 'Void';
            $receipt->total_gross_amt    = 0;
            $receipt->total_bill    = 0;
            $receipt->total_tendered    = 0;
            $receipt->total_change    = 0;
            $receipt->total_tax1    = 0;
            $receipt->total_tax2    = 0;
            $receipt->total_tax3    = 0;
            $receipt->total_tax    = 0;
            $receipt->update();

            //REVERSE ACCOUNTING ENTRIES
            if($receipt->type == 'INE')
			{
				$narration = 'Transaction occurred from void invoice';
			}
			else if($receipt->type == 'RFD')
			{
				$narration = 'Transaction occurred from void refund';
			}
			else if($receipt->type == 'TRN')
			{
				$narration = 'Transaction occurred from void transfer';
			}
            else if($receipt->type == 'PUR')
			{
				$narration = 'Transaction occurred from void purchase';
			}
            else if($receipt->type == 'RPU')
			{
				$narration = 'Transaction occurred from void purchase return';
			}

            $transaction = new Transaction([
				'narration'         => $narration,
				'generated_source'  => $receipt->type,
				'branch_id'         => Auth::user()->branch_id,
			]);

			$transaction->save();

            $counterEntry = SubTransaction::Where('transaction_id',$receipt->transaction_id)->get();

            if($counterEntry != NULL)
            {
                foreach($counterEntry as $item)
                {
                    if($item->type == 'Credit')
                    {
                        $subTransaction = new SubTransaction([
                            'transaction_id'     => $transaction->id,
                            'account_id'     	 => $item->account_id,
                            'account_name'	 	 => $item->account_name,
                            'amount'      	     => $item->amount,
                            'type'      		 => 'Debit',
                        ]);

                        $subTransaction->save();
                    }
                }

                foreach($counterEntry as $item)
                {
                    if($item->type == 'Debit')
                    {
                        $subTransaction = new SubTransaction([
                            'transaction_id'     => $transaction->id,
                            'account_id'     	 => $item->account_id,
                            'account_name'	 	 => $item->account_name,
                            'amount'      	     => $item->amount,
                            'type'      		 => 'Credit',
                        ]);

                        $subTransaction->save();
                    }
                }
            }


            //TRANSFEREE STOCK
            $receiptItems = PosSubReceipt::where('pos_receipt_id',$request->receipt_id)->get();

            if($receiptItems != NULL)
            {
                foreach($receiptItems as $item)
                {
                    $stock = Stock::find($item->stock_id);

                    if($receipt->type == 'INE' OR $receipt->type == 'RFD' OR $receipt->type == 'RPU')
                    {
                        $stock->qty =  $stock->qty + $item->total_unit;
                    }
                    else
                    {
                        $stock->qty =  $stock->qty - $item->total_unit;
                    }
                   
                    $stock->update();

                    $receiptItem = PosSubReceipt::find($item->id);
                    $receiptItem->unit =  0;
                    $receiptItem->total_unit =  0;
                    $receiptItem->sub_total =  0;
                    $receiptItem->free_unit =  0;
                    $receiptItem->supplier_bonus =  0;
                    $receiptItem->item_disc =  0;
                    $receiptItem->purchase_disc =  0;
                    $receiptItem->after_disc =  0;
                    $receiptItem->update();
                }
            }

            $response = response()->json([
                'alert' =>'info',
                'msg'   =>'Receipt voided Successfully'
            ]);

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
    
    public function stockSaved(Request $request)
    {
        $request->validate([
            'receipt_id'   => ['required'],
        ]);

        DB::beginTransaction();

        try
        {
           
            $receipt = PosReceipt::find($request->receipt_id);
            $receipt->status    = 'Stock Received';
            $receipt->update();

            //TRANSFEREE STOCK
            $receiptItems = PosSubReceipt::where('pos_receipt_id',$request->receipt_id)->get();

            //TRANSFEREE STORE
            $transferStoreInfo = TransferStore::where('receipt_id',$request->receipt_id)->first();

            if($receiptItems != NULL AND $transferStoreInfo != NULL)
            {
                foreach($receiptItems as $item)
                {
                    $stockRecord = Stock::where('product_name',$item->item_name)
                    ->where('batch_no',$item->batch_no)
                    ->where('expiry_date',$item->expiry_date)
                    ->where('branch_id',$transferStoreInfo->branch_id)
                    ->first();

                 
                    if($stockRecord != NULL)
                    {
                      //  $stock = Stock::find($stockRecord->id);
                      $stockRecord['qty'] =  $stockRecord['qty'] + $item->total_unit;
                      $stockRecord->update();
                    }
                    else
                    {
                        $stock = Stock::find($item->stock_id);

                        $stock = new Stock([
							'product_name'    	  => strtoupper($stock->product_name),
							'generic'     	  	  => strtoupper($stock->generic),
							'barcode'	 	 	  => $stock->barcode,
							'type'      	  	  => $stock->type,
							'description'         => $stock->description,
							'image'        		  => $stock->image,
							'brand'      		  => $stock->brand,
							'brand_sector'        => $stock->brand_sector,
							'category'      	  => $stock->category,
							'side_effects'        => $stock->side_effects,
							'pack_size'      	  => $stock->pack_size,
							'strip_size'      	  => $stock->strip_size,
							'expiry_date'     	  => $stock->expiry_date,
							'qty'	 	 		  => $item->total_unit,
							'sale_price'      	  => $stock->sale_price,
							'purchase_price'      => $stock->purchase_price,
							'mrp'      		 	  => $stock->mrp,
							'batch_no'      	  => $stock->batch_no,
							'tax_1'      		  => $stock->tax_1,
							'tax_2'      		  => $stock->tax_2,
							'tax_3'      		  => $stock->tax_3,
							'discount_percentage' => $stock->discount_percentage,
							'min_stock'      	  => $stock->min_stock,
							'item_location'       => $stock->item_location,
							'created_by'      	  => Auth::user()->id,
							'status'      		  => 'Active',
							'branch_id'      	  => $transferStoreInfo->branch_id,
						]);

						$stock->save();
                    }
                }
            }
            else
            {
                DB::rollBack();

                $response = response()->json([
                    'alert' =>'danger',
                    'msg'   => 'Failed to complete  the process'
                ]);
            }

            $response = response()->json([
                'alert' =>'info',
                'msg'   =>'Stock Status Changed Successfully'
            ]);

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

    public function getPosReceipt(Request $request)
    {
        $request->validate([
			'id' => ['required']
		]);

        $receipt = PosReceipt::with([
			'profileName:profilers.id,profilers.account_title as profileName',
		])
		->where('id',$request->id)
		->get()->first();


		$receiptList = PosReceipt::find($request->id)->itemList;

        $storeDetail = Branch::with([
			'taxName1:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName2:chart_accounts.id,chart_accounts.account_name as chartName',
			'taxName3:chart_accounts.id,chart_accounts.account_name as chartName',
		])
		->where('id',$receipt['branch_id'])
		->first();

        $tStoreDetails = TransferStore::with([
            'transferBranch:branches.id,branches.name'
		])
		->where('receipt_id',$request->id)
		->get()->first();
		
		$response   = response()->json([
			'receipt'        => $receipt,
			'receiptList'    => $receiptList,
			'storeDetail'    => $storeDetail,
			'tStoreDetails'  => $tStoreDetails,
		]);

		return $response;
    }
}

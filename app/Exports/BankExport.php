<?php

namespace App\Exports;

//use App\Models\Products;
use App\Models\Branch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
//use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Auth;

class BankExport implements
//FromCollection,
ShouldAutoSize,
WithHeadings,
WithEvents
//WithMapping
{
	use Exportable;

	public function headings(): array
	{
		return ['Bank', 'Branch Name','Branch Code', 'Account Title', 'Account Number' , 'Account Type','Ending Date' ,'Account Balance (USD)'];
	}


	public function registerEvents(): array
	{
		$styleArray = [
			'font' => [
				'bold' => true,
				'size' => 18
			]
		];

		return [
			AfterSheet::class => function (AfterSheet $event) use ($styleArray) {
				$event->sheet->getStyle('A1:G1')->applyFromArray($styleArray);
			}
		];
	}

	public function startCell(): string
	{
		return 'A1';
	}
}

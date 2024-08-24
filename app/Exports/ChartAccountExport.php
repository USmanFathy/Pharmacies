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

class ChartAccountExport implements
//FromCollection,
ShouldAutoSize,
WithHeadings,
WithEvents
//WithMapping
{
	use Exportable;

	public function headings(): array
	{
		return ['Account name','Account Code', 'Account nature', 'Account type', 'Expense type'];
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

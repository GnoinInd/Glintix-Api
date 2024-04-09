<?php

namespace App\Exports;

use App\Models\ProjectMaster;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectMasterData implements FromCollection, WithHeadings
// class ProjectMasterData implements FromCollection
{
    protected $month;
    protected $year;
    protected $code;

    public function __construct(int $month, int $year, $code)
    {
        $this->month=$month;
        $this->year=$year;
        $this->code=$code;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
 
    public function collection()
    {
        return ProjectMaster::whereMonth('start_date', $this->month)
        ->whereYear('start_date', $this->year)
        ->where('company_code', $this->code)
        ->get();
    }


    public function headings(): array
    {
        $columns = Schema::getColumnListing('project_masters');
        $filteredColumns = array_diff($columns, ['created_at', 'updated_at']);
        return $filteredColumns;
    }


}

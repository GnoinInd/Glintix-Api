<?php

namespace App\Exports;

use App\Models\ProjectMaster;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectFilterData implements FromCollection, WithHeadings
{

    protected $fromDate;
    protected $toDate;
    protected $code;

    public function __construct($fromDate, $toDate, $code)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->code  = $code;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return ProjectMaster::where('company_code', $this->code)
            ->whereBetween('start_date', [$this->fromDate, $this->toDate])
            ->get();
    }

    public function headings(): array
    {
        $columns = Schema::getColumnListing('project_masters');
        $filteredColumns = array_diff($columns, ['created_at', 'updated_at']);
        return $filteredColumns;
    }

}

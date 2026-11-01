<?php

namespace crocodicstudio\crudbooster\export;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportWorkflow
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function export(
        mixed $filetype,
        array $data,
        mixed $filename,
        mixed $paperSize,
        mixed $paperOrientation,
    ): BinaryFileResponse|Response|null {
        switch ($filetype) {
            case 'pdf':
                $view = view('crudbooster::export', $data)->render();
                $pdf = App::make('dompdf.wrapper');
                $pdf->loadHTML($view);
                $pdf->setPaper($paperSize, $paperOrientation);

                return $pdf->stream($filename.'.pdf');
            case 'xls':
                return Excel::download(new DefaultExportXls($data), $filename.'.xlsx');
            case 'csv':
                return Excel::download(new DefaultExportXls($data), $filename.'.csv');
        }

        return null;
    }
}

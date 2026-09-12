<?php

namespace App\Http\Controllers\Owner;

use App\Actions\ImportSalesOrdersAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreSalesImportRequest;
use Illuminate\Http\RedirectResponse;

class SalesImportController extends Controller
{
    public function __invoke(StoreSalesImportRequest $request, ImportSalesOrdersAction $import): RedirectResponse
    {
        $result = $import->execute($request->attributes->get('activeBusiness'), $request->user(), $request->file('sales_file'));

        return redirect()->route('sales.index')->with('success',
            "File berhasil diproses: {$result['new_count']} pesanan baru dan {$result['updated_count']} pesanan diperbarui.");
    }
}

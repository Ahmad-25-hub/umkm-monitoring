<?php

namespace App\Http\Controllers\Employee;

use App\Actions\ImportTikTokSalesCsvAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreSalesImportRequest;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class SalesImportController extends Controller
{
    public function __invoke(
        StoreSalesImportRequest $request,
        ImportTikTokSalesCsvAction $importTikTokSalesCsv,
    ): RedirectResponse {
        /** @var Business $business */
        $business = $request->attributes->get('activeEmployeeBusiness');
        /** @var User $employee */
        $employee = $request->user();
        $result = $importTikTokSalesCsv->execute(
            $business,
            $employee,
            $request->file('sales_file'),
        );

        return redirect()->route('employee.dashboard')->with(
            'success',
            "File berhasil diproses: {$result['new_count']} pesanan baru dan {$result['updated_count']} pesanan diperbarui.",
        );
    }
}

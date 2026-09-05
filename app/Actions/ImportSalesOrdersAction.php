<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportSalesOrdersAction
{
    public const SALES_TIMEZONE = 'Asia/Jakarta';

    public function __construct(
        private ImportTikTokSalesCsvAction $importTikTokSalesCsv,
        private ImportShopeeSalesXlsxAction $importShopeeSalesXlsx,
    ) {}

    /**
     * @return array{row_count: int, order_count: int, new_count: int, updated_count: int}
     *
     * @throws ValidationException
     */
    public function execute(Business $business, User $employee, UploadedFile $file): array
    {
        return match (Str::lower($file->getClientOriginalExtension())) {
            'csv' => $this->importTikTokSalesCsv->execute($business, $employee, $file),
            'xlsx' => $this->importShopeeSalesXlsx->execute($business, $employee, $file),
            default => throw ValidationException::withMessages([
                'sales_file' => 'Format file belum didukung. Gunakan CSV TikTok Seller atau XLSX Shopee.',
            ]),
        };
    }
}

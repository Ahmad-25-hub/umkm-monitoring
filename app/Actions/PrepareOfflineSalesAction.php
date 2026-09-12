<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use App\Support\SalesSpreadsheetReader;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class PrepareOfflineSalesAction
{
    public const HEADERS = ['Tanggal', 'No. transaksi', 'Produk', 'Jumlah', 'Harga satuan'];

    public function __construct(private SalesSpreadsheetReader $reader) {}

    /**
     * @return array{orders: array<string, array<string, mixed>>, rows: array<int, array<string, mixed>>, errors: array<int, string>, duplicates: array<int, string>}
     */
    public function execute(Business $business, UploadedFile $file): array
    {
        $rows = $this->reader->readRows($file, self::HEADERS, 2000, strict: true);

        if ($rows === []) {
            throw ValidationException::withMessages(['sales_file' => 'Template masih kosong. Isi penjualan pada sheet Penjualan, lalu unggah kembali.']);
        }

        $orders = [];
        $previewRows = [];
        $errors = [];

        foreach ($rows as $row) {
            $number = (int) $row['_row'];
            $data = [
                'ordered_on' => $this->date($row['Tanggal']),
                'order_id' => Str::upper(trim($row['No. transaksi'])),
                'product_name' => trim($row['Produk']),
                'quantity' => trim($row['Jumlah']),
                'unit_price' => trim($row['Harga satuan']),
            ];
            $validator = Validator::make($data, [
                'ordered_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:'.today(ImportSalesOrdersAction::SALES_TIMEZONE)->toDateString()],
                'order_id' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_-]+$/'],
                'product_name' => ['required', 'string', 'max:255'],
                'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
                'unit_price' => ['required', 'integer', 'min:0', 'max:1000000000'],
            ], [
                'ordered_on.*' => 'Tanggal harus valid, antara 01/01/2000 dan hari ini (WIB). Gunakan DD/MM/YYYY.',
                'order_id.*' => 'No. transaksi wajib diisi, maksimal 80 karakter (huruf, angka, - atau _).',
                'product_name.*' => 'Produk wajib diisi, maksimal 255 karakter.',
                'quantity.*' => 'Jumlah harus berupa bilangan bulat antara 1 dan 100.000.',
                'unit_price.*' => 'Harga satuan harus berupa rupiah bulat antara 0 dan 1.000.000.000, tanpa Rp atau pemisah ribuan.',
            ]);
            $rowErrors = $validator->errors()->all();
            if (in_array('__NADI_FORMULA__', $row, true)) {
                $rowErrors[] = 'Isi nilai penjualan langsung, bukan formula Excel.';
            }
            $orderKey = $data['order_id'];

            if ($rowErrors === [] && isset($orders[$orderKey]) && $orders[$orderKey]['ordered_on'] !== $data['ordered_on']) {
                $rowErrors[] = 'Tanggal berbeda untuk nomor transaksi yang sama. Gunakan nomor transaksi unik untuk setiap pesanan.';
            }

            foreach ($rowErrors as $error) {
                $errors[] = "Baris {$number}: {$error}";
            }

            $previewRows[] = [
                'number' => $number,
                'date' => $data['ordered_on'] !== '' ? CarbonImmutable::parse($data['ordered_on'])->format('d/m/Y') : $row['Tanggal'],
                'order_id' => $data['order_id'],
                'product_name' => $data['product_name'],
                'quantity' => $row['Jumlah'],
                'unit_price' => $row['Harga satuan'],
                'subtotal' => $rowErrors === [] ? (int) $data['quantity'] * (int) $data['unit_price'] : null,
                'errors' => $rowErrors,
            ];

            if ($rowErrors !== []) {
                continue;
            }

            $orders[$orderKey] ??= ['ordered_on' => $data['ordered_on'], 'items' => []];
            $orders[$orderKey]['items'][] = [
                'product_name' => $data['product_name'],
                'quantity' => (int) $data['quantity'],
                'unit_price' => (int) $data['unit_price'],
            ];
        }

        $duplicates = [];
        foreach (array_chunk(array_keys($orders), 500) as $ids) {
            $duplicates = [...$duplicates, ...SalesOrder::query()->whereBelongsTo($business)
                ->where('platform', 'offline')->whereIn('platform_order_id', $ids)->pluck('platform_order_id')->all()];
        }

        return ['orders' => $orders, 'rows' => $previewRows, 'errors' => $errors, 'duplicates' => $duplicates];
    }

    private function date(string $value): string
    {
        $value = trim($value);

        try {
            if (preg_match('/^\d+(\.\d+)?$/', $value) && (float) $value >= 36526 && (float) $value <= 2958465) {
                return CarbonImmutable::create(1899, 12, 30, timezone: ImportSalesOrdersAction::SALES_TIMEZONE)
                    ->addDays((int) $value)->toDateString();
            }

            $format = str_contains($value, '/') ? 'd/m/Y' : 'Y-m-d';
            $date = CarbonImmutable::createFromFormat('!'.$format, $value, ImportSalesOrdersAction::SALES_TIMEZONE);

            if ($date && $date->format($format) === $value) {
                return $date->toDateString();
            }
        } catch (Throwable) {
            return '';
        }

        return '';
    }
}

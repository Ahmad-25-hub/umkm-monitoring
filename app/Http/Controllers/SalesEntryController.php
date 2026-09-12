<?php

namespace App\Http\Controllers;

use App\Actions\PrepareOfflineSalesAction;
use App\Actions\StoreOfflineSalesAction;
use App\Http\Requests\PreviewOfflineSalesRequest;
use App\Http\Requests\StoreOfflineSalesRequest;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Support\OfflineSalesTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesEntryController extends Controller
{
    public function create(Request $request): View
    {
        return view('dashboard.sales-entry', $this->viewData($request));
    }

    public function store(StoreOfflineSalesRequest $request, StoreOfflineSalesAction $store): RedirectResponse
    {
        $data = $request->validated();
        $result = $store->execute($this->business($request), $request->user(), [
            $data['order_id'] => ['ordered_on' => $data['ordered_on'], 'items' => $data['items']],
        ], 'Input manual');

        if ($result['skipped_count'] > 0) {
            throw ValidationException::withMessages(['order_id' => 'Nomor transaksi ini sudah tercatat. Gunakan nomor baru untuk penjualan lain.']);
        }

        return redirect()->route($request->boolean('save_again') ? $this->prefix($request).'.create' : $this->returnRoute($request))
            ->with('success', 'Penjualan offline berhasil dicatat.');
    }

    public function template(OfflineSalesTemplate $template): Response
    {
        return response($template->contents(), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-penjualan-offline-nadi.xlsx"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function preview(PreviewOfflineSalesRequest $request, PrepareOfflineSalesAction $prepare): View
    {
        $preview = $prepare->execute($this->business($request), $request->file('sales_file'));
        $token = null;

        if ($preview['errors'] === []) {
            $token = (string) Str::uuid();
            $request->session()->cache()->put($this->previewKey($request, $token), [
                'orders' => $preview['orders'],
                'source' => $request->file('sales_file')->getClientOriginalName(),
            ], now()->addMinutes(30));
        }

        return view('dashboard.sales-entry-preview', [
            ...$this->viewData($request),
            'preview' => $preview,
            'token' => $token,
        ]);
    }

    public function confirm(Request $request, StoreOfflineSalesAction $store): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'uuid']]);
        $key = $this->previewKey($request, $data['token']);
        $preview = $request->session()->cache()->get($key);

        if (! is_array($preview)) {
            return redirect()->route($this->prefix($request).'.create')->withErrors([
                'sales_file' => 'Pratinjau kedaluwarsa atau usaha aktif berubah. Unggah ulang file untuk memeriksa penjualan.',
            ]);
        }

        $result = $store->execute($this->business($request), $request->user(), $preview['orders'], $preview['source']);
        $request->session()->cache()->forget($key);

        return redirect()->route($this->returnRoute($request))->with('success',
            "Penjualan offline tersimpan: {$result['new_count']} transaksi baru; {$result['skipped_count']} transaksi yang sudah tercatat dilewati.");
    }

    private function business(Request $request): Business
    {
        return $request->attributes->get('activeBusiness') ?? $request->attributes->get('activeEmployeeBusiness');
    }

    private function prefix(Request $request): string
    {
        return $request->routeIs('employee.*') ? 'employee.sales-entry' : 'sales-entry';
    }

    private function returnRoute(Request $request): string
    {
        return $request->routeIs('employee.*') ? 'employee.dashboard' : 'sales.index';
    }

    private function previewKey(Request $request, string $token): string
    {
        return 'offline-sales:'.$this->prefix($request).':'.$request->user()->id.':'.$this->business($request)->id.':'.$token;
    }

    /** @return array<string, mixed> */
    private function viewData(Request $request): array
    {
        $user = $request->user();
        $employee = $request->routeIs('employee.*');
        $person = [
            'name' => $user->name,
            'email' => $user->email,
            'initials' => Str::of($user->name)->squish()->explode(' ')->filter()->take(2)
                ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->implode(''),
        ];
        $business = $this->business($request);

        return [
            'shell' => $employee ? 'employee.app-shell' : 'dashboard.app-shell',
            'employee' => $person,
            'owner' => $person,
            'business' => $business,
            'businesses' => $employee ? [] : $request->attributes->get('activeBusinessMemberships')
                ->map(fn (BusinessMembership $membership): array => [
                    'id' => $membership->business_id, 'name' => $membership->business->name, 'location' => 'Pemilik',
                ])->all(),
            'activeBusinessId' => $business->id,
            'routePrefix' => $this->prefix($request),
            'returnRoute' => $this->returnRoute($request),
            'marketplaceRoute' => $employee ? 'employee.sales-imports.store' : 'sales-imports.store',
            'defaultOrderId' => 'OFF-'.Str::upper((string) Str::uuid()),
        ];
    }
}

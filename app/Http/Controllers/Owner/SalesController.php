<?php

namespace App\Http\Controllers\Owner;

use App\Actions\BuildOwnerSalesReportAction;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalesController extends Controller
{
    public function __invoke(Request $request, BuildOwnerSalesReportAction $buildSalesReport): View
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(BuildOwnerSalesReportAction::PERIODS))],
            'channel' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(BuildOwnerSalesReportAction::STATUSES))],
        ]);

        /** @var User $user */
        $user = $request->user();
        /** @var Business $activeBusiness */
        $activeBusiness = $request->attributes->get('activeBusiness');
        /** @var Collection<int, BusinessMembership> $activeMemberships */
        $activeMemberships = $request->attributes->get('activeBusinessMemberships');

        $businesses = $activeMemberships
            ->map(fn (BusinessMembership $membership): array => [
                'id' => $membership->business_id,
                'name' => $membership->business->name,
                'location' => 'Pemilik',
            ])
            ->values()
            ->all();

        $ownerInitials = Str::of($user->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return view('dashboard.sales', [
            'owner' => [
                'name' => $user->name,
                'initials' => $ownerInitials,
            ],
            'businesses' => $businesses,
            'activeBusinessId' => $activeBusiness->id,
            'activeBusiness' => $activeBusiness,
            'report' => $buildSalesReport->execute(
                business: $activeBusiness,
                availableBusinesses: $activeMemberships->pluck('business'),
                period: $filters['period'] ?? '30d',
                channel: $filters['channel'] ?? null,
                status: $filters['status'] ?? 'valid',
            ),
        ]);
    }
}

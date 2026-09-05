<?php

namespace App\Actions;

use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BuildAccountNavigationContextAction
{
    /** @return array{mode: string, roleLabel: string, logoutRoute: string, person: array{name: string, email: string, initials: string}, businesses: array<int, array{id: int, name: string, location: string}>, activeBusinessId: int|null, business: mixed} */
    public function execute(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        $memberships = $user->memberships()
            ->active()
            ->with('business')
            ->oldest('id')
            ->get();
        $ownerMemberships = $memberships->where('role', BusinessMembership::ROLE_OWNER)->values();
        $employeeMemberships = $memberships->where('role', BusinessMembership::ROLE_EMPLOYEE)->values();
        $preferredEmployeeId = (int) $request->session()->get('active_employee_business_id');
        $preferredOwnerId = (int) $request->session()->get('active_business_id');
        $isEmployeeContext = $preferredEmployeeId > 0
            && $employeeMemberships->contains('business_id', $preferredEmployeeId)
            && ! ($preferredOwnerId > 0 && $ownerMemberships->contains('business_id', $preferredOwnerId));
        $initials = Str::of($user->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
        $person = ['name' => $user->name, 'email' => $user->email, 'initials' => $initials];

        if ($employeeMemberships->isNotEmpty() && ($isEmployeeContext || $ownerMemberships->isEmpty())) {
            $activeMembership = $employeeMemberships->firstWhere('business_id', $preferredEmployeeId)
                ?? $employeeMemberships->first();

            return [
                'mode' => 'employee',
                'roleLabel' => 'Karyawan',
                'logoutRoute' => 'employee.logout',
                'person' => $person,
                'businesses' => [],
                'activeBusinessId' => null,
                'business' => $activeMembership->business,
            ];
        }

        if ($ownerMemberships->isNotEmpty()) {
            $activeMembership = $ownerMemberships->firstWhere('business_id', $preferredOwnerId)
                ?? $ownerMemberships->first();

            return [
                'mode' => 'owner',
                'roleLabel' => 'Pemilik',
                'logoutRoute' => 'logout',
                'person' => $person,
                'businesses' => $ownerMemberships
                    ->map(fn (BusinessMembership $membership): array => [
                        'id' => $membership->business_id,
                        'name' => $membership->business->name,
                        'location' => 'Pemilik',
                    ])
                    ->all(),
                'activeBusinessId' => $activeMembership->business_id,
                'business' => $activeMembership->business,
            ];
        }

        return [
            'mode' => 'standalone',
            'roleLabel' => 'Pengguna',
            'logoutRoute' => 'logout',
            'person' => $person,
            'businesses' => [],
            'activeBusinessId' => null,
            'business' => null,
        ];
    }
}

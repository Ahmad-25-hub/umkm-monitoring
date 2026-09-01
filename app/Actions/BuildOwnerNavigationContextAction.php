<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BuildOwnerNavigationContextAction
{
    /**
     * @return array{owner: array{name: string, initials: string}, businesses: array<int, array{id: int, name: string, location: string}>, activeBusinessId: int, activeBusiness: Business}
     */
    public function execute(Request $request): array
    {
        /** @var User $user */
        $user = $request->user();
        /** @var Business $activeBusiness */
        $activeBusiness = $request->attributes->get('activeBusiness');
        $activeMemberships = $request->attributes->get('activeBusinessMemberships');
        $initials = Str::of($user->name)
            ->squish()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return [
            'owner' => ['name' => $user->name, 'initials' => $initials],
            'businesses' => $activeMemberships
                ->map(fn (BusinessMembership $membership): array => [
                    'id' => $membership->business_id,
                    'name' => $membership->business->name,
                    'location' => 'Pemilik',
                ])
                ->values()
                ->all(),
            'activeBusinessId' => $activeBusiness->id,
            'activeBusiness' => $activeBusiness,
        ];
    }
}

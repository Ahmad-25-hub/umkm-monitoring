<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateAvatarRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProfileAvatarController extends Controller
{
    public function update(UpdateAvatarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $newPath = $request->file('avatar')->store('avatars/'.$user->getKey(), 'public');

        if ($newPath === false) {
            return back()->withErrors([
                'avatar' => 'Foto profil gagal disimpan. Silakan coba lagi.',
            ], 'avatarUpdate');
        }

        $oldPath = $user->avatar_path;

        try {
            $user->avatar_path = $newPath;
            $user->save();
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);

            throw $exception;
        }

        if ($this->isOwnedAvatar($oldPath, $user)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('status', 'avatar-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $oldPath = $user->avatar_path;
        $user->avatar_path = null;
        $user->save();

        if ($this->isOwnedAvatar($oldPath, $user)) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('status', 'avatar-deleted');
    }

    private function isOwnedAvatar(?string $path, User $user): bool
    {
        return $path !== null && str_starts_with($path, 'avatars/'.$user->getKey().'/');
    }
}

<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_avatar_is_stored_for_authenticated_user(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 300, 300)->size(200),
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'avatar-updated');
        $path = $user->fresh()->avatar_path;
        $this->assertStringStartsWith('avatars/'.$user->id.'/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrorsIn('avatarUpdate', 'avatar');
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_oversized_avatar_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('large.jpg')->size(2049),
        ]);

        $response->assertSessionHasErrorsIn('avatarUpdate', [
            'avatar' => 'Ukuran foto profil maksimal 2 MB.',
        ]);
        $this->assertNull($user->fresh()->avatar_path);
    }

    public function test_replacing_avatar_deletes_previous_owned_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $oldPath = 'avatars/'.$user->id.'/old.jpg';
        $user->avatar_path = $oldPath;
        $user->save();
        Storage::disk('public')->put($oldPath, 'old-image');

        $this->actingAs($user)->patch(route('profile.avatar.update'), [
            'avatar' => UploadedFile::fake()->image('new.png'),
        ])->assertSessionHas('status', 'avatar-updated');

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_deleting_avatar_does_not_delete_another_users_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherPath = 'avatars/'.$otherUser->id.'/shared.jpg';
        Storage::disk('public')->put($otherPath, 'other-image');
        $user->avatar_path = $otherPath;
        $user->save();

        $response = $this->actingAs($user)->delete(route('profile.avatar.destroy'));

        $response->assertSessionHas('status', 'avatar-deleted');
        $this->assertNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertExists($otherPath);
    }
}

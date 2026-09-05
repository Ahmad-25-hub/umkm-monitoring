<?php

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OtherBrowserSessionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_account_settings_explains_when_session_management_is_not_supported(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.settings'))
            ->assertSeeText('Daftar perangkat hanya tersedia ketika aplikasi menggunakan penyimpanan sesi database.');
    }

    public function test_database_sessions_are_listed_with_masked_ip_addresses(): void
    {
        config()->set('session.driver', 'database');
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'other-session',
            'user_id' => $user->id,
            'ip_address' => '192.168.10.77',
            'user_agent' => 'Mozilla/5.0 Chrome/120.0',
            'payload' => '{}',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($user)->get(route('account.settings'));

        $response
            ->assertSeeText('Google Chrome · komputer')
            ->assertSeeText('192.168.10.xxx')
            ->assertDontSeeText('192.168.10.77');
    }

    public function test_other_sessions_are_not_deleted_when_password_is_wrong(): void
    {
        config()->set('session.driver', 'database');
        $user = User::factory()->create();
        $this->insertSession('other-session', $user->id);

        $response = $this->actingAs($user)->delete(route('account.sessions.destroy-others'), [
            'current_password' => 'password-salah',
        ]);

        $response->assertSessionHasErrorsIn('sessionDestroy', [
            'current_password' => 'Password saat ini tidak sesuai.',
        ]);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
    }

    public function test_other_sessions_are_deleted_without_affecting_another_users_sessions(): void
    {
        config()->set('session.driver', 'database');
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->insertSession('other-session', $user->id);
        $this->insertSession('foreign-session', $otherUser->id);

        $response = $this->actingAs($user)->delete(route('account.sessions.destroy-others'), [
            'current_password' => 'password',
        ]);

        $response->assertSessionHas('status', 'other-sessions-destroyed');
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'foreign-session', 'user_id' => $otherUser->id]);
        $this->assertAuthenticatedAs($user);
    }

    private function insertSession(string $id, int $userId): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '10.0.0.42',
            'user_agent' => 'Mozilla/5.0 Firefox/120.0',
            'payload' => '{}',
            'last_activity' => now()->timestamp,
        ]);
    }
}

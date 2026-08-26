<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_owner_overview_displays_the_primary_business_signals(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('Bisnis Anda tumbuh dengan baik hari ini.')
            ->assertSeeText('Business Health')
            ->assertSeeText('Rp24,8 jt')
            ->assertSeeText('Sales Performance')
            ->assertSeeText('NADI Insights')
            ->assertSeeText('Team Performance')
            ->assertSeeText('Needs Your Attention')
            ->assertSeeText('Ask NADI');
    }
}

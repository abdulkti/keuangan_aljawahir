<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class DashboardTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testDashboardLoadsWhenLoggedIn()
    {
        $this->loginAsAdmin();
        $result = $this->get('/dashboard');
        $result->assertStatus(200);
        $result->assertSee('Dashboard');
    }

    public function testDashboardShowsTotalSavings()
    {
        $this->loginAsAdmin();
        $result = $this->get('/dashboard');
        $result->assertStatus(200);
        // Total savings should be displayed (from seed data)
        $result->assertSee('Tabungan');
    }

    public function testDashboardRedirectsWhenNotLoggedIn()
    {
        $result = $this->get('/dashboard');
        $result->assertRedirectTo('/login');
    }
}

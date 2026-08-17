<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class AuthTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testLoginPageLoads()
    {
        $result = $this->get('/login');
        $result->assertStatus(200);
        $result->assertSee('Masuk');
    }

    public function testRootShowsLoginPage()
    {
        $result = $this->get('/');
        $result->assertStatus(200);
        $result->assertSee('Masuk');
    }

    public function testLoginWithValidCredentials()
    {
        $result = $this->post('/auth/login', [
            'email'    => 'admin@cendekiabangsa.sch.id',
            'password' => 'admin123',
        ]);
        $result->assertStatus(302);
        $this->assertNotNull(session('user'));

        // Clean up session for next test
        $this->session = [];
    }

    public function testLoginWithInvalidPassword()
    {
        $result = $this->post('/auth/login', [
            'email'    => 'admin@cendekiabangsa.sch.id',
            'password' => 'wrongpassword',
        ]);
        $result->assertStatus(200);
        $result->assertSee('Email atau kata sandi salah');
        $this->assertNull(session('user'));
    }

    public function testLoginWithInvalidEmail()
    {
        $result = $this->post('/auth/login', [
            'email'    => 'nonexistent@email.com',
            'password' => 'admin123',
        ]);
        $result->assertStatus(200);
        $result->assertSee('Email atau kata sandi salah');
        $this->assertNull(session('user'));
    }

    public function testDashboardRequiresLogin()
    {
        $result = $this->get('/dashboard');
        $result->assertRedirectTo('/login');
    }

    public function testLogout()
    {
        $this->loginAsAdmin();
        $result = $this->get('/auth/logout');
        $result->assertRedirectTo('/login');
        $this->assertNull(session('user'));
    }
}

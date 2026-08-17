<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class PengaturanTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/pengaturan');
        $result->assertStatus(200);
        $result->assertSee('Pengaturan');
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/pengaturan');
        $result->assertRedirectTo('/login');
    }

    public function testStoreUser()
    {
        $this->loginAsAdmin();
        $result = $this->post('/pengaturan/store', [
            'nama'     => 'Test User',
            'email'    => 'test@test.com',
            'password' => 'password123',
            'role'     => 'staff',
            'sekolah'  => 'smp',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('users', [
            'email'   => 'test@test.com',
            'nama'    => 'Test User',
            'role'    => 'staff',
            'sekolah' => 'smp',
        ]);
    }

    public function testUpdateUser()
    {
        $this->loginAsAdmin();
        $result = $this->post('/pengaturan/update', [
            'id'      => 3,
            'nama'    => 'Staff SMP Updated',
            'email'   => 'smp@aljawahir.sch.id',
            'role'    => 'staff',
            'sekolah' => 'smp',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('users', [
            'id'   => 3,
            'nama' => 'Staff SMP Updated',
        ]);
    }

    public function testDeleteUser()
    {
        $this->loginAsAdmin();
        $result = $this->post('/pengaturan/delete', ['id' => 5]);
        $result->assertStatus(302);
        $this->dontSeeInDatabase('users', ['id' => 5]);
    }
}

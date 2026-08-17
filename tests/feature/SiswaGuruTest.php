<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class SiswaGuruTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/siswaguru');
        $result->assertStatus(200);
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/siswaguru');
        $result->assertRedirectTo('/login');
    }

    public function testSiswaStore()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/siswa/store', [
            'nis'           => 'TEST001',
            'nama'          => 'Test Student',
            'jenis_kelamin' => 'L',
            'kelas_id'      => 15,
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('students', ['nis' => 'TEST001', 'nama' => 'Test Student']);
    }

    public function testSiswaUpdate()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/siswa/update', [
            'id'            => 1,
            'nis'           => '23010045',
            'nama'          => 'Aditya Pratama Updated',
            'jenis_kelamin' => 'L',
            'kelas_id'      => 15,
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('students', ['id' => 1, 'nama' => 'Aditya Pratama Updated']);
    }

    public function testSiswaDelete()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/siswa/delete', ['id' => 6]);
        $result->assertStatus(302);
        $this->seeInDatabase('students', ['id' => 6]);
        $updated = $this->grabFromDatabase('students', 'aktif', ['id' => 6]);
        $this->assertEquals(0, (int)$updated);
    }

    public function testGuruStore()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/guru/store', [
            'nip'           => '999999999999999999',
            'nama'          => 'Test Teacher',
            'jenis_kelamin' => 'P',
            'bidang'        => 'Matematika',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('teachers', ['nip' => '999999999999999999', 'nama' => 'Test Teacher']);
    }

    public function testGuruUpdate()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/guru/update', [
            'id'            => 1,
            'nip'           => '198503142010011001',
            'nama'          => 'Budi Santoso Updated',
            'jenis_kelamin' => 'L',
            'bidang'        => 'Fisika',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('teachers', ['id' => 1, 'nama' => 'Budi Santoso Updated']);
    }

    public function testGuruDelete()
    {
        $this->loginAsAdmin();
        $result = $this->post('/siswaguru/guru/delete', ['id' => 3]);
        $result->assertStatus(302);
        $this->seeInDatabase('teachers', ['id' => 3]);
        $updated = $this->grabFromDatabase('teachers', 'aktif', ['id' => 3]);
        $this->assertEquals(0, (int)$updated);
    }
}

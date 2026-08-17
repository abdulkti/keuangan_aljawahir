<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class TagihanTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/tagihan');
        $result->assertStatus(200);
        $result->assertSee('Tagihan');
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/tagihan');
        $result->assertRedirectTo('/login');
    }

    public function testCreateBill()
    {
        $this->loginAsAdmin();
        $result = $this->post('/tagihan/create', [
            'siswa_id'      => 1,
            'jenis_tagihan' => 'SPP Juli 2026',
            'nominal'       => 750000,
            'tahun_ajaran_id' => 1,
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('bills', [
            'siswa_id'      => 1,
            'jenis_tagihan' => 'SPP Juli 2026',
            'nominal'       => 750000,
        ]);
    }

    public function testDetailLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/tagihan/detail/1');
        $result->assertStatus(200);
    }

    public function testDetailRequiresLogin()
    {
        $result = $this->get('/tagihan/detail/1');
        $result->assertRedirectTo('/login');
    }

    public function testBayar()
    {
        $this->loginAsAdmin();
        $result = $this->post('/tagihan/bayar', [
            'tagihan_id'      => 2,
            'nominal_dibayar' => 750000,
            'metode'          => 'tunai',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('bill_payments', [
            'tagihan_id'      => 2,
            'nominal_dibayar' => 750000,
            'metode'          => 'tunai',
        ]);
        $this->seeInDatabase('bills', [
            'id'     => 2,
            'status' => 'lunas',
        ]);
    }
}

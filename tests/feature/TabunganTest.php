<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class TabunganTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/tabungan');
        $result->assertStatus(200);
        $result->assertSee('Tabungan');
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/tabungan');
        $result->assertRedirectTo('/login');
    }

    public function testCreateAccount()
    {
        $this->loginAsAdmin();
        $result = $this->post('/tabungan/create-account', [
            'tipe'    => 'siswa',
            'siswa_id' => 1,
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('savings_accounts', [
            'tipe'     => 'siswa',
            'siswa_id' => 1,
        ]);
    }

    public function testTransaksiSetor()
    {
        $this->loginAsAdmin();
        $result = $this->post('/tabungan/transaksi', [
            'akun_id' => 1,
            'tipe'    => 'setor',
            'nominal' => 100000,
            'metode'  => 'tunai',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('savings_transactions', [
            'akun_id' => 1,
            'tipe'    => 'setor',
            'nominal' => 100000,
        ]);
    }

    public function testTransaksiTarik()
    {
        $this->loginAsAdmin();
        $result = $this->post('/tabungan/transaksi', [
            'akun_id' => 1,
            'tipe'    => 'tarik',
            'nominal' => 50000,
            'metode'  => 'transfer',
        ]);
        $result->assertStatus(302);
        $this->seeInDatabase('savings_transactions', [
            'akun_id' => 1,
            'tipe'    => 'tarik',
            'nominal' => 50000,
        ]);
    }

    public function testRiwayatReturnsHtml()
    {
        $this->loginAsAdmin();
        $result = $this->get('/tabungan/riwayat/1');
        $result->assertStatus(200);
        $this->assertStringContainsString('<table', $result->getBody());
    }

    public function testRiwayatRequiresLogin()
    {
        $result = $this->get('/tabungan/riwayat/1');
        $result->assertStatus(302);
    }
}

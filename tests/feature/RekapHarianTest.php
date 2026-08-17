<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class RekapHarianTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/rekap-harian');
        $result->assertStatus(200);
        $result->assertSee('Rekap Harian');
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/rekap-harian');
        $result->assertRedirectTo('/login');
    }

    public function testIndexWithDateFilter()
    {
        $this->loginAsAdmin();
        $result = $this->get('/rekap-harian?tanggal=2026-06-18');
        $result->assertStatus(200);
    }

    public function testExportExcelReturnsFile()
    {
        $this->loginAsAdmin();
        $result = $this->get('/rekap-harian/export-excel');
        $result->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $result->getHeaderLine('Content-Type')
        );
    }

    public function testExportExcelRequiresLogin()
    {
        $result = $this->get('/rekap-harian/export-excel');
        $result->assertRedirectTo('/login');
    }
}

<?php

namespace Tests\Feature;

use Tests\Support\DatabaseTestCase;

class PembukuanTest extends DatabaseTestCase
{
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';

    public function testIndexLoads()
    {
        $this->loginAsAdmin();
        $result = $this->get('/pembukuan');
        $result->assertStatus(200);
        $result->assertSee('Pembukuan');
    }

    public function testIndexRequiresLogin()
    {
        $result = $this->get('/pembukuan');
        $result->assertRedirectTo('/login');
    }

    public function testExportExcelReturnsFile()
    {
        $this->loginAsAdmin();
        $result = $this->get('/pembukuan/export-excel');
        $result->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $result->getHeaderLine('Content-Type')
        );
    }

    public function testExportExcelRequiresLogin()
    {
        $result = $this->get('/pembukuan/export-excel');
        $result->assertRedirectTo('/login');
    }
}

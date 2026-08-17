<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

abstract class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'App';
    protected $seed = 'App\Database\Seeds\EduFinanceSeeder';
    protected $seedOnce = false;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function loginAsAdmin(): void
    {
        $this->session = [
            'user' => [
                'id'     => 1,
                'nama'   => 'Rina Amalia',
                'email'  => 'admin@cendekiabangsa.sch.id',
                'role'   => 'admin',
                'sekolah'=> 'admin',
            ],
        ];
    }

    protected function loginAsStaff(string $sekolah = 'smp'): void
    {
        $this->session = [
            'user' => [
                'id'     => 2,
                'nama'   => 'Budi Santoso',
                'email'  => 'budi@cendekiabangsa.sch.id',
                'role'   => 'staff',
                'sekolah'=> $sekolah,
            ],
        ];
    }
}

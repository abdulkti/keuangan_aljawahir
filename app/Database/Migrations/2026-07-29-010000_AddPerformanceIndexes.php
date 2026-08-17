<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceIndexes extends Migration
{
    public function up()
    {
        // tb_tabungan (savings accounts)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_tipe ON tb_tabungan (tipe)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_sekolah ON tb_tabungan (sekolah)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_aktif ON tb_tabungan (aktif)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_siswa_id ON tb_tabungan (siswa_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_guru_id ON tb_tabungan (guru_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_nasabah_id ON tb_tabungan (nasabah_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tabungan_tipe_aktif_sekolah ON tb_tabungan (tipe, aktif, sekolah)');

        // tb_siswa (students)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_siswa_tahun_ajaran_id ON tb_siswa (tahun_ajaran_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_siswa_kelas_id ON tb_siswa (kelas_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_siswa_sekolah ON tb_siswa (sekolah)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_siswa_nis ON tb_siswa (nis)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_siswa_aktif_status ON tb_siswa (aktif, status)');

        // tb_guru (teachers)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_guru_tahun_ajaran_id ON tb_guru (tahun_ajaran_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_guru_sekolah ON tb_guru (sekolah)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_guru_bidang ON tb_guru (bidang)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_guru_nip ON tb_guru (nip)');

        // tb_tagihan_siswa (bills)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_siswa_id ON tb_tagihan_siswa (siswa_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_jenis ON tb_tagihan_siswa (jenis_tagihan)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_tahun_ajaran_id ON tb_tagihan_siswa (tahun_ajaran_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_status ON tb_tagihan_siswa (status)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_jatuh_tempo ON tb_tagihan_siswa (jatuh_tempo)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_sekolah ON tb_tagihan_siswa (sekolah)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tagihan_siswa_jenis_ta ON tb_tagihan_siswa (siswa_id, jenis_tagihan, tahun_ajaran_id)');

        // tb_pembayaran (payments)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_tagihan_id ON tb_pembayaran (tagihan_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_created_at ON tb_pembayaran (created_at)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_user_id ON tb_pembayaran (user_id)');

        // tb_transaksi_tabungan (savings transactions)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_transaksi_akun_id ON tb_transaksi_tabungan (akun_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_transaksi_tipe ON tb_transaksi_tabungan (tipe)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_transaksi_created_at ON tb_transaksi_tabungan (created_at)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_transaksi_akun_tipe ON tb_transaksi_tabungan (akun_id, tipe)');

        // tb_kelas (classes)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kelas_sekolah ON tb_kelas (sekolah)');

        // tb_kas_unit (unit cash)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_unit_unit_id ON tb_kas_unit (unit_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_unit_tanggal ON tb_kas_unit (tanggal)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_unit_jenis ON tb_kas_unit (jenis)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_unit_status_tutup ON tb_kas_unit (status_tutup)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_unit_referensi ON tb_kas_unit (referensi_id, referensi_tipe)');

        // tb_kas_yayasan (foundation cash)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_yayasan_unit_id ON tb_kas_yayasan (unit_id)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_yayasan_tanggal ON tb_kas_yayasan (tanggal)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_kas_yayasan_jenis ON tb_kas_yayasan (jenis)');

        // tb_transaksi_tht (THT transactions)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_tht_guru_id ON tb_transaksi_tht (guru_id)');

        // tb_nasabah (customers)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_nasabah_nama ON tb_nasabah (nama)');

        // tb_users (users)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_users_email ON tb_users (email)');
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_users_role_sekolah ON tb_users (role, sekolah)');
    }

    public function down()
    {
        // tb_tabungan
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_tipe');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_sekolah');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_aktif');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_siswa_id');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_guru_id');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_nasabah_id');
        $this->db->query('DROP INDEX IF EXISTS idx_tabungan_tipe_aktif_sekolah');

        // tb_siswa
        $this->db->query('DROP INDEX IF EXISTS idx_siswa_tahun_ajaran_id');
        $this->db->query('DROP INDEX IF EXISTS idx_siswa_kelas_id');
        $this->db->query('DROP INDEX IF EXISTS idx_siswa_sekolah');
        $this->db->query('DROP INDEX IF EXISTS idx_siswa_nis');
        $this->db->query('DROP INDEX IF EXISTS idx_siswa_aktif_status');

        // tb_guru
        $this->db->query('DROP INDEX IF EXISTS idx_guru_tahun_ajaran_id');
        $this->db->query('DROP INDEX IF EXISTS idx_guru_sekolah');
        $this->db->query('DROP INDEX IF EXISTS idx_guru_bidang');
        $this->db->query('DROP INDEX IF EXISTS idx_guru_nip');

        // tb_tagihan_siswa
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_siswa_id');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_jenis');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_tahun_ajaran_id');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_status');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_jatuh_tempo');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_sekolah');
        $this->db->query('DROP INDEX IF EXISTS idx_tagihan_siswa_jenis_ta');

        // tb_pembayaran
        $this->db->query('DROP INDEX IF EXISTS idx_pembayaran_tagihan_id');
        $this->db->query('DROP INDEX IF EXISTS idx_pembayaran_created_at');
        $this->db->query('DROP INDEX IF EXISTS idx_pembayaran_user_id');

        // tb_transaksi_tabungan
        $this->db->query('DROP INDEX IF EXISTS idx_transaksi_akun_id');
        $this->db->query('DROP INDEX IF EXISTS idx_transaksi_tipe');
        $this->db->query('DROP INDEX IF EXISTS idx_transaksi_created_at');
        $this->db->query('DROP INDEX IF EXISTS idx_transaksi_akun_tipe');

        // tb_kelas
        $this->db->query('DROP INDEX IF EXISTS idx_kelas_sekolah');

        // tb_kas_unit
        $this->db->query('DROP INDEX IF EXISTS idx_kas_unit_unit_id');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_unit_tanggal');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_unit_jenis');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_unit_status_tutup');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_unit_referensi');

        // tb_kas_yayasan
        $this->db->query('DROP INDEX IF EXISTS idx_kas_yayasan_unit_id');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_yayasan_tanggal');
        $this->db->query('DROP INDEX IF EXISTS idx_kas_yayasan_jenis');

        // tb_transaksi_tht
        $this->db->query('DROP INDEX IF EXISTS idx_tht_guru_id');

        // tb_nasabah
        $this->db->query('DROP INDEX IF EXISTS idx_nasabah_nama');

        // tb_users
        $this->db->query('DROP INDEX IF EXISTS idx_users_email');
        $this->db->query('DROP INDEX IF EXISTS idx_users_role_sekolah');
    }
}

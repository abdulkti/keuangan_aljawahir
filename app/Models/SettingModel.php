<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingModel extends Model
{
    protected $table = 'tb_pengaturan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['key', 'value'];
    protected $useTimestamps = true;

    public function getSetting($key)
    {
        $row = $this->where('key', $key)->first();
        return $row ? $row['value'] : null;
    }

    public function setSetting($key, $value)
    {
        $row = $this->where('key', $key)->first();
        if ($row) {
            return $this->update($row['id'], ['value' => $value]);
        }
        return $this->insert(['key' => $key, 'value' => $value]);
    }
}

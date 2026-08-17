<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if ($this->isLoggedIn()) {
            $role = session()->get('user.role');
            $redirectUrl = ($role === 'admin') ? '/dashboard-yayasan' : '/dashboard';
            return redirect()->to($redirectUrl);
        }

        if ($this->request->getMethod() === 'POST') {
            $maxAttempts = 5;
            $lockoutSeconds = 900;

            $attempts = $this->session->get('login_attempts') ?? 0;
            $lockedUntil = $this->session->get('login_locked_until') ?? 0;

            if ($lockedUntil > time()) {
                $remaining = $lockedUntil - time();
                $data['error'] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($remaining / 60) . ' menit.';
            } else {
                $this->session->remove('login_locked_until');

                $email = $this->request->getPost('email');
                $password = $this->request->getPost('password');

                $userModel = new UserModel();
                $user = $userModel->where('email', $email)->where('aktif', 1)->first();

                if ($user && password_verify($password, $user['password'])) {
                    $this->session->remove('login_attempts');
                    $this->session->remove('login_locked_until');

                    $sessionToken = bin2hex(random_bytes(32));
                    $userModel->update($user['id'], [
                        'last_login' => date('Y-m-d H:i:s'),
                        'session_token' => $sessionToken,
                    ]);

                    $this->session->regenerate();
                    $settingModel = new \App\Models\SettingModel();
                    $this->session->set('user', [
                        'id' => $user['id'],
                        'nama' => $user['nama'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'sekolah' => $user['sekolah'],
                        'session_token' => $sessionToken,
                        'nama_sekolah' => $settingModel->getSetting('school_name') ?: 'Al-Jawahir Attarbawi',
                    ]);

                    $redirectUrl = ($user['role'] === 'admin') ? '/dashboard-yayasan' : '/dashboard';
                    return redirect()->to($redirectUrl)->with('success', 'Selamat datang kembali, ' . $user['nama'] . '!');
                }

                $attempts++;
                $this->session->set('login_attempts', $attempts);

                if ($attempts >= $maxAttempts) {
                    $this->session->set('login_locked_until', time() + $lockoutSeconds);
                    $data['error'] = 'Terlalu banyak percobaan gagal. Akun dikunci selama 15 menit.';
                } else {
                    $remaining = $maxAttempts - $attempts;
                    $data['error'] = 'Email atau kata sandi salah. Sisa percobaan: ' . $remaining;
                }
            }
        }

        $data['title'] = 'Masuk';
        $settingModel = new \App\Models\SettingModel();
        $data['schoolName'] = $settingModel->getSetting('school_name') ?: 'Al-Jawahir Attarbawi';
        return view('auth/login', $data);
    }

    public function logout()
    {
        if ($this->request->getMethod() !== 'POST') {
            return redirect()->to('/dashboard');
        }

        $user = $this->session->get('user');
        if ($user) {
            $userModel = new UserModel();
            $userModel->update($user['id'], ['session_token' => null]);
        }

        $this->session->remove('user');
        return redirect()->to('/login')->with('success', 'Anda berhasil keluar.');
    }
}

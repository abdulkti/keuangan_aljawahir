<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        $userData = $session->get('user');

        if (!$userData) {
            return redirect()->to('/login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->find($userData['id']);

        if (!$user || !$user['aktif'] || $user['session_token'] !== ($userData['session_token'] ?? null)) {
            $session->remove('user');

            if ($request->isAJAX()) {
                $response = service('response');
                $response->setStatusCode(401);
                $response->setJSON(['redirect' => '/login']);
                return $response;
            }

            return redirect()->to('/login')->with('error', 'Sesi telah berakhir. Silakan login kembali.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}

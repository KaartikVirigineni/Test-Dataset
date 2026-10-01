<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTLibrary;

class AuthController extends BaseController
{
    public function register()
    {
        $rules = [
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'name' => 'required|min_length[3]'
        ];

        if (!$this->validate($rules)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(400);
        }

        $model = new UserModel();
        $data = [
            'email' => $this->request->getVar('email'),
            'password' => password_hash($this->request->getVar('password'), PASSWORD_DEFAULT),
            'name' => $this->request->getVar('name'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $userId = $model->insert($data);

        return $this->response
            ->setJSON([
                'message' => 'User registered successfully',
                'user_id' => $userId
            ])
            ->setStatusCode(201);
    }

    public function login()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(400);
        }

        $model = new UserModel();
        $user = $model->where('email', $this->request->getVar('email'))->first();

        if (!$user || !password_verify($this->request->getVar('password'), $user['password'])) {
            return $this->response
                ->setJSON(['error' => 'Invalid credentials'])
                ->setStatusCode(401);
        }

        $jwt = new JWTLibrary();
        $token = $jwt->encode([
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);

        return $this->response->setJSON([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name']
            ]
        ]);
    }
}
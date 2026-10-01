<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Libraries\JWTHelper;

class Auth extends BaseController
{
    protected $userModel;
    protected $jwtHelper;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->jwtHelper = new JWTHelper();
    }

    public function register()
    {
        $json = $this->request->getJSON();
        
        if (!isset($json->email) || !isset($json->password)) {
            return $this->errorResponse('Email and password are required', 400);
        }

        if ($this->userModel->where('email', $json->email)->first()) {
            return $this->errorResponse('User already exists', 409);
        }

        $userId = $this->userModel->insert([
            'email' => $json->email,
            'password' => password_hash($json->password, PASSWORD_DEFAULT),
            'name' => $json->name ?? null,
        ]);

        $user = $this->userModel->find($userId);
        $token = $this->jwtHelper->generateToken($user);

        return $this->jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
            ]
        ], 201);
    }

    public function login()
    {
        $json = $this->request->getJSON();
        
        if (!isset($json->email) || !isset($json->password)) {
            return $this->errorResponse('Email and password are required', 400);
        }

        $user = $this->userModel->where('email', $json->email)->first();

        if (!$user || !password_verify($json->password, $user['password'])) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        $token = $this->jwtHelper->generateToken($user);

        return $this->jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
            ]
        ]);
    }
}
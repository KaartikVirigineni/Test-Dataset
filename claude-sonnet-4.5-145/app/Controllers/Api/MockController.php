<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\MockModel;

class MockController extends BaseController
{
    protected $mockModel;

    public function __construct()
    {
        $this->mockModel = new MockModel();
    }

    public function index()
    {
        $user = $this->request->user;
        
        if ($user['role'] === 'admin') {
            $mocks = $this->mockModel->findAll();
        } else {
            $mocks = $this->mockModel->where('user_id', $user['id'])->findAll();
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $mocks
        ]);
    }

    public function show($id)
    {
        $user = $this->request->user;
        $mock = $this->mockModel->find($id);

        if (!$mock) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Mock not found'
            ])->setStatusCode(404);
        }

        if ($user['role'] !== 'admin' && $mock['user_id'] != $user['id']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data' => $mock
        ]);
    }

    public function create()
    {
        $user = $this->request->user;

        $rules = [
            'name' => 'required|min_length[3]|max_length[100]',
            'endpoint' => 'required|max_length[255]',
            'method' => 'required|in_list[GET,POST,PUT,PATCH,DELETE]',
            'response_body' => 'required',
            'status_code' => 'permit_empty|integer|greater_than[99]|less_than[600]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $data = [
            'user_id' => $user['id'],
            'name' => $this->request->getVar('name'),
            'endpoint' => $this->request->getVar('endpoint'),
            'method' => $this->request->getVar('method'),
            'response_body' => $this->request->getVar('response_body'),
            'status_code' => $this->request->getVar('status_code') ?? 200,
        ];

        $mockId = $this->mockModel->insert($data);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mock created successfully',
            'data' => $this->mockModel->find($mockId)
        ])->setStatusCode(201);
    }

    public function update($id)
    {
        $user = $this->request->user;
        $mock = $this->mockModel->find($id);

        if (!$mock) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Mock not found'
            ])->setStatusCode(404);
        }

        if ($user['role'] !== 'admin' && $mock['user_id'] != $user['id']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        $rules = [
            'name' => 'permit_empty|min_length[3]|max_length[100]',
            'endpoint' => 'permit_empty|max_length[255]',
            'method' => 'permit_empty|in_list[GET,POST,PUT,PATCH,DELETE]',
            'response_body' => 'permit_empty',
            'status_code' => 'permit_empty|integer|greater_than[99]|less_than[600]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $this->validator->getErrors()
            ])->setStatusCode(400);
        }

        $data = array_filter([
            'name' => $this->request->getVar('name'),
            'endpoint' => $this->request->getVar('endpoint'),
            'method' => $this->request->getVar('method'),
            'response_body' => $this->request->getVar('response_body'),
            'status_code' => $this->request->getVar('status_code'),
        ]);

        $this->mockModel->update($id, $data);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mock updated successfully',
            'data' => $this->mockModel->find($id)
        ]);
    }

    public function delete($id)
    {
        $user = $this->request->user;
        $mock = $this->mockModel->find($id);

        if (!$mock) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Mock not found'
            ])->setStatusCode(404);
        }

        if ($user['role'] !== 'admin' && $mock['user_id'] != $user['id']) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        $this->mockModel->delete($id);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Mock deleted successfully'
        ]);
    }
}
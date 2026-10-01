<?php

namespace App\Controllers;

use App\Models\ProjectModel;

class ProjectController extends BaseController
{
    private ProjectModel $model;

    public function __construct()
    {
        $this->model = new ProjectModel();
    }

    public function index()
    {
        $projects = $this->model->findAll();
        return $this->response->setJSON($projects);
    }

    public function show($id)
    {
        $project = $this->model->find($id);
        
        if (!$project) {
            return $this->response
                ->setJSON(['error' => 'Project not found'])
                ->setStatusCode(404);
        }

        return $this->response->setJSON($project);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[3]',
            'description' => 'required',
            'repository_url' => 'valid_url'
        ];

        if (!$this->validate($rules)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(400);
        }

        $data = [
            'name' => $this->request->getVar('name'),
            'description' => $this->request->getVar('description'),
            'repository_url' => $this->request->getVar('repository_url'),
            'status' => $this->request->getVar('status') ?? 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->model->insert($data);

        return $this->response
            ->setJSON(['id' => $id, 'message' => 'Project created'])
            ->setStatusCode(201);
    }

    public function update($id)
    {
        $project = $this->model->find($id);
        
        if (!$project) {
            return $this->response
                ->setJSON(['error' => 'Project not found'])
                ->setStatusCode(404);
        }

        $data = array_filter([
            'name' => $this->request->getVar('name'),
            'description' => $this->request->getVar('description'),
            'repository_url' => $this->request->getVar('repository_url'),
            'status' => $this->request->getVar('status'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->model->update($id, $data);

        return $this->response->setJSON(['message' => 'Project updated']);
    }

    public function delete($id)
    {
        $project = $this->model->find($id);
        
        if (!$project) {
            return $this->response
                ->setJSON(['error' => 'Project not found'])
                ->setStatusCode(404);
        }

        $this->model->delete($id);

        return $this->response->setJSON(['message' => 'Project deleted']);
    }
}
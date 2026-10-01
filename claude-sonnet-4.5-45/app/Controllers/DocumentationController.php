<?php

namespace App\Controllers;

use App\Models\DocumentationModel;

class DocumentationController extends BaseController
{
    private DocumentationModel $model;

    public function __construct()
    {
        $this->model = new DocumentationModel();
    }

    public function index()
    {
        $docs = $this->model->findAll();
        return $this->response->setJSON($docs);
    }

    public function show($id)
    {
        $doc = $this->model->find($id);
        
        if (!$doc) {
            return $this->response
                ->setJSON(['error' => 'Documentation not found'])
                ->setStatusCode(404);
        }

        return $this->response->setJSON($doc);
    }

    public function create()
    {
        $rules = [
            'title' => 'required|min_length[3]',
            'content' => 'required',
            'project_id' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(400);
        }

        $data = [
            'title' => $this->request->getVar('title'),
            'content' => $this->request->getVar('content'),
            'project_id' => $this->request->getVar('project_id'),
            'category' => $this->request->getVar('category') ?? 'general',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $id = $this->model->insert($data);

        return $this->response
            ->setJSON(['id' => $id, 'message' => 'Documentation created'])
            ->setStatusCode(201);
    }

    public function update($id)
    {
        $doc = $this->model->find($id);
        
        if (!$doc) {
            return $this->response
                ->setJSON(['error' => 'Documentation not found'])
                ->setStatusCode(404);
        }

        $data = array_filter([
            'title' => $this->request->getVar('title'),
            'content' => $this->request->getVar('content'),
            'project_id' => $this->request->getVar('project_id'),
            'category' => $this->request->getVar('category'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $this->model->update($id, $data);

        return $this->response->setJSON(['message' => 'Documentation updated']);
    }

    public function delete($id)
    {
        $doc = $this->model->find($id);
        
        if (!$doc) {
            return $this->response
                ->setJSON(['error' => 'Documentation not found'])
                ->setStatusCode(404);
        }

        $this->model->delete($id);

        return $this->response->setJSON(['message' => 'Documentation deleted']);
    }
}
<?php
declare(strict_types=1);

namespace App\Controller;

class FlagsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadModel('Flags');
    }

    public function index()
    {
        $flags = $this->Flags->find('all')->toArray();
        
        $this->setJsonResponse([
            'flags' => $flags,
            'count' => count($flags),
        ]);
    }

    public function view($id)
    {
        $flag = $this->Flags->get($id);
        
        $this->setJsonResponse([
            'flag' => $flag,
        ]);
    }

    public function add()
    {
        $this->request->allowMethod(['post']);
        
        $flag = $this->Flags->newEntity($this->request->getData());
        
        if ($this->Flags->save($flag)) {
            $this->setJsonResponse([
                'message' => 'Flag created successfully',
                'flag' => $flag,
            ], 201);
        } else {
            $this->setJsonResponse([
                'error' => 'Failed to create flag',
                'errors' => $flag->getErrors(),
            ], 400);
        }
    }

    public function edit($id)
    {
        $this->request->allowMethod(['put', 'patch']);
        
        $flag = $this->Flags->get($id);
        $flag = $this->Flags->patchEntity($flag, $this->request->getData());
        
        if ($this->Flags->save($flag)) {
            $this->setJsonResponse([
                'message' => 'Flag updated successfully',
                'flag' => $flag,
            ]);
        } else {
            $this->setJsonResponse([
                'error' => 'Failed to update flag',
                'errors' => $flag->getErrors(),
            ], 400);
        }
    }

    public function delete($id)
    {
        $this->request->allowMethod(['delete']);
        
        $flag = $this->Flags->get($id);
        
        if ($this->Flags->delete($flag)) {
            $this->setJsonResponse([
                'message' => 'Flag deleted successfully',
            ]);
        } else {
            $this->setJsonResponse([
                'error' => 'Failed to delete flag',
            ], 400);
        }
    }

    public function toggle($id)
    {
        $this->request->allowMethod(['post']);
        
        $flag = $this->Flags->get($id);
        $flag->enabled = !$flag->enabled;
        
        if ($this->Flags->save($flag)) {
            $this->setJsonResponse([
                'message' => 'Flag toggled successfully',
                'flag' => $flag,
            ]);
        } else {
            $this->setJsonResponse([
                'error' => 'Failed to toggle flag',
            ], 400);
        }
    }

    public function getByKey($key)
    {
        $this->request->allowMethod(['get']);
        
        $flag = $this->Flags->find()
            ->where(['flag_key' => $key])
            ->first();

        if (!$flag) {
            $this->setJsonResponse([
                'error' => 'Flag not found',
            ], 404);
            return;
        }

        $this->setJsonResponse([
            'key' => $flag->flag_key,
            'enabled' => $flag->enabled,
            'value' => $flag->config_value,
        ]);
    }
}
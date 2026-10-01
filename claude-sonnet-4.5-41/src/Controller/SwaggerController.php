<?php
declare(strict_types=1);

namespace App\Controller;

class SwaggerController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['spec', 'ui']);
    }

    public function spec()
    {
        $specPath = ROOT . DS . 'openapi.yaml';
        
        if (!file_exists($specPath)) {
            $this->response = $this->response->withStatus(404);
            $this->setJsonResponse(['error' => 'OpenAPI spec not found']);
            return;
        }

        $this->response = $this->response
            ->withType('application/yaml')
            ->withFile($specPath, ['download' => false]);
    }

    public function ui()
    {
        $this->viewBuilder()->setLayout(false);
        $this->set('specUrl', '/swagger');
    }
}
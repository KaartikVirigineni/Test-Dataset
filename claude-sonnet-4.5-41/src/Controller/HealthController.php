<?php
declare(strict_types=1);

namespace App\Controller;

class HealthController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['index']);
    }

    public function index()
    {
        $this->setJsonResponse([
            'status' => 'healthy',
            'service' => 'feature-flags',
            'timestamp' => time(),
        ]);
    }
}
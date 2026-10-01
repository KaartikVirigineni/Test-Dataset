<?php
declare(strict_types=1);

namespace App\Controller;

class AuthController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['login']);
    }

    public function login()
    {
        $this->request->allowMethod(['post']);
        
        $data = $this->request->getData();
        
        if (empty($data['username']) || empty($data['password'])) {
            $this->setJsonResponse([
                'error' => 'Username and password are required'
            ], 400);
            return;
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->find()
            ->where([
                'username' => $data['username'],
                'password' => hash('sha256', $data['password']),
            ])
            ->first();

        if (!$user) {
            $this->setJsonResponse([
                'error' => 'Invalid credentials'
            ], 401);
            return;
        }

        $this->setJsonResponse([
            'token' => $user->token,
            'username' => $user->username,
        ]);
    }
}
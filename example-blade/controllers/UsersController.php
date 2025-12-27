<?php

use PaigeJulianne\PicoMVC\Controller;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;

class UsersController extends Controller
{
    private array $users = [
        ['id' => 1, 'name' => 'Alice Johnson', 'email' => 'alice@example.com'],
        ['id' => 2, 'name' => 'Bob Smith', 'email' => 'bob@example.com'],
        ['id' => 3, 'name' => 'Carol Williams', 'email' => 'carol@example.com'],
    ];

    public function index(Request $request): Response
    {
        return $this->view('users.index', [
            'title' => 'Users',
            'users' => $this->users,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $user = null;

        foreach ($this->users as $u) {
            if ($u['id'] === $id) {
                $user = $u;
                break;
            }
        }

        if (!$user) {
            return $this->view('errors.404', [], 404);
        }

        return $this->view('users.show', [
            'title' => $user['name'],
            'user' => $user,
        ]);
    }
}

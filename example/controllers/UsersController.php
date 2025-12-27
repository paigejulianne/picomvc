<?php

use PaigeJulianne\PicoMVC\Controller;
use PaigeJulianne\PicoMVC\Request;
use PaigeJulianne\PicoMVC\Response;
use PaigeJulianne\PicoMVC\ValidationException;

class UsersController extends Controller
{
    /**
     * Display a list of users
     */
    public function index(Request $request): Response
    {
        // In a real app, you'd fetch from database using PicoORM:
        // $users = Users::getAllObjects();

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
            ['id' => 3, 'name' => 'Charlie', 'email' => 'charlie@example.com'],
        ];

        return $this->view('users.index', [
            'title' => 'Users',
            'users' => $users,
        ]);
    }

    /**
     * Display a single user
     */
    public function show(Request $request): Response
    {
        $id = $request->param('id');

        // In a real app: $user = new Users($id);

        $user = [
            'id' => $id,
            'name' => 'User ' . $id,
            'email' => 'user' . $id . '@example.com',
        ];

        return $this->view('users.show', [
            'title' => 'User Details',
            'user' => $user,
        ]);
    }

    /**
     * Store a new user
     */
    public function store(Request $request): Response
    {
        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
            ]);

            // In a real app:
            // $user = new Users();
            // $user->setMulti($data);
            // $user->save();

            return $this->redirect('/users');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return $e->toResponse();
            }

            return $this->view('users.create', [
                'title' => 'Create User',
                'errors' => $e->getErrors(),
                'old' => $request->all(),
            ]);
        }
    }

    /**
     * Update an existing user
     */
    public function update(Request $request): Response
    {
        $id = $request->param('id');

        try {
            $data = $this->validate([
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
            ]);

            // In a real app:
            // $user = new Users($id);
            // $user->setMulti($data);
            // $user->save();

            return $this->redirect('/users/' . $id);
        } catch (ValidationException $e) {
            return $e->toResponse();
        }
    }

    /**
     * Delete a user
     */
    public function destroy(Request $request): Response
    {
        $id = $request->param('id');

        // In a real app:
        // $user = new Users($id);
        // $user->delete();

        if ($request->expectsJson()) {
            return $this->json(['message' => 'User deleted']);
        }

        return $this->redirect('/users');
    }

    /**
     * API: List all users as JSON
     */
    public function apiIndex(Request $request): Response
    {
        // In a real app: $users = Users::getAllObjects();

        $users = [
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
            ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
        ];

        return $this->json([
            'data' => $users,
            'total' => count($users),
        ]);
    }

    /**
     * API: Get a single user as JSON
     */
    public function apiShow(Request $request): Response
    {
        $id = $request->param('id');

        // In a real app: $user = new Users($id);

        return $this->json([
            'id' => (int)$id,
            'name' => 'User ' . $id,
            'email' => 'user' . $id . '@example.com',
        ]);
    }
}

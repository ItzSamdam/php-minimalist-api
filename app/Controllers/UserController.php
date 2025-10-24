<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use App\Models\User;
use App\Core\Response;
use App\Core\Request;
use App\Core\Exceptions\ValidationException;
use App\Core\Validation\RuleFactory as Rule;
use Respect\Validation\Validator as v;

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    public function index(): string
    {
        try {
            $queryParams = Request::getQueryParams();

            // Add pagination and filtering
            $page = max(1, (int) ($queryParams['page'] ?? 1));
            $limit = min(100, max(1, (int) ($queryParams['limit'] ?? 20)));
            $offset = ($page - 1) * $limit;

            $users = $this->userRepository->findAll();
            $total = count($users);

            // Simple pagination (in real app, implement in repository)
            $paginatedUsers = array_slice($users, $offset, $limit);

            return Response::success([
                'data' => $paginatedUsers,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (\Exception $e) {
            return Response::error('Failed to fetch users', 500);
        }
    }

    public function show(int $id): string
    {
        try {
            $user = $this->userRepository->find($id);

            if (!$user) {
                return Response::notFound('User not found');
            }

            return Response::success($user);
        } catch (\Exception $e) {
            return Response::error('Failed to fetch user', 500);
        }
    }

    public function store(): string
    {
        try {
            // Strict validation using Respect/Validation
            $rules = [
                'name' => Rule::allOf(
                    Rule::required(),
                    Rule::name(),
                    Rule::minLength(2),
                    Rule::maxLength(100)
                ),
                'email' => Rule::allOf(
                    Rule::required(),
                    Rule::email(),
                    Rule::maxLength(255)
                ),
                'password' => Rule::optional(
                    Rule::password()
                )
            ];

            $data = Request::validate($rules);

            // Check if email already exists
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser) {
                return Response::error('Email already exists', 409);
            }

            $user = new User($data);
            $userId = $this->userRepository->createUser($user);

            return Response::created(['id' => $userId], 'User created successfully');
        } catch (ValidationException $e) {
            return Response::error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return Response::error('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    public function update(int $id): string
    {
        try {
            $existingUser = $this->userRepository->find($id);
            if (!$existingUser) {
                return Response::notFound('User not found');
            }

            // Different validation for update (some fields optional)
            $rules = [
                'name' => Rule::optional(
                    Rule::allOf(
                        Rule::name(),
                        Rule::minLength(2),
                        Rule::maxLength(100)
                    )
                ),
                'email' => Rule::optional(
                    Rule::allOf(
                        Rule::email(),
                        Rule::maxLength(255)
                    )
                )
            ];

            $data = Request::validate($rules);

            // Check if email is taken by another user
            if (isset($data['email'])) {
                $userWithEmail = $this->userRepository->findByEmail($data['email']);
                if ($userWithEmail && $userWithEmail->id != $id) {
                    return Response::error('Email already taken', 409);
                }
            }

            $user = new User(array_merge((array) $existingUser, $data));
            $success = $this->userRepository->updateUser($id, $user);

            if ($success) {
                return Response::success([], 'User updated successfully');
            }

            return Response::error('Failed to update user', 500);
        } catch (ValidationException $e) {
            return Response::error('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return Response::error('Failed to update user', 500);
        }
    }

    public function destroy(int $id): string
    {
        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                return Response::notFound('User not found');
            }

            $success = $this->userRepository->delete($id);

            if ($success) {
                return Response::success([], 'User deleted successfully');
            }

            return Response::error('Failed to delete user', 500);
        } catch (\Exception $e) {
            return Response::error('Failed to delete user', 500);
        }
    }

    public function restore(int $id): string
    {
        try {
            $user = $this->userRepository->findWithTrashed($id);
            if (!$user) {
                return Response::notFound('User not found');
            }

            $success = $this->userRepository->restore($id);

            if ($success) {
                return Response::success([], 'User restored successfully');
            }

            return Response::error('Failed to restore user', 500);
        } catch (\Exception $e) {
            return Response::error('Failed to restore user', 500);
        }
    }
}

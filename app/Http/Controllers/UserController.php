<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(
            [
                'page'     => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1'],
            ]
        );

        $per_page = $data['per_page'] ?? 15;
        $page     = $data['page'] ?? 1;

        return $this->successResponse(
            data: User::query()
                ->latest()
                ->paginate(page: $page, perPage: $per_page)
                ->getCollection(),
            responseMessage: 'Successfully retrieved users',
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = User::create($this->validatedUserData($request));

        return $this->successResponse(
            data: $user->fresh(),
            responseMessage: 'Successfully created user',
            statusCode: 201,
        );
    }

    public function show(User $user): JsonResponse
    {
        return $this->successResponse(
            data: $user,
            responseMessage: 'Successfully retrieved user',
        );
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $user->update($this->validatedUserData($request, $user));

        return $this->successResponse(
            data: $user->fresh(),
            responseMessage: 'Successfully updated user',
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return $this->successResponse(
            responseMessage: 'Successfully deleted user',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUserData(Request $request, ?User $user = null): array
    {
        $required = $user === null ? 'required' : 'sometimes';

        return $request->validate([
            'nik'            => [$required, 'string', 'max:16', Rule::unique('users', 'nik')->ignore($user?->id)],
            'nama_lengkap'   => [$required, 'string', 'max:255'],
            'tempat_lahir'   => [$required, 'string', 'max:255'],
            'tanggal_lahir'  => [$required, 'date'],
            'alamat'         => [$required, 'string'],
            'kelamin'        => [$required, Rule::in(['L', 'P'])],
            'nama_keluarga'  => [$required, 'string', 'max:255'],
            'poin_keaktifan' => ['sometimes', 'integer', 'min:0'],
            'role'           => ['sometimes', Rule::in(['admin', 'warga'])],
            'email'          => [$required, 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password'       => [$required, 'string', 'min:8'],
        ]);
    }
}

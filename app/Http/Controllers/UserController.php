<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListUsersRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $dados = $request->validated();
        $query = User::query()->orderByRaw(
            "CASE WHEN perfil = 'administrador' THEN 0 ELSE 1 END",
        );

        if (filled($dados['q'] ?? null)) {
            $busca = trim($dados['q']);
            $query->where(static fn (Builder $query): Builder => $query
                ->where('name', 'like', "%{$busca}%")
                ->orWhere('email', 'like', "%{$busca}%"));
        }

        if (isset($dados['perfil'])) {
            $query->where('perfil', $dados['perfil']);
        }

        if (array_key_exists('ativo', $dados)) {
            $query->where('ativo', (bool) $dados['ativo']);
        }

        return UserResource::collection($query->get());
    }

    public function show(User $usuario): UserResource
    {
        $this->authorize('view', $usuario);

        return new UserResource($usuario);
    }

    public function update(UpdateUserRequest $request, User $usuario): UserResource|JsonResponse
    {
        $dados = $request->validated();

        if (array_key_exists('ativo', $dados) && ! $dados['ativo']) {
            if ($usuario->is($request->user())) {
                return response()->json([
                    'message' => 'Você não pode desativar o próprio usuário.',
                ], 422);
            }

            if ($usuario->isAdministrador()) {
                return response()->json([
                    'message' => 'O usuário administrador principal não pode ser desativado.',
                ], 422);
            }
        }

        if (blank($dados['password'] ?? null)) {
            unset($dados['password']);
        }

        $usuario->update($dados);

        return new UserResource($usuario->refresh());
    }
}

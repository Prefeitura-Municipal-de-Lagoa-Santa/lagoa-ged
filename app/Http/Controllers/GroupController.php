<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use App\Models\User;
//use Illuminate\Http\Request;
use App\Http\Requests\UpdateGroupRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;
use Illuminate\Http\JsonResponse;
use Redirect;
use Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return Inertia::render('permissions/groups', [
            'groups' => $groups,
            'filters' => [],
        ]);
    }
    public function edit(Group $group)
    {
        $allUsers = User::query()
            ->select('_id', 'full_name', 'username', 'email')
            ->get();
        //dd($allUsers);

        return Inertia::render('permissions/groupEdit', [
            'group' => $group,
            'allUsers' => $allUsers,
        ]);
    }
    public function update(UpdateGroupRequest $request, Group $group)
    {
        // A validação já aconteceu! Se o código chegou até aqui, os dados são válidos.
        // Use $request->validated() para pegar apenas os dados que passaram na validação.
        $validatedData = $request->validated();

        // Use o método update() do Eloquent para um código mais limpo
        // (Requer a propriedade $fillable no seu modelo Group)
        $group->update($validatedData);

        return Redirect::back()->with('success', 'Grupo atualizado com sucesso!');
    }
    public function create()
    {
        $allUsers = User::query()
            ->select('_id', 'full_name', 'username', 'email')
            ->get();
        //dd($allUsers);

        return Inertia::render('permissions/groupCreate', [
            'allUsers' => $allUsers,
        ]);
    }
    public function store(StoreGroupRequest $request)
    {
        $validatedData = $request->validated();

        Group::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'],
            'user_ids' => $validatedData['user_ids'],
        ]);

        return to_route('groups.index')
            ->with('flash.success', 'Grupo criado com sucesso!');
    }

    public function destroy(Group $group)
    {
        try {
            // 1. Tenta autorizar a ação
            $this->authorize('delete', $group);

        } catch (AuthorizationException $e) {
            // 2. Se a autorização falhar, a exceção é capturada AQUI.
            // Em vez de deixar o Laravel mostrar a página 403, nós fazemos nosso próprio redirecionamento.
            return redirect()->back()->with('error', 'O grupo "' . $group->name . '" não pode ser excluído.');
        }

        // 3. Se o código chegou até aqui, a autorização passou.
        $group->delete();

        // E retornamos com a mensagem de sucesso.
        return redirect()->route('groups.index')->with('success', 'Grupo excluído com sucesso!');
    }

}

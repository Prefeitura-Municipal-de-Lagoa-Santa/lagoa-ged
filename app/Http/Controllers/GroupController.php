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
use MongoDB\BSON\ObjectId;

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

        $validatedData = $request->validated();

        if ($group->is_protected) {
            $protectedUserNames = config('permissions.protected_usernames', []);
            $protectedUsers = User::whereIn('username', $protectedUserNames)->pluck('id')->toArray();
            if (!empty($protectedUsers)) {
                $currentUserIds = $validatedData['user_ids'] ?? [];
                $finalUserIds = array_unique(array_merge($currentUserIds, $protectedUsers));
                $validatedData['user_ids'] = array_values($finalUserIds);
                
            }
        }

        // --- Início da modificação para converter IDs para ObjectIds ---
        if (isset($validatedData['user_ids']) && is_array($validatedData['user_ids'])) {
            $validatedData['user_ids'] = collect(value: $validatedData['user_ids'])
                ->map(callback: function ($id) {
                    // Garante que o ID é uma string válida/convertível antes de criar ObjectId
                    if (!empty($id)) {
                        return new ObjectId($id);
                    }
                    return null; // Lida com IDs vazios/inválidos, se necessário
                })
                ->filter() // Remove quaisquer nulos que resultaram de IDs inválidos
                ->toArray();
        }
        // --- Fim da modificação ---
        

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

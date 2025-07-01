<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Redirect;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::query()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return Inertia::render('permissions/users', [
            'users' => $users,
            'filters' => [],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $allGroups = Group::query()
            ->orderBy('name')
            ->select('_id', 'name')
            ->get();

        $userGroups = Group::query()
            ->select('_id', 'name')
            ->where('user_ids', $user->id)
            ->get();
        //dd($groups);

        return Inertia::render('permissions/userEdit', [
            'user' => $user,
            'userGroups' => $userGroups,
            'allGroups' => $allGroups,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $validated = $request->validated();

        // Sua lógica de atualização dos campos do usuário permanece a mesma
        if (!$user->is_ldap || !$user->is_protected) {
            $user->update([
                'full_name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
            ]);
        }

        // --- INÍCIO DA LÓGICA DE GRUPOS QUE VOCÊ PREFERE ---

        // 1. Pega os IDs dos grupos que vieram do formulário.
        $newGroupIds = $validated['userGroups'] ?? [];

        // 2. Se o usuário for protegido, garanta que ele permaneça em TODOS os grupos protegidos.
        if ($user->is_protected) {
            // Busca os nomes dos grupos protegidos do arquivo de configuração.
            $protectedGroupNames = config('permissions.protected_groups', []);

            if (!empty($protectedGroupNames)) {
                // Busca no banco os IDs de todos os grupos cujos nomes estão na lista de protegidos.
                $protectedGroupIds = Group::whereIn('name', $protectedGroupNames)->pluck('id')->toArray();
                //dd($protectedGroupIds);
                // Une o array de grupos do formulário com os IDs dos grupos protegidos.
                // A função array_unique() remove quaisquer duplicatas que possam surgir.
                $newGroupIds = array_unique(array_merge($newGroupIds, $protectedGroupIds));
            }
        }

        // 3. REMOVE o usuário de TODOS os grupos que ele fazia parte anteriormente.
        // Esta abordagem é eficiente e limpa o estado antigo completamente.
        Group::where('user_ids', $user->id)->pull('user_ids', $user->id);

        // 4. ADICIONA o usuário à lista final e correta de grupos.
        // A lista `$newGroupIds` já foi tratada para incluir os grupos protegidos, se necessário.
        if (!empty($newGroupIds)) {
            Group::whereIn('id', $newGroupIds)->push('user_ids', $user->id);
        }

        // --- FIM DA LÓGICA DE GRUPOS ---

        return Redirect::route('users.edit', $user->id)->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}

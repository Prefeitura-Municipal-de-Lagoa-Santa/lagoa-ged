<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Group;
use App\Models\User;
use Hash;
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
        $allGroups = Group::query()
            ->orderBy('name')
            ->select('_id', 'name')
            ->get();
       
        return Inertia::render('permissions/userCreate', [
            'allGroups' => $allGroups,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();

        // 1. Criar o novo usuário
        $user = User::create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Criptografa a senha
        ]);
        
        //dd($user->id);
        // --- INÍCIO DA LÓGICA DE GRUPOS (SEMELHANTE AO UPDATE) ---

        // Pega os IDs dos grupos que vieram do formulário.
        $newGroupIds = $validated['userGroups'] ?? [];

        // No momento da criação, um novo usuário não é 'is_protected' por padrão.
        // Se houver uma lógica para novos usuários serem protegidos, ela iria aqui.
        // Por enquanto, seguimos a lógica de que 'is_protected' só é setado depois ou via outra lógica.

        // Adiciona o novo usuário à lista final e correta de grupos.
        // A lista `$newGroupIds` contém os grupos selecionados no formulário.
        if (!empty($newGroupIds)) {
            // Usa push para adicionar o ID do novo usuário aos arrays 'user_ids' dos grupos.
            Group::whereIn('id', $newGroupIds)->push('user_ids', $user->id);
        }

        // --- FIM DA LÓGICA DE GRUPOS ---
        // Redireciona para a página de edição do novo usuário ou para o índice de usuários.
        // Passa uma mensagem de sucesso para o frontend.
        return Redirect::route('users.edit', $user->id)->with('success', 'Usuário criado com sucesso!');
        // Ou, se preferir ir para a lista de usuários:
        // return Redirect::route('users.index')->with('success', 'Usuário criado com sucesso!');
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

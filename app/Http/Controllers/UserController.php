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
use MongoDB\BSON\ObjectId;

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

        $user = User::create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Criptografa a senha
        ]);
        
        $newGroupIds = $validated['userGroups'] ?? [];

        if (!empty($newGroupIds)) {
            // Converte os IDs dos grupos de string para ObjectId (se necessário, dependendo de como 'id' do Group é armazenado)
            // Normalmente, se Group é um modelo MongoDB, Group::whereIn('id', ...) já funcionaria com ObjectIds ou strings se configurado.
            // A parte crucial aqui é garantir que o $user->id seja um ObjectId.
            $objectIdUser = new ObjectId($user->id); // Converte o ID do usuário para ObjectId

            Group::whereIn(column: 'id', values: $newGroupIds)
                ->push('user_ids', $objectIdUser); // Usa o ObjectId do usuário
        }

        return Redirect::route('users.edit', $user->id)->with('success', 'Usuário criado com sucesso!');

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

        $UserId = new ObjectId($user->id); // Converte o ID do usuário para ObjectId

        $userGroups = Group::query()
            ->select('_id', 'name')
            ->where('user_ids', $UserId)
            ->get();
        //dd($userGroups);

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

        if (!$user->is_ldap || !$user->is_protected) {
            $user->update([
                'full_name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
            ]);
        }

        $newGroupIds = $validated['userGroups'] ?? [];

        if ($user->is_protected) {

            $protectedGroupNames = config('permissions.protected_groups', []);

            if (!empty($protectedGroupNames)) {
                $protectedGroupIds = Group::whereIn('name', $protectedGroupNames)->pluck('id')->toArray();
                $newGroupIds = array_unique(array_merge($newGroupIds, $protectedGroupIds));
            }
        }

        // Converte o ID do usuário para ObjectId uma vez para reutilização
        $objectIdUser = new ObjectId($user->id);

        // Remove o ID do usuário dos grupos antigos
        Group::where( 'user_ids',$objectIdUser)->pull('user_ids',$objectIdUser);



        if (!empty($newGroupIds)) {
            // Adiciona o ID do usuário aos novos grupos
            Group::whereIn( 'id',$newGroupIds)
                ->push('user_ids', $objectIdUser); // Usa o ObjectId do usuário
        }
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

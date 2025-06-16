<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
}

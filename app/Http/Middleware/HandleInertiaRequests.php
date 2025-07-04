<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            //'auth' => ['user' => $request->user(),],
            'auth' => function () use ($request) {
                $user = $request->user();
                if (!$user) {
                    return ['user' => null]; // Retorna nulo se não houver usuário logado
                }

                // Monta um array controlado com os dados do usuário + permissões
                return [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        // Adicione outros campos seguros do usuário que você precisar no front-end
                        'permissions' => $this->getUserPermissions($user), // <-- NOSSA LÓGICA
                    ]
                ];
            },
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'importErrors' => fn () => $request->session()->get('importErrors'),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => !$request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
    public function getUserPermissions(User $user): array
    {
        return [
            'view_any_users' => $user->can('viewAny', User::class),
            'view_any_groups' => $user->can('viewAny', Group::class),
            // Adicione aqui TODAS as permissões que seu front-end precisa saber
        ];
    }
}

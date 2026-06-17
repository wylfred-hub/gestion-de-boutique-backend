<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * GET /api/v1/users
     * Liste des utilisateurs (super_admin + admin)
     *
     * Supporte :
     * - search (string)
     * - per_page (int)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $query = User::query()->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        // Isolation : Si l'utilisateur n'est pas Super Admin, il ne voit que les membres de son organisation
        if (!$user->isSuperAdmin()) {
            $currentOrgId = session('current_organization_id');
            $query->whereHas('organizations', function ($q) use ($currentOrgId) {
                $q->where('organizations.id', $currentOrgId);
            });
        }

        $users = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ], 200);
    }

    /**
     * POST /api/v1/organizations/{organization}/members/create
     * Création d'un nouvel utilisateur et association immédiate à une organisation.
     */
    public function storeMember(Request $request, Organization $organization)
    {
        // 1. Vérifier les droits via la Policy (doit être owner/admin de l'org ou super_admin)
        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:owner,admin,vendeur', // Rôle au sein de l'org
        ]);

        return DB::transaction(function () use ($validated, $organization) {
            // 2. Créer l'utilisateur global
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => User::ROLE_VENDEUR, // Rôle système par défaut
                'is_active' => true,
            ]);

            // 3. L'attacher à l'organisation actuelle avec le rôle pivot choisi
            $user->organizations()->attach($organization->id, [
                'role'      => $validated['role'],
                'is_active' => true,
            ]);

            return new UserResource($user);
        });
    }

    /**
     * POST /api/v1/users
     * Créé par un super_admin (sans organisation)
     */
    public function store(Request $request)
    {
        $currentUser = auth()->user();

        if (!$currentUser->isSuperAdmin()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role'     => 'required|in:vendeur,admin,super_admin',
        ]);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'role'      => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ], 201);
    }
}

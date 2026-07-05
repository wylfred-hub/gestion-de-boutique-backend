<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class OrganizationController extends Controller
{
    /**
     * GET /api/v1/organizations
     * Lister les organisations (super_admin voit toutes, admin voit ses orgs)
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Organization::where('is_active', true);

        // Super admin voit toutes, admin voit ses orgs seulement
        if (!$user->isSuperAdmin()) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $organizations = $query->paginate(15);

        return OrganizationResource::collection($organizations);
    }

    /**
     * POST /api/v1/organizations
     * Créer une nouvelle organisation (super_admin seulement)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['is_active'] = true;




        $organization = Organization::create($validated);

        return new OrganizationResource($organization);
    }

    /**
     * GET /api/v1/organizations/{id}
     * Détails d'une organisation
     */
    public function show(Organization $organization)
    {
        // Autorisation: user doit être dans l'org
        $this->authorize('view', $organization);

        return new OrganizationResource($organization);
    }

    /**
     * PUT /api/v1/organizations/{id}
     * Modifier une organisation (admin/owner seulement)
     */
    public function update(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'logo' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $organization->update($validated);


        return new OrganizationResource($organization);
    }

    /**
     * DELETE /api/v1/organizations/{id}
     * Supprimer une organisation (owner seulement, soft delete)
     */
    public function destroy(Organization $organization)
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        return response()->json([
            'message' => 'Organisation supprimée',
        ]);
    }

    /**
     * GET /api/v1/organizations/{id}/members
     * Lister les members d'une organisation
     */
    public function members(Organization $organization)
    {
        $this->authorize('view', $organization);

        $members = $organization->users()
            ->withPivot('role', 'is_active')
            ->get();

        return UserResource::collection($members);
    }

    /**
     * POST /api/v1/organizations/{organization}/members/create
     * Créer un utilisateur (ou réutiliser un compte existant par email)
     * et l'attacher à l'organisation avec un rôle
     */
    public function storeMember(Request $request, Organization $organization)
    {
        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'name'     => 'required_without:existing_email|string|max:150',
            'email'    => 'required|email|max:150',
            'password' => 'required_without:existing_email|string|min:8',
            'role'     => 'required|in:owner,admin,vendeur',
        ]);

        // Vérifier si un compte existe déjà avec cet email
        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            // L'utilisateur existe déjà : on ne crée rien, on vérifie juste qu'il n'est pas déjà dans l'org
            if ($organization->users()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Cet utilisateur est déjà dans l\'organisation',
                ], 422);
            }
        } else {
            // Nouvel utilisateur : name et password deviennent obligatoires
            $request->validate([
                'name'     => 'required|string|max:150',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
        }

        $organization->users()->attach($user->id, [
            'role'      => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => $user->wasRecentlyCreated
                ? 'Utilisateur créé et ajouté à l\'organisation'
                : 'Utilisateur existant ajouté à l\'organisation',
            'user'    => new UserResource($user),
        ], 201);
    }

    /**
     * POST /api/v1/organizations/{id}/members
     * Ajouter un user à une organisation (admin/owner seulement)
     */
    public function addMember(Request $request, Organization $organization)
    {
        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:owner,admin,vendeur',
        ]);

        // Vérifier user n'existe pas déjà
        if ($organization->users()->where('user_id', $validated['user_id'])->exists()) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà dans l\'organisation',
            ], 422);
        }

        $organization->users()->attach($validated['user_id'], [
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Utilisateur ajouté à l\'organisation',
        ]);
    }

    /**
     * DELETE /api/v1/organizations/{id}/members/{user_id}
     * Retirer un user d'une organisation
     */
    public function removeMember(Organization $organization, int $user_id)
    {
        $this->authorize('manageMembers', $organization);

        // Vérifier ne pas supprimer le dernier owner
        $ownerCount = $organization->users()
            ->where('organization_user.role', 'owner')
            ->count();

        $userRole = $organization->users()
            ->where('users.id', $user_id)
            ->first()?->pivot?->role;




        if ($ownerCount === 1 && $userRole === 'owner') {
            return response()->json([
                'message' => 'Impossible de retirer le dernier owner',
            ], 422);
        }

        $organization->users()->detach($user_id);

        return response()->json([
            'message' => 'Utilisateur retiré de l\'organisation',
        ]);
    }

    /**
     * POST /api/v1/organizations/{id}/select
     * Choisir l'organisation active pour la session
     */
    public function select(Organization $organization)
    {
        $user = auth()->user();

        // Vérifier si l'utilisateur appartient à cette organisation (ou est superadmin)
        if (!$user->isSuperAdmin() && !$user->organizations->contains($organization->id)) {
            return response()->json([
                'message' => 'Vous n\'avez pas accès à cette organisation.',
            ], 403);
        }

        session(['current_organization_id' => $organization->id]);

        return response()->json([
            'success' => true,
            'message' => "Organisation '{$organization->name}' sélectionnée.",
            'organization' => new OrganizationResource($organization),
        ]);
    }

    /**
     * POST /api/v1/organizations/deselect
     * Supprimer l'organisation active de la session (SuperAdmin seulement)
     * Permet de revenir à la vue globale du système.
     */
    public function deselect()
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Action non autorisée.'], 403);
        }

        session()->forget('current_organization_id');

        return response()->json([
            'success' => true,
            'message' => 'Retour à la vue globale du système.',
        ]);
    }
}

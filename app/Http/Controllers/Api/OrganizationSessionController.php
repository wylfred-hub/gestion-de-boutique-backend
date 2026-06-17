<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationSessionController extends Controller
{
    /**
     * GET /api/v1/organizations/me
     * Renvoie uniquement les organisations auxquelles appartient l'utilisateur authentifié.
     */
    public function myOrganizations(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Organization::query()->where('is_active', true);

        if (!$user->isSuperAdmin()) {
            $query->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        $organizations = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => OrganizationResource::collection($organizations),
        ], 200);
    }
}


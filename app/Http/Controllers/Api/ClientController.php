<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Http\Resources\SaleResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    // ─── GET /api/v1/clients ──────────────────────────
    public function index(): JsonResponse
    {
        $clients = Client::forCurrentOrganization()
            ->withCount('sales')
            ->when(request('search'), fn($q) =>
                $q->search(request('search'))
            )
            ->when(request('type'), fn($q) =>
                $q->where('type', request('type'))
            )
            ->when(request('category'), fn($q) =>
                $q->where('category', request('category'))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 15));

        return response()->json([
            'success' => true,
            'data'    => ClientResource::collection($clients),
            'meta'    => [
                'total'        => $clients->total(),
                'per_page'     => $clients->perPage(),
                'current_page' => $clients->currentPage(),
                'last_page'    => $clients->lastPage(),
            ],
        ], 200);
    }

    // ─── POST /api/v1/clients ─────────────────────────
    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Injecter organization_id depuis l'utilisateur connecté
        $data['organization_id'] = auth()->user()->organization_id;

        $client = Client::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Client créé avec succès.',
            'data'    => new ClientResource($client),
        ], 201);
    }

    // ─── GET /api/v1/clients/{id} ─────────────────────
    public function show(int $id): JsonResponse
    {
        $client = Client::forCurrentOrganization()
            ->withCount('sales')
            ->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ClientResource($client),
        ], 200);
    }

    // ─── PUT /api/v1/clients/{id} ─────────────────────
    public function update(UpdateClientRequest $request, int $id): JsonResponse
    {
        $client = Client::forCurrentOrganization()->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.',
            ], 404);
        }

        $client->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Client mis à jour avec succès.',
            'data'    => new ClientResource($client->fresh()),
        ], 200);
    }

    // ─── DELETE /api/v1/clients/{id} ──────────────────
    public function destroy(int $id): JsonResponse
    {
        $client = Client::forCurrentOrganization()->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.',
            ], 404);
        }

        $ventesActives = $client->sales()
            ->whereNotIn('status', ['annulee', 'livree'])
            ->exists();

        if ($ventesActives) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un client avec des ventes en cours.',
            ], 422);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Client supprimé avec succès.',
        ], 200);
    }

    // ─── GET /api/v1/clients/{id}/sales ───────────────
    public function sales(int $id): JsonResponse
    {
        $client = Client::forCurrentOrganization()->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Client introuvable.',
            ], 404);
        }

        $sales = $client->sales()
            ->with(['user', 'items.product'])
            ->withCount('items')
            ->when(request('status'), fn($q) =>
                $q->where('status', request('status'))
            )
            ->orderBy('created_at', 'desc')
            ->paginate(request('per_page', 15));

        return response()->json([
            'success' => true,
            'client'  => [
                'id'           => $client->id,
                'full_name'    => $client->full_name,
                'total_achats' => $client->total_achats,
            ],
            'data' => SaleResource::collection($sales),
            'meta' => [
                'total'        => $sales->total(),
                'per_page'     => $sales->perPage(),
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
            ],
        ], 200);
    }
}
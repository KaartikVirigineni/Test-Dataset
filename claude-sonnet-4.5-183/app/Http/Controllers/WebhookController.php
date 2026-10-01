<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Webhook;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function catch(Request $request, $endpoint_id)
    {
        $endpoint = Endpoint::where('endpoint_id', $endpoint_id)->first();

        if (!$endpoint) {
            return response()->json([
                'message' => 'Endpoint not found'
            ], 404);
        }

        $body = $request->all();
        if ($request->getContentTypeFormat() === 'json') {
            $body = $request->json()->all();
        }

        $webhook = Webhook::create([
            'endpoint_id' => $endpoint->id,
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $body,
            'query_params' => $request->query(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Webhook received',
            'webhook_id' => $webhook->id,
        ], 200);
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $endpointId = $request->input('endpoint_id');

        $query = Webhook::query()
            ->with('endpoint')
            ->whereHas('endpoint', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });

        if ($endpointId) {
            $query->whereHas('endpoint', function ($q) use ($endpointId) {
                $q->where('endpoint_id', $endpointId);
            });
        }

        $webhooks = $query->latest()->paginate($perPage);

        return response()->json($webhooks);
    }

    public function show(Request $request, $id)
    {
        $webhook = Webhook::with('endpoint')
            ->whereHas('endpoint', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->findOrFail($id);

        return response()->json($webhook);
    }

    public function destroy(Request $request, $id)
    {
        $webhook = Webhook::whereHas('endpoint', function ($q) use ($request) {
            $q->where('user_id', $request->user()->id);
        })->findOrFail($id);

        $webhook->delete();

        return response()->json([
            'message' => 'Webhook deleted successfully'
        ]);
    }

    public function endpoints(Request $request)
    {
        $endpoints = Endpoint::where('user_id', $request->user()->id)
            ->withCount('webhooks')
            ->latest()
            ->get();

        return response()->json($endpoints);
    }

    public function createEndpoint(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $endpoint = Endpoint::create([
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'endpoint' => $endpoint,
            'url' => url("/api/catch/{$endpoint->endpoint_id}"),
        ], 201);
    }

    public function destroyEndpoint(Request $request, $id)
    {
        $endpoint = Endpoint::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $endpoint->webhooks()->delete();
        $endpoint->delete();

        return response()->json([
            'message' => 'Endpoint and all associated webhooks deleted successfully'
        ]);
    }
}
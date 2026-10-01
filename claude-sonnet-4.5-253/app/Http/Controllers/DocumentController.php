<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        if (!$request->user()) {
            $query->where('is_published', true);
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($documents);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        $document = Document::create($validated);

        return response()->json($document, 201);
    }

    public function show($id)
    {
        $document = Document::findOrFail($id);

        if (!$document->is_published && (!auth('sanctum')->user() || auth('sanctum')->user()->id !== $document->user_id)) {
            abort(403, 'This document is not published');
        }

        return response()->json($document);
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        if ($document->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'category' => 'nullable|string|max:100',
            'is_published' => 'boolean',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $document->update($validated);

        return response()->json($document);
    }

    public function destroy(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        if ($document->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $document->delete();

        return response()->json(['message' => 'Document deleted successfully']);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentTemplateController extends Controller
{
    /**
     * Display a listing of document templates.
     */
    public function index(Request $request)
    {
        $query = DocumentTemplate::query();

        if ($request->has('template_type')) {
            $query->where('template_type', $request->template_type);
        }

        $templates = $query->orderBy('template_type')->orderBy('template_name')->get();

        if ($request->expectsJson()) {
            return response()->json($templates);
        }

        return view('document-templates.index', compact('templates'));
    }

    /**
     * Store a newly created template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'template_type' => 'required|in:receipt,invoice,delivery_note',
            'template_name' => 'required|string|max:255',
            'template_data' => 'required|array',
            'is_default' => 'boolean',
        ]);

        // If this is set as default, unset other defaults of the same type
        if ($validated['is_default'] ?? false) {
            DocumentTemplate::where('template_type', $validated['template_type'])
                ->update(['is_default' => false]);
        }

        $template = DocumentTemplate::create($validated);

        if ($request->expectsJson()) {
            return response()->json($template, 201);
        }

        return redirect()->route('document-templates.index')->with('success', 'Template created successfully');
    }

    /**
     * Update the specified template.
     */
    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'template_data' => 'required|array',
            'is_default' => 'boolean',
        ]);

        // If this is set as default, unset other defaults of the same type
        if ($validated['is_default'] ?? false) {
            DocumentTemplate::where('template_type', $documentTemplate->template_type)
                ->where('id', '!=', $documentTemplate->id)
                ->update(['is_default' => false]);
        }

        $documentTemplate->update($validated);

        if ($request->expectsJson()) {
            return response()->json($documentTemplate);
        }

        return redirect()->route('document-templates.index')->with('success', 'Template updated successfully');
    }

    /**
     * Remove the specified template.
     */
    public function destroy(DocumentTemplate $documentTemplate)
    {
        $documentTemplate->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Template deleted successfully']);
        }

        return redirect()->route('document-templates.index')->with('success', 'Template deleted successfully');
    }
}

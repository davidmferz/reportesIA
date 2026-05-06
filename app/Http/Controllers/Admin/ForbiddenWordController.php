<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForbiddenWord;
use Illuminate\Http\Request;

class ForbiddenWordController extends Controller
{
    public function index()
    {
        $words = ForbiddenWord::orderByDesc('active')
            ->orderBy('word')
            ->paginate(20);

        return view('admin.forbidden-words.index', compact('words'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'min:2', 'max:100', 'unique:forbidden_words,word'],
            'reason' => ['nullable', 'string', 'max:255'],
        ], [
            'word.required' => 'La palabra es obligatoria.',
            'word.min' => 'La palabra debe tener al menos 2 caracteres.',
            'word.max' => 'La palabra no puede superar los 100 caracteres.',
            'word.unique' => 'Esa palabra ya está registrada.',
        ]);

        $validated['word'] = mb_strtolower(trim($validated['word']), 'UTF-8');
        $validated['active'] = true;

        ForbiddenWord::create($validated);

        return redirect()->route('admin.forbidden-words.index')
            ->with('success', 'Palabra prohibida agregada. Se aplica a todas las generaciones futuras.');
    }

    public function update(Request $request, ForbiddenWord $forbiddenWord)
    {
        $validated = $request->validate([
            'word' => ['required', 'string', 'min:2', 'max:100', 'unique:forbidden_words,word,' . $forbiddenWord->id],
            'reason' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $validated['word'] = mb_strtolower(trim($validated['word']), 'UTF-8');
        $validated['active'] = $request->has('active');

        $forbiddenWord->update($validated);

        return redirect()->route('admin.forbidden-words.index')
            ->with('success', 'Palabra prohibida actualizada.');
    }

    public function destroy(ForbiddenWord $forbiddenWord)
    {
        $forbiddenWord->delete();

        return redirect()->route('admin.forbidden-words.index')
            ->with('success', 'Palabra prohibida eliminada.');
    }
}

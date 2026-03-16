<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    use ApiResponse;

    protected string $resourceName = 'Note';

    public function index()
    {
        $notes = Note::where('created_by', auth()->id())->latest()->get();
        return $this->retrieved($notes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'note_title'   => 'required|string|max:255',
            'note_content' => 'required|string',
        ]);

        $note = Note::create([
            'created_by'   => auth()->id(),
            'note_title'   => $request->note_title,
            'note_content' => $request->note_content,
        ]);

        return $this->created($note);
    }

    public function show(Note $note)
    {
        if ($note->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        return $this->retrieved($note);
    }

    public function update(Request $request, Note $note)
    {
        if ($note->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        $request->validate([
            'note_title'   => 'sometimes|string|max:255',
            'note_content' => 'sometimes|string',
        ]);

        $note->update($request->only(['note_title', 'note_content']));

        return $this->updated($note);
    }

    public function destroy(Note $note)
    {
        if ($note->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        $note->delete();

        return $this->deleted();
    }
}

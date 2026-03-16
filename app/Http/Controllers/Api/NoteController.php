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

    /**
     * @OA\Get(
     *     path="/api/notes",
     *     tags={"Notes"},
     *     summary="Get semua notes milik user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Notes retrieved successfully"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $notes = Note::where('created_by', auth()->id())->latest()->get();
        return $this->retrieved($notes);
    }

    /**
     * @OA\Post(
     *     path="/api/notes",
     *     tags={"Notes"},
     *     summary="Buat note baru",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"note_title","note_content"},
     *             @OA\Property(property="note_title", type="string", example="Judul Note"),
     *             @OA\Property(property="note_content", type="string", example="Isi note disini")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Note created successfully"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/notes/{id}",
     *     tags={"Notes"},
     *     summary="Get detail note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Note retrieved successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(Note $note)
    {
        if ($note->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        return $this->retrieved($note);
    }

    /**
     * @OA\Put(
     *     path="/api/notes/{id}",
     *     tags={"Notes"},
     *     summary="Update note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="note_title", type="string", example="Updated Title"),
     *             @OA\Property(property="note_content", type="string", example="Updated Content")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Note updated successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/notes/{id}",
     *     tags={"Notes"},
     *     summary="Hapus note",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Note deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Note $note)
    {
        if ($note->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        $note->delete();

        return $this->deleted();
    }
}

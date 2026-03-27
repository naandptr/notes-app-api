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
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Halaman yang ditampilkan",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Jumlah data per halaman (default: 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Cari notes berdasarkan judul atau konten",
     *         @OA\Schema(type="string", example="laravel")
     *     ),
     *     @OA\Parameter(
     *         name="tag_id",
     *         in="query",
     *         required=false,
     *         description="Filter notes berdasarkan tag",
     *         @OA\Schema(type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Notes retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Note retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(property="last_page", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $notes = Note::where('created_by', auth()->id())
            ->with('tags')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('note_title', 'like', "%{$search}%")
                    ->orWhere('note_content', 'like', "%{$search}%");
                });
            })
            ->when($request->tag_id, function ($query, $tagId) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
            })
            ->latest()
            ->paginate($request->get('per_page', 10));

        return $this->paginated($notes);
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
     *             @OA\Property(property="note_content", type="string", example="Isi note disini"),
     *             @OA\Property(property="tag_ids", type="array",
     *                 @OA\Items(type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Note created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Note created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2"),
     *                 @OA\Property(property="note_title", type="string", example="Judul Note"),
     *                 @OA\Property(property="note_content", type="string", example="Isi note disini"),
     *                 @OA\Property(property="tags", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'note_title'   => 'required|string|max:255',
            'note_content' => 'required|string',
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'uuid|exists:tags,id',
        ]);

        $note = Note::create([
            'created_by'   => auth()->id(),
            'note_title'   => $request->note_title,
            'note_content' => $request->note_content,
        ]);

        if ($request->tag_ids) {
            $note->tags()->attach($request->tag_ids);
        }

        return $this->created($note->load('tags'));
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

        return $this->retrieved($note->load('tags'));
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
     *             @OA\Property(property="note_content", type="string", example="Updated Content"),
     *             @OA\Property(property="tag_ids", type="array",
     *                 @OA\Items(type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2")
     *             )
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
            'tag_ids'      => 'nullable|array',
            'tag_ids.*'    => 'uuid|exists:tags,id',
        ]);

        $note->update($request->only(['note_title', 'note_content']));

        if ($request->has('tag_ids')) {
            $note->tags()->sync($request->tag_ids); // sync = replace all tag
        }

        return $this->updated($note->load('tags'));
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

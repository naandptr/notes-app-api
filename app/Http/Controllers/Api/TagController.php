<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    use ApiResponse;

    protected string $resourceName = 'Tag';

    /**
     * @OA\Get(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     summary="Get semua tags milik user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tag retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Tag retrieved successfully"),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2"),
     *                     @OA\Property(property="tag_name", type="string", example="Laravel"),
     *                     @OA\Property(property="created_by", type="string", example="019cea71-d973-73d3-9c20-d2bea6a6826f")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index()
    {
        $tags = Tag::where('created_by', auth()->id())->latest()->get();
        return $this->retrieved($tags);
    }

    /**
     * @OA\Post(
     *     path="/api/tags",
     *     tags={"Tags"},
     *     summary="Buat tag baru",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"tag_name"},
     *             @OA\Property(property="tag_name", type="string", example="Laravel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tag created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="code", type="integer", example=201),
     *             @OA\Property(property="message", type="string", example="Tag created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string", example="019cf4d0-c324-7067-85dd-cfa8e926d8d2"),
     *                 @OA\Property(property="tag_name", type="string", example="Laravel")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Tag already exists"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'tag_name' => 'required|string|max:50',
        ]);

        $exists = Tag::where('created_by', auth()->id())
            ->where('tag_name', $request->tag_name)
            ->exists();

        if ($exists) {
            return $this->badRequest('Tag already exists');
        }

        $tag = Tag::create([
            'created_by' => auth()->id(),
            'tag_name'       => $request->tag_name,
        ]);

        return $this->created($tag);
    }

    /**
     * @OA\Delete(
     *     path="/api/tags/{id}",
     *     tags={"Tags"},
     *     summary="Hapus tag",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Tag deleted successfully"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function destroy(Tag $tag)
    {
        if ($tag->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        $tag->delete();
        return $this->deleted();
    }
}
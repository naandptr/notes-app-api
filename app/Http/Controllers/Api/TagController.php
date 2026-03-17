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

    public function index()
    {
        $tags = Tag::where('created_by', auth()->id())->latest()->get();
        return $this->retrieved($tags);
    }

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

    public function destroy(Tag $tag)
    {
        if ($tag->created_by !== auth()->id()) {
            return $this->forbidden();
        }

        $tag->delete();
        return $this->deleted();
    }
}
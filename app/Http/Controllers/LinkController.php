<?php

namespace App\Http\Controllers;

use App\Models\Link;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LinkController extends Controller
{
    public function index(Request $request)
    {
        return view('links.index', [
            'links' => Link::query()
                ->where(function (Builder $query) use ($request) {

                    if ($request->has('search')) {
                        $search = strtolower($request->get('search'));

                        $query
                            ->where(DB::raw('LOWER(title)'), 'like', "%{$search}%")
                            ->orWhere(DB::raw('LOWER(description)'), 'like', "%{$search}%")
                            ->orWhere('url', 'like', "%{$search}%");
                    }

                    if ($request->has('tag')) {
                        $query->whereHas('tags', fn($query) => $query->where('tags.id', $request->get('tag')));
                    }

                })
                ->orderBy('added_at', 'desc')
                ->paginate(25),
            'tags' => Tag::query()
                ->orderBy('name')
                ->withCount('links')
                ->get(),
            'total_links' => Link::count(),
        ]);
    }

    public function create()
    {
        return view('links.create', [
            'tags' => Tag::query()
                ->withCount('links')
                ->get()
                ->sortByDesc('links_count'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'url' => 'required',
            'description' => 'required',
            'added_at' => 'required',
            'tags' => '',
        ]);

        $link = Link::create([
            'title' => $validated['title'],
            'url' => $validated['url'],
            'description' => $validated['description'],
            'added_at' => $validated['added_at'],
        ]);

        $link->tags()->sync($validated['tags']);

        return redirect()
            ->route('links.index')
            ->with('alert-success', 'Successfully added new link!');
    }

    public function show(Link $link)
    {
        return view('links.show', [
            'link' => $link->loadMissing('tags'),
        ]);
    }

    public function edit(Link $link)
    {
        return view('links.edit', [
            'link' => $link,
            'selected_tags' => $link->tags->map(fn ($tag) => $tag->id)->values()->toArray(),
            'tags' => Tag::query()
                ->withCount('links')
                ->get()
                ->sortByDesc('links_count'),
        ]);
    }

    public function update(Request $request, Link $link)
    {
        $validated = $request->validate([
            'title' => 'required',
            'url' => 'required',
            'description' => 'required',
            'added_at' => 'required',
            'tags' => ''
        ]);

        $link->update([
            'title' => $validated['title'],
            'url' => $validated['url'],
            'description' => $validated['description'],
            'added_at' => $validated['added_at'],
        ]);

        $link->tags()->sync($validated['tags']);

        return redirect()
            ->route('links.index')
            ->with('alert-success', 'Successfully updated the link!');
    }

    public function destroy(Link $link)
    {
        $link->delete();

        return redirect()
            ->route('links.index')
            ->with('alert-success', 'Successfully deleted the link!');
    }
}

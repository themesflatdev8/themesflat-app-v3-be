<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ThemeSelectorModel;

use App\Http\Requests\Admin\ThemeRequest;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Display a listing of theme
     *
     */
    public function index(Request $request)
    {
        $query = ThemeSelectorModel::query();

        // Validate the keyword input
        $validated = $request->validate([
            'keyword' => 'nullable|string|max:255',
        ]);

        // Check if keyword exists in the request
        if (!empty($validated['keyword'])) {
            $keyword = $validated['keyword'];

            // Search for records that match the keyword in name
            $query->where('name', 'LIKE', '%' . $keyword . '%');
        }

        // Paginate the results
        $themes = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('themeList.themeList', compact('themes'));
    }

    /**
     * Show the form for adding a new theme.
     *
     */
    public function add()
    {
        return view('themeList.add');
    }

    /**
     * Adding a new theme.
     */
    public function post(ThemeRequest $request)
    {
        $validatedData = $request->validated();
        ThemeSelectorModel::create($validatedData);

        return redirect()->back()->with('success', 'Theme item added successfully.');
    }

    /**
     * Show the form for editing theme item.
     *
     * @param  int  $id
     */
    public function edit($id)
    {
        $themeItem = ThemeSelectorModel::where('id', $id)->first();

        return view('themeList/edit', compact('themeItem'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function update(ThemeRequest $request, $id)
    {
        $themeItem = ThemeSelectorModel::where('id', $id)->first();

        if ($themeItem) {
            // Update black list item
            $themeItem->update($request->validated());
            return redirect()->back()->with('success', 'Update theme item successfully');
        }
    }

    /**
     * Delete a theme item
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $themeItem = ThemeSelectorModel::find($id);

        if ($themeItem) {
            $themeItem->delete();
            return redirect()->back()->with('success', 'Theme item deleted successfully.');
        }

        return redirect()->back()->with('error', 'Theme item not found.');
    }
}

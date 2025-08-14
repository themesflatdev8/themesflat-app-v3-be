<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlackListModel;
use App\Http\Requests\Admin\BlackListRequest;
use Illuminate\Http\Request;

class BlackListController extends Controller
{
    /**
     * Display and search value black list
     * GET /black-list
     */
    public function index(Request $request)
    {
        $query = new BlackListModel();

        if (!empty($request->get('keyword'))) {
            $keyword = $request->get('keyword');
            $query = $query->where(function ($query) use ($keyword) {
                $query->where('value', 'LIKE', '%' . $keyword . '%');
            });
        }

        $blackList = $query->orderBy('created_at', 'ASC')->paginate(10);
        
        return view('blacklist/menuBlackList', compact('blackList'));
    }
    
    /**
     * Display add black list form
     * GET /black-list/add
     */
    public function add()
    {
        return view('blacklist/add');
    }

    /**
     * Add new black list item
     * POST /black-list/add
     */
    public function post(BlackListRequest $request)
    {
        $validatedData = $request->validated();

        BlackListModel::create($validatedData);

        return redirect()->back()->with('success', 'Black list item added successfully.');
    }

    /**
     * Get black list item to edit
     * GET /black-list/edit/:id
     * @param integer $id
     */
    public function edit($id)
    {
        $blackListItem = BlackListModel::where('id', $id)->first();

        return view('blacklist/edit', compact('blackListItem'));
    }
    
    /**
     * Update black list item
     * POST /black-list/edit/:id
     * @param integer $id
     */
    public function update($id, BlackListRequest $request)
    {
        $blackListItem = BlackListModel::where('id', $id)->first();

        if ($blackListItem) {
            // Update black list item
            $blackListItem->update($request->validated());
            return redirect()->back()->with('success', 'Update black list success');
        }
    }
}

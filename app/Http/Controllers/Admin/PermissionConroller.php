<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

// use Session;

class PermissionConroller extends Controller
{
    /**
     * Display a listing of Permission.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $allowedSorts = ['id', 'name', 'parent_id']; // টেবিলে parent_id থাকলে
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'id';
        $dir  = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $query = Permission::query();

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%");
                // parent name যদি আলাদা কলামে থাকে/আ্যাক্সেসর থাকে, চাইলে এখানে অ্যাডজাস্ট করুন
            });
        }

        $permissions = $query->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Show the form for creating new Permission.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $parent = Permission::where('parent_id', 0)->orderBy('id', 'DESC')->pluck('name', 'id');
        return view('admin.permissions.create', ['parent' => $parent]);
    }

    /**
     * Store a newly created Permission in storage.
     *
     * @param  \App\Http\Requests\StorePermissionsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        Permission::create($request->all());
        Session::flash('message', 'Record created successfully');

        return redirect()->route('admin.permissions.index')->with('success', 'Permission Created Successfully');

        //return redirect()->action('Admin\PermissionsController@index');
    }


    /**
     * Show the form for editing Permission.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $permission = Permission::findOrFail($id);

        $parent = Permission::where('parent_id', 0)->orderBy('id', 'DESC')->pluck('name', 'id');

        return view('admin.permissions.edit', (['permission' => $permission, 'parent' => $parent]));
    }

    /**
     * Update Permission in storage.
     *
     * @param  \App\Http\Requests\UpdatePermissionsRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update($request->all());

        Session::flash('message', 'Record has been updated successfully');

        return back();
        //return redirect()->action('Admin\PermissionsController@index');

    }


    /**
     * Remove Permission from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $permission = Permission::findOrFail($id);
        $permission->delete();
        return redirect()->route('permissions.index');
    }

    /**
     * Delete all selected Permission at once.
     *
     * @param Request $request
     */
    public function massDestroy(Request $request)
    {

        if ($request->input('ids')) {
            $entries = Permission::whereIn('id', $request->input('ids'))->get();

            foreach ($entries as $entry) {
                $entry->delete();
            }
        }
    }
}

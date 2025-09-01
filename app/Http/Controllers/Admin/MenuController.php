<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

// use Session;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $allowedSorts = ['id', 'title', 'permission', 'url'];
        $sort = in_array($request->input('sort'), $allowedSorts) ? $request->input('sort') : 'id';
        $dir  = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $query = Menu::with('parent_menu');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                    ->orWhere('permission', 'like', "%{$q}%")
                    ->orWhere('url', 'like', "%{$q}%");
            });
        }

        $menus = $query->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.menu.index', compact('menus', 'q', 'sort', 'dir', 'perPage'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $menu = new Menu();
        $menus = Menu::pluck('title', 'id');
        $permissions = Permission::get();

        return  view('admin.menu.create')
            ->with('menus', $menus)
            ->with('menu', $menu)
            ->with('permissions', $permissions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'permission' => 'required',
        ]);
        $menu  = new Menu();
        $menu->title = $request->title;
        $menu->parent_id = $request->parent_id;
        $menu->url = $request->url;
        $menu->permission = $request->permission;
        $menu->icon = $request->icon;
        $menu->save();

        $this->forgetCache();

        Session::flash('message', 'Record created successfully');
        return redirect()->route('menus.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $menu =  Menu::where('id', $id)->first();
        $menus = Menu::pluck('title', 'id');
        $permissions = Permission::get();

        return view('admin.menu.edit', compact('menu', 'menus', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'permission' => 'required',
        ]);

        $menu  = Menu::find($request->id);
        $menu->title = $request->title;
        $menu->parent_id = $request->parent_id;
        $menu->url = $request->url;
        $menu->permission = $request->permission;
        $menu->icon = $request->icon;
        $menu->save();

        $this->forgetCache();

        Session::flash('message', 'Record updated successfully');
        return redirect()->route('menus.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();

        $this->forgetCache();

        Session::flash('message', 'Record Deleted successfully');
        return redirect()->route('menus.index');
    }

    private function forgetCache()
    {
        Cache::forget('AdminPanelMenus');
    }
}

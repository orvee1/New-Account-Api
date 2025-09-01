<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Session;
use Auth;
use Validator;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class AdministratorController extends Controller
{

    public function index(Request $request)
    {
        $type = $request->type ?? null;

        $users = User::query()
            ->with('roles')
            ->when($type, function ($query, $type) {
                $query->whereHas("roles", function ($query) use ($type) {
                    $query->where('name', $type);
                });
            })
            ->get();

        $permission_ids = Setting::where('name', 'admin_device_verification_permissions')->first()->value ?? [];

        $permission_ids = $permission_ids ? json_decode($permission_ids, true) : [];

        $is_device_accept_permission = Auth::user()->hasRole("Developer") || in_array(Auth::user()->id, $permission_ids);

        return view('admin.administrator.index', compact('users', 'is_device_accept_permission'));
    }

    public function create()
    {
        //cache permission reset;
        $this->permission_rest();

        return view('admin.administrator.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required'],
            'phone_number' => ['required'],
            'password' => ['required']
        ]);

        if ($validator->fails()) {
            Session::flash('class', 'alert-danger');
            Session::flash('message', "Please enter valid data!!!");
            return redirect()->route('administrator.create')->withInput();
        }

        if (User::where('email', $request->email)->exists() || User::where('phone_number', $request->phone_number)->exists()) {
            Session::flash('class', 'alert-danger');
            session()->flash('message', 'This email  already exists');
            return redirect()->route('administrator.create')->withInput();
        }

        $allData = $request->all();

        $allData['type'] = 2;

        $allData['password'] = bcrypt($request->password);

        User::create($allData);

        Session::flash('message', 'Record has been added successfully');

        //cache permission reset;
        $this->permission_rest();

        return redirect()->route('administrator.index');
    }

    public function show($id)
    {
        // return
        $user = User::query()
            ->with('roles')
            ->findOrFail($id);

        // return
        $activities = Activity::query()
            ->where([
                "user_id"   => $user->id,
                "guard"     => "web",
            ])
            ->latest()
            ->paginate(100);

        return view('admin.administrator.show', compact('user', 'activities'));
    }

    public function edit($id)
    {
        $user = User::find($id);

        $roles = Role::whereNull('deleted_at')->pluck('name', 'name');

        $title = 'GENESIS Admin : Administrator Edit';

        $this->permission_rest();

        return view('admin.administrator.edit', compact('user', 'title', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required'],
            'status' => ['required'],
            'phone_number' => [
                'required',
                'string',
                'min:1',
                'regex:/^[0-9]*$/i'
            ]
        ]);

        if ($validator->fails()) {
            Session::flash('class', 'alert-danger');
            Session::flash('message', "Please enter valid data!!!");
            return back()->withInput();
        }

        $user = User::find($id);

        if ($request->email != $user->email) {
            if (User::where('email', $request->email)->exists()) {
                Session::flash('class', 'alert-danger');
                session()->flash('message', 'This email already exists');
                return redirect()->back()->withInput();
            }
        }

        if ($request->phone_prefix . $request->phone_number != $user->phone_number) {
            if (User::where('phone_number', $request->phone_prefix . $request->phone_number)->exists()) {
                Session::flash('class', 'alert-danger');
                session()->flash('message', 'This phone number already exists');
                return redirect()->back()->withInput();
            }
        }

        $user->email = $request->email;
        $user->name = $request->name;
        $user->phone_number = $request->phone_prefix . $request->phone_number;
        $user->access_course_ids = array_map(fn($id) => (int) $id, $request->access_course_ids ?? []);
        $user->status = $request->status;

        if ($request->password) {
            $user->password = bcrypt($request->password);
            $user->security = $request->password;
        }

        $user->save();
        $roles = $request->input('roles') ? $request->input('roles') : [];
        $user->syncRoles($roles);

        Session::flash('message', 'Record has been updated successfully');

        //cache permission reset;
        $this->permission_rest();

        return back();
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find(Auth::id());

        User::destroy($id); // 1 way
        Session::flash('message', 'Record has been deleted successfully');
        return redirect()->action('Admin\AdministratorController@index');
    }

    private function permission_rest()
    {
        Artisan::call('permission:cache-reset');
        //return 'ok';
    }
}

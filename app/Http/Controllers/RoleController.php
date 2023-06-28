<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        /*  $roles = Role::orderBy('id', 'DESC')->paginate(5);
        return view('roles.index', compact('roles'))
            ->with('i', ($request->input('page', 1) - 1) * 5); */
        if ($request->ajax()) {
            //sleep(2);



            $datas = Role::orderBy("id", "desc")->get();
            return datatables()->of($datas)
                ->editColumn('checkbox', function ($row) {
                    return '<input type="checkbox" id="' . $row->id . '" class="flat" name="table_records[]" value="' . $row->id . '" >';
                })
                ->editColumn('role', function ($row) {
                    $rpermission = DB::table('role_has_permissions')->where('role_id', $row->id)
                        ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                        ->orderBy("permission_id", "asc")
                        ->pluck('permissions.name');
                    $tperm = '';
                    foreach ($rpermission as $perm) {
                        $tperm .= ucfirst($perm) . ',';
                    }
                    return $tperm;
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="#" class="btn btn-sm btn-warning btn-edit" id="getEditData" data-id="' . $row->id . '"><i class="fa fa-edit"></i> Edit</a> ';
                    $html .= '<a href="#" data-rowid="' . $row->id . '" class="btn btn-sm btn-danger btn-delete"><i class="fa fa-trash"></i> Delete</a>';
                    return $html;
                })->rawColumns(['checkbox', 'action'])->toJson();
        }
        $permission = Permission::get();
        return view('roles.index', compact('permission'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'permission' => 'required'
        ]);



        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $role = Role::create(['name' => $request->input('name')]);
        $role->syncPermissions($request->input('permission'));

        /* return redirect()->route('roles.index')
            ->with('success', 'Role created successfully'); */
        return response()->json(['success' => 'Role created successfully']);
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();

        return view('roles.show', compact('role', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        /*  $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();

        return view('roles.edit', compact('role', 'permission', 'rolePermissions')); */

        $data = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            //->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->pluck('role_has_permissions.permission_id')
            ->all();

        //dd($permission);
        //exit();


 /*        $rolese = '';
        foreach ($permission as $value) {
            if (in_array($value->id, $rolePermissions)) {
                $sselected = "checked";
            } else {
                $sselected = "";
            }

            $rolese .= '
            <div class="custom-control custom-switch">
             <input ' . $sselected . ' id="customCheckbox' . $value->id . '" class="custom-control-input name" name="epermission[]" type="checkbox" value="' . $value->id . '">
             <label for="customCheckbox' . $value->id . '" class="custom-control-label">' . $value->name . '</label>
            </div>';
        }


        $html = '<div class="form-group">
                    <label for="Name">Name:</label>
                    <input type="text" class="form-control" name="name" id="editName" value="' . $data->name . '">
                </div>
                <div class="col-xs-12 col-sm-12 col-md-12">
                            <div class="form-group">
                                <strong>Permission:</strong>
                                <br />

                               ' . $rolese  . '

                            </div>
                        </div>';



        return response()->json(['html' => $html]); */

        return response()->json(['name' => $data->name,'permission' => $rolePermissions]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        /*
        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]); */
        $rules = [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permission' => 'required',
        ];


        $validator =  Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()]);
        }

        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();

        $role->syncPermissions($request->input('permission'));

        /* return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully'); */
        return response()->json(['success' => 'Role updated successfully']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->get('id');
        $ret = DB::table("roles")->where('id', $id)->delete();
        if ($ret) {
            return ['success' => true, 'message' => 'Role Deleted Successfully'];
        } else {
            return ['error' => true, 'message' => $id];
        }

        /* return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully'); */
    }

    public function destroy_all(Request $request)
    {

        $arr_del  = $request->get('table_records'); //$arr_ans is Array MacAddress

        for ($xx = 0; $xx < count($arr_del); $xx++) {
            DB::table("roles")->where('id', $arr_del[$xx])->delete();
        }
        //$ids = $request->get('table_records');
        //User::whereIn('id',explode(",",$ids))->delete();
        return redirect('/roles')->with('success', 'Roles Deleted Successfully');
    }
}

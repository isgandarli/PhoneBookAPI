<?php

use Illuminate\Support\Facades\DB;

function SuccessResponse($code, $data)
{
    return response()->json(['code' => $code, 'data' => $data]);
}

function FailResponse($code, $message)
{
    return response()->json(['code' => $code, 'error_description' => $message]);
}

function ValidationError($message)
{
    return response()->json(['code' => 10, 'error_description' => $message]);
}

/**
 * Adds or Revokes permissions automatically based on request fields.
 * If field is present permission is added otherwise removed.
 *
 * @param $user
 * @param $request_array
 * @return bool
 */
function ManagePermissions($user, $request_array)
{
    $all_permissions = \App\Permissions::all();

    $user_role_id = $user->role_id;

    foreach ($all_permissions as $permission)
    {
        $role_permission = \App\Role_Permissions::where('role_id', $user_role_id)->where('permission_id', $permission->id)->first();

        if (array_key_exists($permission->name, $request_array))
        {
            if (!$role_permission)
            {
                $role_permission = new \App\Role_Permissions();
                $role_permission->role_id = $user_role_id;
                $role_permission->permission_id = $permission->id;
                $role_permission->save();
            }
        }
        else
        {
            if ($role_permission)
            {
                //it shouldn't be possible to revoke permission of admin if only 1 admin left
                $admin_roles_array = \App\Roles::whereIn('name', ['admin', 'developer', 'super admin'])->pluck('id')->toArray();
                $other_admins = \App\User::where('id', '!=', $user->id)->whereIn('role_id', $admin_roles_array)->first();
                if (!$other_admins)
                    return false;

                $role_permission->delete();
            }
        }
    }

    return true;
}

/**
 * Checks user's permission to access provided resource. Returns true or false
 *
 * @param $resource
 * @return bool
 */
function CheckPermission($resource)
{
    $user = request()->auth;
    $authorized = false;

    foreach ($user->permissions as $permission)
    {
        if ($permission->PermissionDetails->name == $resource)
        {
            $authorized = true;
            break;
        }
    }

    return $authorized;
}

function GetAllChildrenStructuresRecursively($starting_from_structure_id)
{
    $query = "WITH RECURSIVE
    rec_d (id, name, parent_id) AS
    (
      SELECT id, name, parent_id FROM public.structure WHERE id = " . intval($starting_from_structure_id) . "  and deleted_at is NULL
      UNION ALL
      SELECT public.structure.id, public.structure.name, public.structure.parent_id FROM rec_d, public.structure where structure.parent_id = rec_d.id and deleted_at is NULL
    )
SELECT id, name, parent_id FROM rec_d;";
    $eligible_structures_array = DB::select($query);

    return $eligible_structures_array;
}

function GetStructureHierarchy($structure_id)
{
    $structure_flat = GetAllChildrenStructuresRecursively(1);
    $structure_flat = collect($structure_flat);

    $structure = $structure_flat->where("id", $structure_id)->first();
    $hierarchy = ["id" => $structure->id, "name" => $structure->name];

    $children = $structure_flat->where('parent_id', $structure_id);

    if ($children->isNotEmpty())
        foreach ($children->all() as $child)
        {
            $childHierarchy = GetStructureHierarchy($child->id);
            $hierarchy[] = $childHierarchy;
        }

    return $hierarchy;
}

function CheckStructureOperationsEligibility($structure_id)
{
    $user = request()->auth;

    $user_structure_id = $user->manages_department_id;
    $eligible_structures_array = GetAllChildrenStructuresRecursively($user_structure_id);

    $eligible_structures_ids_array = [];
    foreach ($eligible_structures_array as $key => $structure)
    {
        $eligible_structures_ids_array[] = $structure->id;
    }

    if (!in_array($structure_id, $eligible_structures_ids_array))
        return false;

    return true;
}

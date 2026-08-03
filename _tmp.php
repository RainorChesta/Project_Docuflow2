foreach (\App\Models\User::all(['id','name','email','division_id','system_role']) as $u) { echo json_encode($u->toArray()), PHP_EOL; }

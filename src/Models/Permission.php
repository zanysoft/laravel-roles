<?php

namespace ZanySoft\LaravelRoles\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ZanySoft\LaravelRoles\Contracts\PermissionHasRelations as PermissionHasRelationsContract;
use ZanySoft\LaravelRoles\Traits\PermissionHasRelations;

class Permission extends Model implements PermissionHasRelationsContract
{
    use PermissionHasRelations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'model'];

    protected $accessOfActionMethod = [
        'list' => 'view',
        'index' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'translateItem' => 'create',
        'createDefaultPermissions' => 'create',
        'createBulkCountriesSubDomain' => 'create', // Domain Mapping
        'generate' => 'create',
        'edit' => 'edit',
        'modify' => 'edit',
        'update' => 'edit',
        'block' => 'edit',
        'unblock' => 'edit',
        'reorder' => 'edit',
        'saveReorder' => 'edit',
        'saveAjaxRequest' => 'edit',
        'saveSetting' => 'edit',
        'saveSettings' => 'edit',
        'syncFilesLines' => 'edit',
        'sync' => 'edit',
        'synchronise' => 'edit',
        'attach' => 'edit',
        'status' => 'edit',
        'approve' => 'edit',
        'publish' => 'edit',
        'unpublish' => 'edit',
        'unPublish' => 'edit',
        'detach' => 'delete',
        'destroy' => 'delete',
        'delete' => 'delete',
        'forceDelete' => 'delete',
        'empty' => 'delete',
        'force_delete' => 'delete',
        'bulkDelete' => 'delete',
        'reset' => 'delete',
        'download' => 'download',
        'upload' => 'upload',
        'make' => 'make',
        'install' => 'install',
        'uninstall' => 'uninstall',
        'send' => 'send',
        'resend' => 'send',
        'sendProposal' => 'send',
        'sendAgreement' => 'send',
        'restore' => 'restore',
        'report' => 'report',
        'dashboard' => 'access',
        'redirect' => 'access',
        'reSendVerificationEmail' => 'resend-verification-notification',
        'reSendVerificationSms' => 'resend-verification-notification',
    ];

    /**
     * Create a new model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($connection = config('roles.connection')) {
            $this->connection = $connection;
        }
    }

    /**
     * Default Staff users permissions
     *
     * @return array
     */
    public static function getStaffPermissions()
    {
        $separator = config('roles.separator', '.');

        $permissions = [
            'access'.$separator.'dashboard'
        ];

        return $permissions;
    }

    /**
     * Default Super Admin users permissions
     *
     * @return array
     */
    public static function getSuperAdminPermissions()
    {
        $separator = config('roles.separator', '.');
        $permissions = [
            'list'.$separator.'permission',
            'create'.$separator.'permission',
            'update'.$separator.'permission',
            'delete'.$separator.'permission',
            'list'.$separator.'role',
            'create'.$separator.'role',
            'update'.$separator.'role',
            'delete'.$separator.'role',
        ];

        return $permissions;
    }

    /**
     * Check Super Admin permissions
     * NOTE: Must use try {...} catch {...}
     *
     * @return bool
     */
    public static function checkSuperAdminPermissions()
    {
        try {
            $superAdminPermissions = array_merge((array)Permission::getSuperAdminPermissions(), (array)Permission::getStaffPermissions());
            if (!empty($superAdminPermissions)) {
                foreach ($superAdminPermissions as $superAdminPermission) {
                    $permission = Permission::where('slug', $superAdminPermission)->first();
                    if (empty($permission)) {
                        return false;
                    }
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {}

        return true;
    }



    public function setAccessOfActionMethod($methods, $merge = true)
    {
        if ($merge) {
            $this->accessOfActionMethod = array_merge($this->accessOfActionMethod, (array)$methods);
        } else {
            $this->accessOfActionMethod = (array)$methods;
        }

        return $this;
    }

    /**
     * Reset default permissions
     * NOTE: Must use try {...} catch {...}
     */
    public static function resetDefaultPermissions($delete_unnecessary = false)
    {
        $success = false;

        try {
            // Get all permissions
            $permissions = Permission::defaultPermissions();
            $permissions = array_merge($permissions,(array)Permission::getSuperAdminPermissions(), (array)Permission::getStaffPermissions());

            if (!empty($permissions)) {

                $ids = Permission::pluck('id', 'slug')->toArray();

                $table = \DB::getTablePrefix() . 'permissions';
                DB::statement('ALTER TABLE ' . $table . ' AUTO_INCREMENT = 1;');
                foreach ($permissions as $permission) {

                    $name = Str::title(str_replace(['-', '_', '.'], ' ', $permission));

                    //$permission = Str::slug($permission, config('roles.separator'));

                    if (Permission::where('slug', '=', $permission)->count()) {
                        if (isset($ids[$permission])) {
                            unset($ids[$permission]);
                        }
                    } else {
                        $entry = new Permission();
                        $entry->name = $name;
                        $entry->slug = $permission;
                        $entry->save();
                        $success = true;
                    }
                }

                if ($delete_unnecessary && !empty($ids)) {
                    $ids = array_values($ids);
                    Permission::whereIn('id', array_values($ids))->delete();
                }
            }
            return $success;

        } catch (\Exception $e) {

        }
        return $success;
    }

    /**
     * Get all Admin Controllers public methods
     *
     * @return array
     */
    public static function defaultPermissions()
    {
        $permissions = Permission::getRoutesPermissions();
        $permissions = collect($permissions)->mapWithKeys(function ($item) {
            return [$item['permission'] => $item['permission']];
        })->sort()->toArray();

        return $permissions;
    }

    /**
     * @return array
     */
    public static function getRoutesPermissions()
    {
        $model = new static;
        $routeCollection = Route::getRoutes();

        $defaultAccess = ['view', 'create', 'update', 'delete', 'reorder', 'details_row'];
        $defaultAllowAccess = ['view', 'create', 'update', 'delete'];
        $defaultDenyAccess = ['reorder', 'details_row'];

        // Controller's Action => Access
        $accessOfActionMethod = $model->accessOfActionMethod;
        $tab = $data = [];
        foreach ($routeCollection as $key => $value) {

            // Init.
            $data['filePath'] = null;
            $data['actionMethod'] = null;
            $data['methods'] = [];
            $data['permission'] = null;

            // Get & Clear the route prefix
            $routePrefix = $value->getPrefix();
            $routePrefix = trim($routePrefix, '/');
            if ($routePrefix != 'admin') {
                $routePrefix = head(explode('/', $routePrefix));
            }

            //dd(config('rolesx.route_prefix'));

            if ($routePrefix == config('roles.route_prefix')) {

                $data['methods'] = $value->methods();

                $data['uri'] = $value->uri();
                $data['uri'] = preg_replace('#\{[^\}]+\}#', '*', $data['uri']);

                $data['actionMethod'] = $actionMethod = $value->getActionMethod();
                $controllerActionPath = $value->getActionName();

                try {
                    $controllerNamespace = '\\' . preg_replace('#@.+#i', '', $controllerActionPath);
                    $reflector = new \ReflectionClass($controllerNamespace);
                    if (!$reflector->hasMethod($actionMethod)) {
                        continue;
                    }
                    $data['filePath'] = $filePath = $reflector->getFileName();
                } catch (\Exception $e) {
                    $data['filePath'] = $filePath = null;
                }

                $access = isset($accessOfActionMethod[$actionMethod]) ? $accessOfActionMethod[$actionMethod] : null;

                if (!$access) {
                    $actionMethods = [
                        2 => Str::kebab($actionMethod),
                        3 => Str::camel($actionMethod),
                        4 => Str::snake($actionMethod),
                    ];

                    foreach ($actionMethods as $am) {
                        if (isset($accessOfActionMethod[$am])) {
                            $access = $accessOfActionMethod[$am];
                            break;
                        }
                    }
                }

                if (!$access) {
                    if (Str::startsWith(strtolower($actionMethod), ['view', 'load', 'show', 'check', 'has'])) {
                        $access = 'view';
                    }

                    if (Str::startsWith(strtolower($actionMethod), [
                        'edit', 'modify', 'update', 'set', 'change', 'enable', 'disable', 'approve', 'unapprove', 'publish', 'unpublish'
                    ])
                    ) {
                        $access = 'edit';
                    }

                    if (Str::startsWith(strtolower($actionMethod), ['delete', 'remove', 'destroy'])) {
                        $access = 'delete';
                    }

                    if (Str::startsWith(strtolower($actionMethod), ['new', 'create', 'add'])) {
                        $access = 'create';
                    }

                    if (Str::startsWith(strtolower($actionMethod), ['send', 'resend'])) {
                        $access = 'send';
                    }
                }

                if (!empty($filePath) && file_exists($filePath)) {
                    $content = file_get_contents($filePath);

                    $content = preg_replace('/.*?:?(\/\/.*)/', '', $content);

                    //if (Str::contains($content, 'extends PanelController')) {
                    $allowAccess = [];
                    $denyAccess = [];


                    if (Str::contains($controllerActionPath, '\PermissionController')) {
                        if (!config('larapen.admin.allow_permission_create')) {
                            $denyAccess[] = 'create';
                        }
                        if (!config('larapen.admin.allow_permission_update')) {
                            $denyAccess[] = 'update';
                        }
                        if (!config('larapen.admin.allow_permission_delete')) {
                            $denyAccess[] = 'delete';
                        }
                    } else if (Str::contains($controllerActionPath, '\RoleController')) {
                        if (!config('larapen.admin.allow_role_create')) {
                            $denyAccess[] = 'create';
                        }
                        if (!config('larapen.admin.allow_role_update')) {
                            $denyAccess[] = 'update';
                        }
                        if (!config('larapen.admin.allow_role_delete')) {
                            $denyAccess[] = 'delete';
                        }
                    } else {
                        // Get allowed accesses
                        $tmp = '';
                        //preg_match('#->allowAccess\(([^\)]+)\);#', $content, $tmp);
                        preg_match('/\$(allowAccess|allow_access)\s*=\s*(.*?);/im', $content, $tmp);
                        $allowAccessStr = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : '';

                        if (!empty($allowAccessStr)) {
                            $tmp = '';
                            preg_match_all("#'([^']+)'#", $allowAccessStr, $tmp);
                            $allowAccess = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : [];

                            if (empty($denyAccess)) {
                                $tmp = '';
                                preg_match_all('#"([^"]+)"#', $allowAccessStr, $tmp);
                                $allowAccess = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : [];
                            }
                        }

                        // Get denied accesses
                        $tmp = '';
                        ///preg_match('#->denyAccess\(([^\)]+)\);#', $content, $tmp);
                        preg_match('/\$(denyAccess|deny_access)\s*=\s*(.*?);/im', $content, $tmp);
                        $denyAccessStr = '';
                        if (!empty($tmp)) {
                            $denyAccessStr = isset($tmp[2]) ? $tmp[2] : (isset($tmp[1]) ? $tmp[1] : '');
                        }

                        if (!empty($denyAccessStr)) {
                            $tmp = '';
                            preg_match_all("#'([^']+)'#", $denyAccessStr, $tmp);
                            $denyAccess = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : [];

                            if (empty($denyAccess)) {
                                $tmp = '';
                                preg_match_all('#"([^"]+)"#', $denyAccessStr, $tmp);
                                $denyAccess = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : [];
                            }
                        }
                    }

                    $allowAccess = array_merge((array)$defaultAllowAccess, (array)$allowAccess);
                    $denyAccess = array_merge((array)$defaultDenyAccess, (array)$denyAccess);

                    $availableAccess = array_merge(array_diff($allowAccess, $defaultAccess), $defaultAccess);
                    $availableAccess = array_diff($availableAccess, $denyAccess);

                    if (in_array($access, $defaultAccess)) {
                        if (!in_array($access, $availableAccess)) {
                            continue;
                        }
                    }
                    //}
                }
                if (Str::contains($controllerActionPath, '\ActionController')) {
                    $data['permission'] = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $actionMethod));
                } else {
                    $tmp = '';
                    preg_match('#\\\([a-zA-Z0-9]+)Controller@#', $controllerActionPath, $tmp);
                    $controllerSlug = (isset($tmp[1]) && !empty($tmp)) ? $tmp[1] : '';
                    $controllerSlug = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $controllerSlug));

                    $separator = config('roles.separator', '.');

                    $permission = (!empty($access)) ? Str::kebab($access) . $separator . Str::kebab($controllerSlug) : null;

                    $data['permission'] = $permission;

                    if (in_array($controllerSlug, ['forgot-password', 'login', 'register', 'reset-password', 'ajax-request'])) {
                        continue;
                    }
                }

                if (empty($data['permission'])) {
                    continue;
                }

                if ($data['filePath']) {
                    unset($data['filePath']);
                }
                if ($data['actionMethod']) {
                    unset($data['actionMethod']);
                }

                // Save It!
                $tab[$key] = $data;
            }
        }

        return $tab;
    }
}

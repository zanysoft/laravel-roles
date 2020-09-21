<?php

namespace ZanySoft\LaravelRoles\Models;

use Illuminate\Database\Eloquent\Model;
use ZanySoft\LaravelRoles\Contracts\RoleHasRelations as RoleHasRelationsContract;
use ZanySoft\LaravelRoles\Traits\RoleHasRelations;
use ZanySoft\LaravelRoles\Traits\Slugable;

class Role extends Model implements RoleHasRelationsContract
{
    use Slugable, RoleHasRelations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description'];

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

    public static function findByName(string $name, $guardName = null): RoleContract
    {
        $role = static::where('name', $name)->first();

        if (!$role) {
            throw new \InvalidArgumentException("There is no role named `{$name}`.");
        }

        return $role;
    }

    public static function findById(int $id, $guardName = null): RoleContract
    {
        $role = static::where('id', $id)->first();

        if (! $role) {
            throw new \InvalidArgumentException("There is no role named `{$id}`.");
        }

        return $role;
    }
}

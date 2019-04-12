<?php

namespace ZanySoft\LaravelRoles\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class VerifyPermission
{
    /**
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param int|string $permission
     *
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $permission, $all = true)
    {
        if (str_contains($permission, '&')) {
            $all = true;
        } else {
            $all = false;
        }

        $permission = str_replace(' ', '', $permission);

        if ($this->auth->check() && $this->auth->user()->hasPermission($permission, $all)) {
            return $next($request);
        }

        $permission = str_replace('|', "' and '", $permission);
        $permission = str_replace(['.', '_'], ' ', $permission);

        $msg = sprintf("You don't have a permission for '%s'.", $permission);
        //$msg = "Unauthorized request! You don't have a permission for desired action";

        if ($request->expectsJson()) {
            return response()->json(array(
                'error' => 403,
                'message' => $msg,
            ), 403);
        }

        abort('403', $msg);
    }
}

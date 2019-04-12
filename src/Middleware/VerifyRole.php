<?php

namespace ZanySoft\LaravelRoles\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class VerifyRole
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
     * @param int|string $role
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $role)
    {
        if ($this->auth->check() && $this->auth->user()->hasRole($role)) {
            return $next($request);
        }

        $role = str_replace('.', ' ', $role);

        $msg = sprintf("You don't have a required '%s' role.", $role);

        if ($request->expectsJson()) {
            return response()->json(array(
                'error' => 403,
                'message' => $msg,
            ), 403);
        }

        abort('403', $msg);
    }
}

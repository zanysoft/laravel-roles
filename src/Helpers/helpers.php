<?php

if (!function_exists('can')) {
    function can($expression)
    {
        if (Auth::check() && Auth::user()->hasPermission($expression)) {
            return true;
        }
        return false;
    }

}
if (!function_exists('hasRole')) {
    function hasRole($expression)
    {
        if (Auth::check() && Auth::user()->hasRole($expression)) {
            return true;
        }

        return false;
    }
}

if (function_exists('hasPermission')) {
    function hasPermission($expression)
    {
        if (Auth::check() && Auth::user()->hasPermission($expression)) {
            return true;
        }
        return false;
    }
}

if (function_exists('hasPermission')) {
    function allowed($expression)
    {
        if (Auth::check() && Auth::user()->allowed($expression)) {
            return true;
        }
        return false;
    }
}

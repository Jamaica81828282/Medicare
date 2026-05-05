<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $isAdmin = DB::table('model_has_roles')
            ->join('roles', '[roles.id](http://roles.id)', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_id', Auth::id())
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->where('[roles.name](http://roles.name)', 'admin')
            ->exists();
        if (!$isAdmin) {
            abort(403, 'Access denied. Admin only.');
        }
        return $next($request);
    }
}
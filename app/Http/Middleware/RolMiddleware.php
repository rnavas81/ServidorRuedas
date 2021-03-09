<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AsignacionRol;

class RolMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $rol = AsignacionRol::with("roles","users")
            ->where('idUsuario',$user->id)
            ->first();
        
        if ($rol->roles->id == 1) {
            return $next($request);
        }else{
            return response()->json([
                    'mensaje' => 'Unauthenticated.',
                    'status' => 403
                        ], 403);
        }
    }
}

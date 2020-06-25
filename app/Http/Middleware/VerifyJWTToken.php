<?php

namespace App\Http\Middleware;

use Closure;

use Tymon\JWTAuth\Facades\JWTAuth as JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;


class VerifyJWTToken 
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::toUser($request->bearerToken());
        } catch (JWTException $e) {
            if ($e instanceof TokenExpiredException) {
                return response()->json(['token_expired']);
            } else if ($e instanceof TokenInvalidException) {
                return response()->json(['token_invalid']);
            } else {
                return response()->json(['error' => 'Token is required']);
            }
        }

        return $next($request);
    }
}

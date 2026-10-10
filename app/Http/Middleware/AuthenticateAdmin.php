<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class AuthenticateAdmin {
 public function handle(Request $request, Closure $next): Response {
  if(!$request->session()->has('admin_user.user_id')) return redirect()->route('admin.login');
  return $next($request);
 }
}

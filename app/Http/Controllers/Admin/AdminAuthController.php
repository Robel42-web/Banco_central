<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller {
 public function showLogin(){ return session()->has('admin_user') ? redirect()->route('admin.dashboard') : view('admin.auth.login'); }
 public function login(Request $request){
  $data=$request->validate(['email'=>'required|email|max:254','password'=>'required|string|max:200']);
  $url=rtrim((string)env('SUPABASE_URL'),'/'); $key=(string)env('SUPABASE_ANON_KEY');
  if(!$url||!$key) throw ValidationException::withMessages(['email'=>'Falta configurar SUPABASE_URL o SUPABASE_ANON_KEY en .env.']);
  try { $r=Http::acceptJson()->withHeaders(['apikey'=>$key])->timeout(10)->post($url.'/auth/v1/token?grant_type=password',$data); }
  catch(\Throwable $e){ report($e); throw ValidationException::withMessages(['email'=>'No se pudo conectar con Supabase Auth.']); }
  $uid=$r->json('user.id');
  if(!$r->successful()||!is_string($uid)) throw ValidationException::withMessages(['email'=>'Credenciales incorrectas o cuenta no autorizada.']);
  try { $admin=DB::table('admin_users')->where('user_id',$uid)->where('status','active')->first(['user_id','full_name','role','branch_id','atm_id']); }
  catch(\Throwable $e){ report($e); throw ValidationException::withMessages(['email'=>'No se pudo validar el permiso en la base central.']); }
  if(!$admin) throw ValidationException::withMessages(['email'=>'Este usuario no tiene permisos administrativos activos.']);
  $request->session()->regenerate();
  $request->session()->put('admin_user',['user_id'=>$admin->user_id,'full_name'=>$admin->full_name,'role'=>$admin->role,'branch_id'=>$admin->branch_id,'atm_id'=>$admin->atm_id]);
  return redirect()->intended(route('admin.dashboard'));
 }
 public function logout(Request $request){ $request->session()->forget('admin_user'); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('admin.login')->with('status','Sesión cerrada correctamente.'); }
}

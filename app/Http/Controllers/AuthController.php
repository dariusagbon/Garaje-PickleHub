<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function showLogin(){return view('auth.login');} public function showRegister(){return view('auth.register');}
 public function register(Request $r){$d=$r->validate(['name'=>'required|string|max:120','email'=>'required|email|unique:users','password'=>'required|confirmed|min:8']); $u=User::create($d); Auth::login($u); return redirect()->intended($u->is_admin ? route('admin.dashboard') : route('dashboard'));}
 public function login(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required']); if(!Auth::attempt($d,$r->boolean('remember'))) return back()->withErrors(['email'=>'Invalid credentials.'])->onlyInput('email'); $r->session()->regenerate(); $user=Auth::user(); return redirect()->intended($user->is_admin ? route('admin.dashboard') : route('dashboard'));}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect('/');}
}

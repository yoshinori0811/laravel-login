<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{

    /**
     * ログインフォームの表示
     * @return View
     */
    public function showLogin()
    {
        return view('login.login_form');
    }

    /**
     * ログイン処理
     * @param App\Http\Requests\LoginFormRequest $request
     */
    public function login(LoginFormRequest $request)
    {
        $credentials = $request->only('email', 'password');

        // １アカウントがロックされていたらはじく
        $user = User::where('email', '=', $credentials['email'])->first();
        if (! is_null($user)) {
            if ($user->locked_flg === 1) {
                return back()->withErrors([
                    'danger' => 'アカウントがロックされています。',
                ]);
            }
            if (Auth::attempt($credentials)) {
                $request->session()->regenerate();
    
                // ２成功したらエラーカウントリセット
                if($user->error_count > 0){
                    $user->error_count = 0;
                    $user->save();
                }
                return redirect()->route('home')->with('success', 'ログイン成功しました！');
            }
            
            // ３ログイン失敗したらエラーカウントを1増やす
            $user->error_count = $user->error_count + 1;
            $user->save();
            if($user->error_count > 5) {
                $user->locked_flg = 1;
                $user->save();
                return back()->withErrors([
                    'danger' => 'アカウントがロックされました。ロックを解除したい場合は運営者に連絡してください。',
                ]);
    
            }
        }


        return back()->withErrors([
            'danger' => 'メールアドレスかパスワードが間違っています。',
        ]);

    }
    
    /**
     * ユーザーをアプリケーションからログアウトさせる
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('showLogin')->withErrors([
            'danger' => 'ログアウトしました。',]);
    }



    /**
     * ホーム画面表示
     * 
     */
    public function showHome()
    {
        return view('login.home');
    }


}

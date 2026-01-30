<?php

declare(strict_types=1);

namespace PrestoWorld\Bridge\WordPress\Http\Controllers;

use Witals\Framework\Http\Response;
use Witals\Framework\Http\Request;
use Witals\Framework\Contracts\Auth\AuthContextInterface;
use Witals\Framework\Http\AbstractController;

class LoginController extends AbstractController
{
    public function __construct(
        protected AuthContextInterface $auth,
        protected \Witals\Framework\Contracts\Auth\TokenStorageInterface $tokenStorage
    ) {}


    public function show(Request $request): Response
    {
        if ($this->auth->getToken() !== null) {
            return $this->redirect('/wp-admin/');
        }

        $redirectTo = $request->query('redirect_to', '/wp-admin/');
        
        $html = $this->renderLoginPage($redirectTo);
        return $this->html($html);
    }

    public function login(Request $request): Response
    {
        $loginKey = $request->input('log');
        $password = $request->input('pwd');
        $redirectTo = $request->input('redirect_to', '/wp-admin/');

        $orm = app(\Cycle\ORM\ORMInterface::class);
        $repo = $orm->getRepository(\App\Models\WpUser::class);
        
        // Find user by login or email
        $user = $repo->select()
            ->where('login', $loginKey)
            ->orWhere('email', $loginKey)
            ->fetchOne();

        if ($user) {
            error_log("LoginController: User found: {$user->login} (ID: {$user->id})");
            $hasher = new \PrestoWorld\Bridge\WordPress\Support\PasswordHash(8, true);
            $check = $hasher->CheckPassword($password, $user->password);
            
            // Check for plain MD5 (common in migrated/dev DBs)
            $isMd5 = md5($password) === $user->password;
            
            if ($check || $isMd5) {
                error_log("LoginController: Password verified for user {$user->login}");
                // Successful login
                $payload = [
                    'id' => $user->id,
                    'name' => $user->displayName,
                    'roles' => ['administrator'] // Force admin capability for now
                ];

                // Create and persist token
                $token = $this->tokenStorage->create($payload);

                // Create actor object for AuthContext
                $actor = (object)[
                    'id' => $user->id,
                    'name' => $user->login,
                    'roles' => ['administrator']
                ];

                $this->auth->start($token, $actor);
                return $this->redirect($redirectTo);
            } else {
                error_log("LoginController: Password failed for user {$user->login}. Provided: $password. Stored Hash: " . substr($user->password, 0, 10) . "...");
            }
        } else {
             error_log("LoginController: User not found for login key: $loginKey");
        }
        
        // Failed login
        $html = $this->renderLoginPage($redirectTo, "Invalid username or password.");
        return $this->html($html);
    }



    protected function renderLoginPage(string $redirectTo, ?string $error = null): string
    {
        $errorHtml = $error ? "<div id='login_error'>$error</div>" : '';
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <title>Log In &lsaquo; PrestoWorld &#8212; WordPress</title>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@icon/dashicons@0.9.7/dashicons.min.css'>
            <style>
                body { background: #f0f0f1; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Oxygen-Sans, Ubuntu, Cantarell, \"Helvetica Neue\", sans-serif; }
                .login { width: 320px; padding: 8% 0 0; margin: auto; }
                .login h1 { text-align: center; margin-bottom: 20px; }
                .login h1 a { color: #3c434a; text-decoration: none; font-size: 32px; font-weight: 400; }
                #login_error { border-left: 4px solid #d63638; background: #fff; box-shadow: 0 1px 1px 0 rgba(0,0,0,.1); padding: 12px; margin-bottom: 20px; }
                #loginform { background: #fff; border: 1px solid #c3c4c7; padding: 26px 24px 34px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
                label { display: block; margin-bottom: 5px; font-size: 14px; }
                input[type=text], input[type=password] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #dcdcde; box-sizing: border-box; }
                .button-primary { background: #2271b1; border-color: #2271b1; color: #fff; text-decoration: none; text-shadow: none; display: inline-block; padding: 0 10px; line-height: 2.15384615; min-height: 30px; margin: 0; cursor: pointer; border-width: 1px; border-style: solid; border-radius: 3px; white-space: nowrap; box-sizing: border-box; font-size: 13px; }
            </style>
        </head>
        <body class='login'>
            <div class='login'>
                <h1><a href='/'>PrestoWorld</a></h1>
                $errorHtml
                <form name='loginform' id='loginform' action='/wp-login.php' method='post'>
                    <p>
                        <label for='user_login'>Username or Email Address</label>
                        <input type='text' name='log' id='user_login' value=''>
                    </p>
                    <p>
                        <label for='user_pass'>Password</label>
                        <input type='password' name='pwd' id='user_pass' value=''>
                    </p>

                    <input type='hidden' name='redirect_to' value='".htmlspecialchars($redirectTo)."'>
                    <p class='submit'>
                        <input type='submit' name='wp-submit' id='wp-submit' class='button button-primary' value='Log In'>
                    </p>
                </form>
            </div>
        </body>
        </html>
        ";
    }
}

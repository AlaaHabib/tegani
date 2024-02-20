<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use External\Bar\Auth\LoginService;
use External\Baz\Auth\Authenticator;
use External\Baz\Auth\Responses\IResponse;
use External\Baz\Auth\Responses\Success;
use External\Foo\Auth\AuthWS;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $login = $request->login;
            $password = $request->password;

            // Extract the company prefix from the login
            $companyPrefix = strtoupper(substr($login, 0, strpos($login, '_')));
            
            // Authenticate based on the company prefix
            switch ($companyPrefix) {
                case 'FOO':
                    $authService = new AuthWS();
                    try {
                        $authService->authenticate($login, $password);
                        $token = $this->generateJwtToken($login, $password, $companyPrefix);
                    } catch (\Exception $e) {
                        return response()->json(['status' => 'failure']);
                    }
                    break;

                case 'BAR':
                    $authService = new LoginService();
                    if ($authService->login($login, $password)) {
                        $token = $this->generateJwtToken($login, $password, $companyPrefix);
                    }
                    break;

                case 'BAZ':
                    $authService = new Authenticator();
                    $response = $authService->auth($login, $password);
                    if ($response instanceof IResponse && $response instanceof Success) {

                        $token = $this->generateJwtToken($login, $password, $companyPrefix);
                    }
                    break;

                default:
                    return response()->json(['status' => 'failure']);
            }


            if ($token) {
                return response()->json(['status' => 'success', 'token' => $token]);
            } else {
                return response()->json(['status' => 'failure']);
            }
        } catch (JWTException $e) {
            return response()->json(['status' => 'failure']);
        }
    }

    private function generateJwtToken($login, $password, $companyPrefix)
    {
        try {
            // Create a payload for the JWT token
            $payload = [
                'login' => $login,
                'company' => $companyPrefix,
            ];

            // Generate the JWT token
            $token = JWTAuth::claims($payload)->attempt([
                'name' => $login,
                'password' => $password,
            ]);
            return $token;
        } catch (\Exception $e) {
            Log::error('Error JWT token generation: ' . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTrace(),
            ]);
            return null;
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class MainController extends Controller
{
    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/login",
     *     tags={"ავტორიზაციის მეთოდი"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "ავტორიზაციის API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          description = "მომხმარებელი ავტორიზაციას გაივლის იმეილით და პაროლით",
     *          
     *          @OA\JsonContent (
     *              required = {"email", "password"},
     *              
     *              @OA\Property (
     *                  property = "email",
     *                  type = "string",
     *                  format = "email"
     *              ),
     * 
     *              @OA\Property (
     *                  property = "password",
     *                  type = "string",
     *                  format = "password"
     *              )
     *          )
     *      )
     *  )
     */
    public function Login(Request $request) {
        $data = $this->validate($request, [
            "email" => "required|email",
            "password" => "required"
        ]);

        if(Auth::attempt($data)) {
            $token = Auth::user()->createToken("TOKEN")->accessToken;

            return response()->json([
                "user" => Auth::user(),
                "token" => $token
            ], 200);
        }else {
            return response()->json([
                "errors" => [
                    "message" => "Login attempt failed!"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/check_user",
     *     tags={"იუზერის გადამოწმების მეთოდი"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "იუზერის გადამოწმების API"
     *     )
     *  )
     */
    public function Check_User() {
        if(Auth::check() && Auth::guard("api")->check()) {
            return response()->json([
                "status" => true
            ], 200);
        }else {
            return response()->json([
                "status" => false
            ], 422);
        }
    }
}
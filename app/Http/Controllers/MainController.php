<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Roles;
use App\Models\ResetPass;
use App\Models\Category;
use App\Models\Permission;
use App\Models\priorities;
use App\Models\Cases;
use App\Models\Projects;
use App\Models\Tasks;
use App\Models\Status;
use Auth;
use DB;
use Carbon\Carbon;

class MainController extends Controller
{
    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/login",
     *     tags={"ავტორიზაციის API"},
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
        // ხდება მომხმარებლის მიერ შეყვანილი ავტორიზაციისთვის საჭირო მონაცემების ვალიდაცია
        $data = $this->validate($request, [
            "email" => "required|email",
            "password" => "required"
        ]);

        if(Auth::attempt($data)) {
            // თუ მომხმარებლის ავტორიზაციის მცდელობა წარმატებით განხორციელდა დაგენერირდება ბეარერ ტოკენი
            $token = Auth::user()->createToken("TOKEN")->accessToken;
            // დააბრუნებს წარმატების შეტყობინებას
            return response()->json([
                "user" => Auth::user(), // ავტორიზირებული მომხმარებლის ინფორმაცია
                "token" => $token // ბეარერ ტოკენი
            ], 200);
        }else {
            // თუ ავტორიზაცია ვერ განხორციელდა, დააბრუნებს წარმატების შეტყობინებას
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
     *     tags={"იუზერის გადამოწმების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "იუზერის გადამოწმების API"
     *     )
     * )
     */
    public function Check_User() {
        if(Auth::check() && Auth::guard("api")->check()) {
            // ხდება იუზერის გადამოწმება თუ ავტორიზირებულია და ტოკენიც ვალიდურია დააბრუნებს წარმატების შეტყობინებას გარკვეული
            // ინფორმაციით
            return response()->json([
                "status" => true,
                "role" => Roles::select("name")->where("id", Auth::user()->role)->first()->name
            ], 200);
        }else {
            // წინააღმდეგ შემთხვევაში დააბრუნებს 422 კოდის შეტყობინებას
            return response()->json([
                "status" => false
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/reset/send_reset",
     *     tags={"პაროლის აღდგენი API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული მარშუტის საშუალებით ხდება კოდის გენერირება, რომელიც გაიგზავნება იუზერის მიერ შეყვანილ იმეილზე, რომელსაც სურს პაროლის აღდგენა"
     *     )
     *  )
     */
    public function Send_Reset(Request $request) {
        $this->validate($request, [
            "email" => "required|email|min:7|max:50",
        ]);

        //მასივი, რომლის ელემენტებისგანაც უნდა მოხდეს შემთხვევითი
        //სტრიქონის შედგენა
        $letters = ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", 1, 2, 3, 4, 5, 6, 7, 8, 9, "$", "!", "@", "#", "%", "&", "(", ")"];

        $random_str = ""; // ამ ცვლადში შეინახება სიმბოლოები მიყოლებით, რითაც შეიქმნება უსაფრთხოების
        //კოდი
        /**
         * მინიმალური და მაქსიმალური მნიშვნელობები შემთხვევითი
         * სიმბოლოს დასაგენერირებლად
         * @var მინიმალური,მაქსიმალური*/
        $rand_min = 0;
        $rand_max = count($letters) - 1;

        for($i = 0; $i < 6; $i++) {
            $random_str .= $letters[random_int($rand_min, $rand_max)];
        }
        // დაგენერირებული შემთხვევითი სტრიქონი სეინახება მონაცემთა ბაზის ცხრილში
        // რათა პაროლის აღდგენის შემდგომ იმეილზე მისული სტრიქონი გადამოწმდეს მართლა არსებობს
        // თუ არა ბაზაში
        $create = ResetPass::create([
            "random_string" => $random_str,
            "email" => $request->input("email")
        ]);

        // მოხდება მაილის გაგზავნის მცდელობა და შეცდომის/გამონაკლისის არსებობის
        // შემთხვევაში მოხდება საწყის გვერდზე გადამისამართება და შეცდომის შეტყობინების
        // დაგენერირება
        try {
            Mail::send("layouts.mail", ["code" => $random_str], function($message) use($request, $random_str) {
                $message->to($request->input("email"));
                $message->from("davit.chechelashvili@geolab.edu.ge", "RDA პაროლის აღდგენა");
                $message->subject("კოდი");
            });

            return response()->json([
                "status" => "კოდი წარმატებით გაიგზავნა. გთხოვთ შეამოწმოთ იმეილი"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "status" => "კოდი ვერ გაიგზავნა",
                "error_exception" => $e->getMessage()
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/reset/password_reset",
     *     tags={"პაროლის აღდგენი API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული მარშუტის საშუალებით უკვე ხდება პაროლის აღდგენა იმეილზე გაგზავნილი კოდის საშუალებით და ხდება ახალი პაროლის შეყვანა"
     *     )
     *  )
     */
    public function Password_Reset(Request $request) {
        //გაგზავნილი მონაცემების ვალიდაცია
        $this->validate($request, [
            "random_code" => "required|min:6|max:6",
            "password" => "required"
        ]);

        $get_email = ResetPass::where("random_string", $request->input("random_code"))->first();
        $final_email = $get_email->email;

        try {
            $user = User::where("email", $final_email)->first();
            $user->password = Hash::make($request->input("password"));
            $user->save();

            return response()->json([
                "success" => [
                    "status" => "პაროლის აღდგენა წარმატებით დასრულდა. შეგიძლიათ შეხვიდეთ სისტემაში"
                ]
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "error" => [
                    "status" => "პაროლის აღდგენავერ მოხერხდა"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/role/roles",
     *     tags={"როლების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "როლების გენერაციის API"
     *     )
     *  )
     */
    public function Generate_Roles() {
        return Roles::all();
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/role/delete_role/{id}",
     *     tags={"როლების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "როლების წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "როლის აიდი",
     *          required = true
     *      )
     *  )
     */
    public function Delete_Role($id) {
        $delete_role = Roles::find($id)->delete(); // მოხდება კონკრეტული როლის მოძებნა აიდის მიხედვით და მისი ბაზიდან ამოშლა

        if($delete_role) {
            // თუ წაშლა წარმატებით განხორციელდა დააბრუნებს შესაბამის წარმატების შეტყობინებას
            return response()->json([
                "success" => [
                    "status" => 1,
                    "message" => "role deleted successfully!"
                ]
            ], 200);
        }else {
            // თუ წაშლა წარმატებით განხორციელდა დააბრუნებს შესაბამის წარუმატებლობის შეტყობინებას
            return response()->json([
                "fail" => [
                    "status" => 0,
                    "message" => "role deleted added!"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/role/edit_role/{id}",
     *     tags={"როლების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "როლის რედაქტირების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "როლის აიდი",
     *          required = true
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          description = "მარშუტზე გაიგზავნება ფორმიდან შეყვანილი ახალი როლის დასახელება",
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          ) 
     *     )
     *  )
     */
    public function Edit_Role($id, Request $request) {
        // ფორმის ველიდან შეყვანილი როლის დასახელების ვალიდირება
        $this->validate($request, [
            "name" => "required"
        ]);

        try {
            $role = Roles::find($id); // პარამეტრიდან გადაცემული როლის აიდის მიხედვით მისწვდება იმ კონკრეტულ როლს
            $role->role = $request->input("name"); // ძველს როლს გადაეწერება ახალი როლი
            $role->save(); // მოხდება დარედაქტირებული მონაცემის შენახვა

            return response()->json([
                "status" => 1,
                "message" => "როლი დარედაქტირდა"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "errors" => [
                    "status" => 0,
                    "exception" => $e->getMessage(),
                    "error" => [
                        "როლი ვერ დარედაქტირდა"
                    ]
                ]
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/category/add_category",
     *     tags={"კატეგორიების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული API-ს საშუალებით მოხდება კატეგორიის დამატება"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          description = "კატეგორიის დამატების API. ფორმაში შევა მხოლოდ კატეგორიის დასახელება",
     *          
     *          @OA\JsonContent (
     *              required = {"category_name"},
     *              
     *              @OA\Property (
     *                  property = "category_name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *      )
     *  )
     */
    public function Add_Category(Request $request) {
        $this->validate($request, [
            "category_name" => "required"
        ]);

        $add_category = Category::create([
            "category_name" => $request->category_name
        ]);

        if($add_category) {
            return response()->json([
                "success" => [
                    "status" => 1,
                    "message" => "Category added successfully!"
                ]
            ], 200);
        }else {
            return response()->json([
                "fail" => [
                    "status" => 0,
                    "message" => "Category not added!"
                ]
            ], 422);
        }
    }
    
    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/category/delete_category/{id}",
     *     tags={"კატეგორიების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული მარშუტის საშუალებით ხდება კატეგორიის წაშლა"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "კატეგორიის აიდი",
     *          required = true
     *      )
     *  )
     */
    public function Delete_Category($id) {
        $delete_category = Category::find($id)->delete();

        if($delete_category) {
            return response()->json([
                "success" => [
                    "status" => 1,
                    "message" => "Category deleted successfully!"
                ]
            ], 200);
        }else {
            return response()->json([
                "fail" => [
                    "status" => 0,
                    "message" => "Category deleted added!"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/category/list",
     *     tags={"კატეგორიების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "კატეგორიების სიის API"
     *     )
     * )
     */
    public function Categories() {
        return Category::all();
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/category/edit_category/{id}",
     *     tags={"კატეგორიების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული მარშუტის საშუალებით ხდება კატეგორიის განახლება/რედაქტირება"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "კატეგორიის აიდი",
     *          required = true
     *      ),
     *      @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"category_name"},
     *              
     *              @OA\Property (
     *                  property = "category_name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *      )
     *  )
     */
    public function Edit_Category($id, Request $request) {
        $this->validate($request, [
            "category_name" => "required"
        ]);

        try {
            $category = Category::find($id);
            $category->category_name = $request->category_name;
            $category->save();

            return response()->json([
                "success" => [
                    "status" => 1,
                    "message" => "Category updated successfully!"
                ]
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "success" => [
                    "status" => 0,
                    "error_exception" => $e->getMessage(),
                    "message" => "Category not updated!"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/permission/delete_permission/{id}",
     *     tags={"პერმიშენების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მოცემული მარშუტის საშუალებით ხდება პერმიშენის წაშლა"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პერმიშენის აიდი",
     *          required = true
     *      )
     *  )
     */
    public function Delete_Permission($id) {
        $delete_permission = Permission::find($id)->delete();

        if($delete_permission) {
            return response()->json([
                "success" => [
                    "status" => 1,
                    "message" => "permission deleted successfully!"
                ]
            ], 200);
        }else {
            return response()->json([
                "fail" => [
                    "status" => 0,
                    "message" => "permission deleted added!"
                ]
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/permission/add_permission",
     *     tags={"პერმიშენების API"},
     *     
     *     @OA\Response(
     *          response = "200",
     *          description = "პერმიშენების დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          description = "მარშუტზე გაიგზავნება ფორმიდან შეყვანილი პერმიშენის სახელი, სათაური ქართულად და როლის აიდი",
     *          
     *          @OA\JsonContent (
     *              required = {"name", "title", "role_id"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     * 
     *              @OA\Property (
     *                  property = "title",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "role_id",
     *                  type = "number",
     *                  format = "number"
     *              )
     *          )
     *      )
     *  )
     */
    public function Add_Permission(Request $request) {
        $this->validate($request, [
            "name" => "required",
            "title" => "required",
            "role_id" => "required"
        ]);

        $add_permission = Permission::create([
            "role_id", $request->role_id,
            "name" => $request->name,
            "title" => $request->title
        ]);

        if($add_permission) {
            return response()->json([
                "success" => [
                    "message" => [
                        "success" => [
                            "პერმიშენი დაემატა"
                        ]
                    ],
                ]
            ], 200);
        }else {
            return response()->json([
                "errors" => [
                    "error" => [
                        "პერმიშენი ვერ დაემატა"
                    ]
                ]
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/permission/edit_permission/{id}",
     *     tags={"პერმიშენების API"},
     *     
     *     @OA\Response(
     *          response = "200",
     *          description = "პერმიშენის რედაქტირების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          description = "მარშუტზე გაიგზავნება ფორმიდან შეყვანილი პერმიშენის სახელი, სათაური ქართულად და როლის აიდი",
     *          
     *          @OA\JsonContent (
     *              required = {"name", "title", "role_id"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     * 
     *              @OA\Property (
     *                  property = "title",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "role_id",
     *                  type = "number",
     *                  format = "number"
     *              )
     *          )
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პერმიშენის აიდი",
     *          required = true
     *      )
     *  )
     */
    public function Edit_Permission($id, Request $request) {
        $this->validate($request, [
            "name" => "required",
            "title" => "required",
            "role_id" => "required"
        ]);

        try {
            $permission = Permission::find($id);
            $permission->name = $request->name;
            $permission->title = $request->title;
            $permission->role_id = $request->role_id;
            $permission->save();

            return response()->json([
                "success" => [
                    "message" => [
                        "success" => [
                            "პერმიშენი დარედაქტირდა"
                        ]
                    ]
                ]
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "errors" => [
                    "error" => [
                        "პერმიშენი ვერ დარედაქტირდა"
                    ]
                ]
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/priority/add",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პრიორიტეტის დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Add_Priority(Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        $add_priority = priorities::create([
            "name" => $request->name
        ]);

        if($add_priority) {
            return response()->json([
                "message" => "პრიორიტეტი დაემატა"
            ], 200);
        }else {
            return response()->json([
                "message" => "პრიორიტეტი ვერ დაემატა"
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/priority/delete/{id}",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პრიორიტეტის წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პრიორიტეტის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Delete_Priority($id) {
        $delete_priority = priorities::find($id)->delete();

        if($delete_priority) {
            return response()->json([
                "message" => "პრიორიტეტი წაიშალა"
            ], 200);
        }else {
            return response()->json([
                "message" => "პრიორიტეტი ვერ წაიშალა"
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/priority/edit/{id}",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პრიორიტეტის რედაქტირების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პრიორიტეტის აიდი",
     *          required = true
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Edit_Priority($id, Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        try {
            priorities::where("id", $id)->update([
                "name" => $request->name
            ]);

            return response()->json([
                "message" => "პრიორიტეტი დარედაქტირდა"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "message" => "პრიორიტეტი ვერ დარედაქტირდა"
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/priority/list",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პრიორიტეტების სიის API"
     *     )
     * )
     */
    public function Priorities() {
        return priorities::paginate(20);
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/priority/full_list",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პრიორიტეტების სიის API (without pagination)"
     *     )
     * )
     */
    public function All_Priority() {
        return priorities::all();
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/priority/get_by_id/{id}",
     *     tags={"პრიორიტეტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "კონკრეტული პრიორიტეტის წამოღების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პრიორიტეტის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Get_Priority($id) {
        return priorities::where("id", $id)->first();
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/case/add",
     *     tags={"საქმეების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "საქმის დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *      )
     * )
     */
    public function Add_Case(Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        $add_case = Cases::create([
            "name" => $request->name
        ]);

        if($add_case) {
            return response()->json([
                "message" => "საქმე დაემატა"
            ], 200);
        }else {
            return response()->json([
                "message" => "საქმე ვერ დაემატა"
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/case/delete/{id}",
     *     tags={"საქმეების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "საქმის წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "საქმის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Delete_Case($id) {
        $delete_case = Cases::find($id)->delete();

        if($delete_case) {
            return response()->json([
                "message" => "საქმე წაიშალა"
            ], 200);
        }else {
            return response()->json([
                "message" => "საქმე ვერ წაიშალა"
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/case/edit/{id}",
     *     tags={"საქმეების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "საქმეების რედაქტირების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "საქმის აიდი",
     *          required = true
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Edit_Case($id, Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        try {
            Cases::where("id", $id)->update([
                "name" => $request->name
            ]);

            return response()->json([
                "message" => "საქმე დარედაქტირდა"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "message" => "საქმე ვერ დარედაქტირდა"
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/case/list",
     *     tags={"საქმეების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "საქმეების სიის API"
     *     )
     * )
     */
    public function All_Cases(Request $request) {
        $case = Cases::orderBy("id", "DESC");

        if($request->keyword != '') {
            $case->where('name', 'like', '%' . $request->keyword . '%');
        }

        $case = $case->paginate(20);
        
        return $case;
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/case/get_by_id/{id}",
     *     tags={"საქმეების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "კონკრეტული საქმის წამოღების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "საქმის აიდი",
     *          required = true
     *     ),
     * )
     */
    public function Get_Case($id) {
        return Cases::where("id", $id)->first();
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/project/add",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პროექტის დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Add_Project(Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        $add_priority = Projects::create([
            "name" => $request->name
        ]);

        if($add_priority) {
            return response()->json([
                "message" => "პროექტი დაემატა"
            ], 200);
        }else {
            return response()->json([
                "message" => "პროექტი ვერ დაემატა"
            ], 422);
        }
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/project/delete/{id}",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პროექტის წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პროექტის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Delete_Project($id) {
        $delete_project = Projects::find($id)->delete();

        if($delete_project) {
            return response()->json([
                "message" => "პროექტი წაიშალა"
            ], 200);
        }else {
            return response()->json([
                "message" => "პროექტი ვერ წაიშალა"
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/project/list",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პროექტების სიის API"
     *     )
     * )
     */
    public function Projects(Request $request) {
        $project = Projects::orderBy("id", "DESC");

        if($request->keyword != '') {
            $project->where('name','like','%'.$request->keyword.'%');
        }

        $project = $project->paginate(20);
        return $project;
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/project/full_list",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პროექტების სიის API (without pagination)"
     *     )
     * )
     */
    public function All_Project(Request $request) {
        $project = Projects::orderBy("id", "DESC");

        if($request->keyword != '') {
            $project->where('name', 'like', '%' . $request->keyword . '%');
        }

        return $project->get();
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/project/get_by_id/{id}",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "კონკრეტული პროექტის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პროექტის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Project_By_Id($id) {
        return Projects::where("id", $id)->first();
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/project/edit/{id}",
     *     tags={"პროექტების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "პროექტების რედაქტირების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "პროექტის აიდი",
     *          required = true
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Edit_Project($id, Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        try {
            Projects::where("id", $id)->update([
                "name" => $request->name
            ]);

            return response()->json([
                "message" => "პროექტი დარედაქტირდა"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "message" => "პროექტი ვერ დარედაქტირდა"
            ], 422);
        }
    }
    
    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/task/list",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალებების გენერაციის API"
     *     )
     *  )
     */
    public function Tasks() {
        return Tasks::paginate(20);
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/task/delete/{id}",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალების წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "დავალების აიდი",
     *          required = true
     *      )
     *  )
     */
    public function Delete_Task($id) {
        $delete_task = Tasks::find($id)->delete();

        if($delete_task) {
            return response()->json([
                "message" => "დავალება წაიშალა"
            ], 200);
        }else {
            return response()->json([
                "message" => "დავალება ვერ წაიშალა"
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/task/add",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალებების დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name", "case", "project_name", "description", "files", "status"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "case",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "project_name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "description",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "files",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "status",
     *                  type = "number",
     *                  format = "number"
     *              )
     *          )
     *     )
     * )
     */
    public function Add_Task(Request $request) {
        $this->validate($request, [
            "name" => "required",
            "case" => "required",
            "project_name" => "required",
            "description" => "required",
            "status" => "required"
        ]);

        $files = [];

        if($request->hasfile("files")) {
            foreach($request->file("files") as $file) {
                $name = $file->getClientOriginalName();
                $file->move(public_path("task_files"), $name);
                $files[] = $name;
            }

            $add_task = Tasks::create([
                "name" => $request->input("name"),
                "case" => $request->input("case"),
                "project_name" => $request->input("project_name"),
                "description" => $request->input("description"),
                "status" => $request->input("status"),
                "files" => $files
            ]);

            if($add_task) {
                return response()->json([
                    "message" => "დავალება აიტვირთა"
                ], 200);
            }else {
                return response()->json([
                    "message" => "დავალება ვერ აიტვირთა"
                ], 422);
            }
        }

        $add_task = Tasks::create([
            "name" => $request->input("name"),
            "case" => $request->input("case"),
            "project_name" => $request->input("project_name"),
            "description" => $request->input("description"),
            "status" => $request->input("status")
        ]);

        if($add_task) {
            return response()->json([
                "message" => "დავალება აიტვირთა"
            ], 200);
        }else {
            return response()->json([
                "message" => "დავალება ვერ აიტვირთა"
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/task/edit/{id}",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალებების რედაქტირების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name", "case", "project_name", "description", "files", "status"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "case",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "project_name",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "description",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "files",
     *                  type = "string",
     *                  format = "string"
     *              ),
     *              @OA\Property (
     *                  property = "status",
     *                  type = "number",
     *                  format = "number"
     *              )
     *          )
     *     )
     * )
     */
    public function Edit_Task($id, Request $request) {
        $this->validate($request, [
            "name" => "required",
            "case" => "required",
            "project_name" => "required",
            "description" => "required",
            "status" => "required"
        ]);

        $files = [];

        if($request->hasfile("files")) {
            foreach($request->file("files") as $file) {
                $name = $file->getClientOriginalName();
                $file->move(public_path("task_files"), $name);
                $files[] = $name;
            }

            $edit_task = Tasks::where("id", $id)->update([
                "name" => $request->input("name"),
                "case" => $request->input("case"),
                "project_name" => $request->input("project_name"),
                "description" => $request->input("description"),
                "status" => $request->input("status"),
                "files" => $files
            ]);

            if($edit_task) {
                return response()->json([
                    "message" => "დავალება აიტვირთა"
                ], 200);
            }else {
                return response()->json([
                    "message" => "დავალება ვერ აიტვირთა"
                ], 422);
            }
        }

        $edit_task = Tasks::where("id", $id)->update([
            "name" => $request->input("name"),
            "case" => $request->input("case"),
            "project_name" => $request->input("project_name"),
            "description" => $request->input("description"),
            "status" => $request->input("status"),
            "files" => $files
        ]);

        if($edit_task) {
            return response()->json([
                "message" => "დავალება დარედაქტირდა"
            ], 200);
        }else {
            return response()->json([
                "message" => "დავალება ვერ დარედაქტირდა"
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/task/get_by_id/{id}",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "კონკრეტული დავალების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "დავალების აიდი",
     *          required = true
     *     )
     * )
     */
    public function Get_Task($id) {
        return Tasks::where("id", $id)->first();
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/status/get_by_owner/{id}",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალების წამოღების API იუზერის მიხედვით ვინც ფლობს დავალებას"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "იუზერის აიდი, ვისზეც არის დავალება მიმაგრებული",
     *          required = true
     *     )
     * )
     */
    public function Get_Task_By_Owner($id) {
        return Tasks::where("user_id", $id)->get();
    }

    /**
     * @OA\Delete(
     *     path="/ticket_system_api/public/api/status/delete/{id}",
     *     tags={"სტატუსების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "სტატუსის წაშლის API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "სტატუსის აიდი",
     *          required = true
     *     )
     *  )
     */
    public function Delete_Status($id) {
        $delete_task = Status::find($id)->delete();

        if($delete_task) {
            return response()->json([
                "message" => "სტატუსი წაიშალა"
            ], 200);
        }else {
            return response()->json([
                "message" => "სტატუსი ვერ წაიშალა"
            ], 422);
        }
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/status/add",
     *     tags={"სტატუსების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "სტატუსის დამატების API"
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Add_Status(Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        $add_status = Status::create([
            "name" => $request->name
        ]);

        if($add_status) {
            return response()->json([
                "message" => "სტატუსი დაემატა"
            ], 200);
        }else {
            return response()->json([
                "message" => "სტატუსი ვერ დაემატა"
            ], 422);
        }
    }

    /**
     * @OA\Put(
     *     path="/ticket_system_api/public/api/status/edit/{id}",
     *     tags={"სტატუსების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "სტატუსის რედაქტირების API"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "სტატუსის აიდი",
     *          required = true
     *     ),
     *     @OA\RequestBody(
     *          required = true,
     *          
     *          @OA\JsonContent (
     *              required = {"name"},
     *              
     *              @OA\Property (
     *                  property = "name",
     *                  type = "string",
     *                  format = "string"
     *              )
     *          )
     *     )
     * )
     */
    public function Edit_Status($id, Request $request) {
        $this->validate($request, [
            "name" => "required"
        ]);

        $edit_status = Status::where("id", $id)->update([
            "name" => $request->name
        ]);

        if($edit_status) {
            return response()->json([
                "message" => "სტატუსი დარედაქტირდა"
            ], 200);
        }else {
            return response()->json([
                "message" => "სტატუსი ვერ დარედაქტირდა"
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/status/list",
     *     tags={"სტატუსების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "სტატუსების წამოღების API"
     *     )
     * )
     */
    public function All_Status() {
        return Status::paginate(20);
    }

    /**
     * @OA\Get(
     *     path="/ticket_system_api/public/api/status/get_by_status/{id}",
     *     tags={"დავალებების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "დავალების წამოღების API სტატუსის მიხედვით"
     *     ),
     *     @OA\Parameter(
     *          name = "id",
     *          in="path",
     *          description = "სტატუსის აიდი",
     *          required = true
     *     )
     * )
     */
    public function Get_Task_By_Status($id) {
        return Tasks::where("status_id", $id)->get();
    }

    /**
     * @OA\Post(
     *     path="/ticket_system_api/public/api/add_user",
     *     tags={"იუზერების API"},
     * 
     *     @OA\Response(
     *          response = "200",
     *          description = "მომხმარებლების ატვირთვის API"
     *     )
     * )
     */
    public function Add_Users(Request $request) {
        DB::transaction(function() use($request) {
            User::truncate();
            
            foreach($request->all() as $data) {
                User::insert([
                    "name" => $data['displayName'],
                    "structure" => $data['ssipName'],
                    "position" => $data['positionNameGeo'],
                    "email" => strtolower($data['emailAddress']),
                    "phone" => "+995" . $data['mobilePhoneNumber'],
                    "created_at" => Carbon::now()
                ]);
            }

            User::where("email", "giorgi.katsarava@rda.gov.ge")->update([
                "role" => 1
            ]);
        });

        try {
            DB::commit();

            return response()->json([
                "message" => "თანამშრომლები აიტვირთა"
            ], 200);
        }catch(Exception $e) {
            return response()->json([
                "message" => "თანამშრომლები ვერ დაემატა"
            ], 422);
        }finally {
            return "ტრანზაქცია დასრულებულია";
        }
    }
}
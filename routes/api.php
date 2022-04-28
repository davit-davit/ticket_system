<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MainController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/

Route::post("/login", [MainController::class, "Login"]); // ავტორიზაციის გვერდის მარშუტი

Route::post("/check_user", [MainController::class, "Check_User"])->middleware("auth:api"); // იუზერის გადამოწმების მარშუტი

Route::post("/add_user", [MainController::class, "Add_Users"])->middleware("auth:api"); // იუზერების დამატების მარშუტი

// პაროლის აღდგენის მარსუტების ჯგუფი
Route::group(["prefix" => "reset"], function() {
    Route::post("/send_reset", [MainController::class, "Send_Reset"]); // კოდის გაგზავნის მარშუტი პაროლის აღდგენისთვის
    Route::post("/password_reset", [MainController::class, "Password_Reset"]); // კოდის გაგზავნის მარშუტი პაროლის აღდგენისთვის
});

// ცატეგორიების მარშუტების ჯგუფი
Route::group(["prefix" => "category"], function() {
    Route::post("/add_category", [MainController::class, "Add_Category"])->middleware("auth:api"); // კატეგორიის დამატების მარშუტი

    Route::delete("/delete_category", [MainController::class, "Delete_Category"])->middleware("auth:api")->where("id", "[0-9]+"); // კატეგორიის წაშლის მარშუტი

    Route::put("/edit_category/{id}", [MainController::class, "Edit_Category"])->middleware("auth:api")->where("id", "[0-9]+"); // კატეგორიების რედაქტირების მარშუტი

    Route::get("/list", [MainController::class, "Categories"])->middleware("auth:api"); // კატეგორიების სიის მარშუტი
});

// როლების მარშუტების ჯგუფი
Route::group(["prefix" => "role"], function() {
    Route::get("/roles", [MainController::class, "Generate_roles"])->middleware("auth:api"); // როლების გენერაციის მარშუტი

    Route::delete("/delete_role/{id}", [MainController::class, "Delete_Role"])->middleware("auth:api")->where("id", "[0-9]+"); // როლის წაშლის მარშუტი

    Route::put("/edit_role/{id}", [MainController::class, "Edit_Role"])->middleware("auth:api")->where("id", "[0-9]+"); // კატეგორიების რედაქტირების მარშუტი
});

// პერმიშენების მარშუტების ჯგუფი
Route::group(["prefix" => "permission"], function() {
    Route::delete("/delete_permission/{id}", [MainController::class, "Delete_Permission"])->middleware("auth:api")->where("id", "[0-9]+"); // როლის წაშლის მარშუტი

    Route::delete("/add_permission", [MainController::class, "Delete_Permission"])->middleware("auth:api"); // პერმიშენის დამატების მარშუტი

    Route::put("/edit_permission/{id}", [MainController::class, "Delete_Permission"])->middleware("auth:api")->where("id", "[0-9]+");
});

// პრიორიტეტების მარშუტების ჯგუფი
Route::group(["prefix" => "priority"], function() {
    Route::post("/add", [MainController::class, "Add_Priority"]); // პრიორიტეტის დამატების მარშუტი

    Route::delete("/delete/{id}", [MainController::class, "Delete_Priority"])->middleware("auth:api")->where("id", "[0-9]+"); // პრიორიტეტის წაშლის მარშუტი

    Route::put("/edit/{id}", [MainController::class, "Edit_Priority"])->middleware("auth:api")->where("id", "[0-9]+"); // პრიორიტეტის რედაქტირების მარშუტი

    Route::get("/list", [MainController::class, "Priorities"])->middleware("auth:api"); // პრიორიტეტის სიის მარშუტი

    Route::get("/get_by_id/{id}", [MainController::class, "Get_Priority"])->middleware("auth:api")->where("id", "[0-9]+"); // კონკრეტული პრიორიტეტის წამოღების მარშუტი

    Route::get("/full_list", [MainController::class, "All_Priority"])->middleware("auth:api"); // პრიორიტეტის სიის მარშუტი გვერდებად დაყოფის გარეშე
});

// საქმეების მარშუტების ჯგუფი
Route::group(["prefix" => "case"], function() {
    Route::post("/add", [MainController::class, "Add_Case"]); // საქმეების დამატების მარშუტი

    Route::delete("/delete/{id}", [MainController::class, "Delete_Case"])->middleware("auth:api")->where("id", "[0-9]+"); // საქმეების წაშლის მარშუტი

    Route::get("/list", [MainController::class, "All_Cases"])->middleware("auth:api"); // ქეისების სიის მარშუტი

    Route::put("/edit/{id}", [MainController::class, "Edit_Case"])->middleware("auth:api")->where("id", "[0-9]+"); // საქმის რედაქტირების მარშუტი

    Route::get("/get_by_id/{id}", [MainController::class, "Get_Case"])->middleware("auth:api")->where("id", "[0-9]+"); // კონკრეტული საქმის წამოღების მარშუტი
});

// პროექტების მარშუტების ჯგუფი
Route::group(["prefix" => "project"], function() {
    Route::post("/add", [MainController::class, "Add_Project"]); // საქმეების დამატების მარშუტი

    Route::delete("/delete/{id}", [MainController::class, "Delete_Project"])->middleware("auth:api")->where("id", "[0-9]+"); // საქმეების წაშლის მარშუტი

    Route::get("/list", [MainController::class, "Projects"])->middleware("auth:api"); // პროექტების სიის მარშუტი

    Route::get("/get_by_id/{id}", [MainController::class, "Project_By_Id"])->middleware("auth:api"); // კონკრეტული პროექტის წამოღების მარშუტი 

    Route::put("/edit/{id}", [MainController::class, "Edit_Project"])->middleware("auth:api")->where("id", "[0-9]+"); // პროექტების რედაქტირების მარშუტი

    Route::get("/full_list", [MainController::class, "All_Project"])->middleware("auth:api"); // პროექტების სიის მარშუტი გვერდებად დაყოფის გარეშეს
});

// დავალებების მარსუტების ჯგუფი
Route::group(["prefix" => "task", "middleware" => ["auth:api"]], function() {
    Route::post("/add", [MainController::class, "Add_Task"]); // დავალებების დამატების მარშუტი

    Route::get("/list", [MainController::class, "Tasks"]); // დავალებების გამოტანის მარშუტი

    Route::delete("/delete/{id}", [MainController::class, "Delete_Task"])->where("id", "[0-9]+"); // დავალების წაშლის მარშუტი

    Route::put("/edit/{id}", [MainController::class, "Edit_Task"])->where("id", "[0-9]+"); // დავალების რედაქტირების მარშუტი

    Route::get("/get_by_id/{id}", [MainController::class, "Get_Task"])->where("id", "[0-9]+"); // დავალების რედაქტირების მარშუტი

    Route::get("/get_by_status/{id}", [MainController::class, "Get_Task_By_Status"])->where("id", "[0-9]+"); // დავალების წამოღების მარშუტი სტატუსის მიხედვით

    Route::get("/get_by_owner/{id}", [MainController::class, "Get_Task_By_Owner"])->where("id", "[0-9]+"); // დავალების წამოღების მარშუტი მფლობელის მიხედვით
});

// სტატუსების მარსუტების ჯგუფი
Route::group(["prefix" => "status", "middleware" => ["auth:api"]], function() {
    Route::delete("/delete/{id}", [MainController::class, "Delete_Status"])->where("id", "[0-9]+"); // სტატუსის წაშლის მარშუტი

    Route::post("/add", [MainController::class, "Add_Status"]); // სტატუსის დამატების მარშუტი

    Route::put("/edit/{id}", [MainController::class, "Edit_Status"])->where("id", "[0-9]+"); // დავალების რედაქტირების მარშუტი

    Route::get("/list", [MainController::class, "All_Status"]); // სტატუსების სიის მარსუტი
});
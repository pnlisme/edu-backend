<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\LectureController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CourseLevelController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ManageController;
use App\Http\Controllers\Api\GoogleController;
use App\Http\Controllers\VoucherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::group(['middleware' => 'api', 'prefix' => 'course'], function ($router) {
    Route::get('index', [CourseController::class, 'filterCourses']);
});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    //course

    //user
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('verify-email/{token}', [AuthController::class, 'verifyEmail'])->name('verify.email');
    Route::post('reset-password/{token}', [AuthController::class, 'resetPassword'])->name('reset.password');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::get('check-token-reset-password/{token}', [AuthController::class, 'checkTokenResetPassword']);

    //google
    Route::post('/get-google-sign-in-url', [AuthController::class, 'getGoogleSignInUrl']);
    Route::get('/google/call-back', [AuthController::class, 'loginGoogleCallback']);
    //Logged in
    Route::middleware(['jwt'])->group(function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
        Route::post('upload-image', [AuthController::class, 'uploadImage']);
        //admin
        Route::post('admin-update-user', [AuthController::class, 'adminUpdateUser']);
        Route::delete('delete-user/{id}', [AuthController::class, 'deleteUser']);
        Route::post('restore-user/{id}', [AuthController::class, 'restoreUser']);
        Route::post('force-delete-user/{id}', [AuthController::class, 'forceDeleteUser']);
        //wishlist
        Route::post('wishlist', [ManageController::class, 'addToWishlist']);
        Route::get('wishlist', [ManageController::class, 'getWishlist']);
        Route::post('delete-wishlist', [ManageController::class, 'deletWishlist']);

        // Routes cho admin
        Route::middleware(['role:admin'])->group(function () {
            Route::get('courses', [CourseController::class, 'getListAdmin'])->name('courses.getListAdmin');

            Route::get('categories', [CategoryController::class, 'getListAdmin'])->name('categories.getListAdmin');
            Route::get('getAdmin', [ManageController::class, 'getAdmin'])->name('users.admins');
            Route::get('getInstructor', [ManageController::class, 'getInstructor'])->name('users.instructors');
            Route::get('getStudent', [ManageController::class, 'getStudent'])->name('users.students');
            Route::put('updateUser/{id}', [ManageController::class, 'updateUserAccount']);
//            Route::post('/users', [ManageController::class, 'updateFoundationAccount']);
            Route::put('updateFoundation/{id}', [ManageController::class, 'updateFoundationAccount']);
            Route::put('contact-info/{id}', [ManageController::class, 'updateContactInfo']);
            Route::delete('delUserAdmin/{id}', [ManageController::class, 'delUser']);
            Route::get('getAdminRp', [ManageController::class, 'getAdminRpPayment']);
            Route::get('getInstructorRp', [ManageController::class, 'getInstructorRp']);
            Route::get('order-history', [ManageController::class, 'getOrderHistory']);

            Route::post('categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{id}', [CategoryController::class, 'update'])->name('categories.update');
            Route::post('categories/{id}/children', [CategoryController::class, 'addChildren'])->name('categories.addChildren');
            Route::patch('categories/{id}/status', [CategoryController::class, 'updateStatus'])->name('categories.updateStatus');
            Route::get('categories/restore/{id}', [CategoryController::class, 'restore'])->name('categories.restore');
            Route::delete('categories/{id}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::post('course-levels', [CourseLevelController::class, 'store'])->name('courselevels.store');
            Route::put('course-levels/{id}', [CourseLevelController::class, 'update'])->name('courselevels.update');
            Route::get('course-levels', [CourseLevelController::class, 'index'])->name('courselevels.index');
            Route::get('course-levels/restore/{id}', [CourseLevelController::class, 'restore'])->name('courselevels.restore');
            Route::delete('course-levels/{id}', [CourseLevelController::class, 'destroy'])->name('courselevels.destroy');

            Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
            Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
            Route::put('languages/{id}', [LanguageController::class, 'update'])->name('languages.update');
            Route::get('languages/restore/{id}', [LanguageController::class, 'restore'])->name('languages.restore');
            Route::delete('languages/{id}', [LanguageController::class, 'destroy'])->name('languages.destroy');

            // Voucher
            Route::prefix('vouchers')->group(function () {
                Route::get('/', [VoucherController::class, 'index']);
                Route::get('/deleted', [VoucherController::class, 'getDeletedVouchers']);
                Route::get('/{idOrCode}', [VoucherController::class, 'show']);
                Route::post('/create', [VoucherController::class, 'create']);
                Route::post('/delete', [VoucherController::class, 'destroy']);
                Route::post('/restore', [VoucherController::class, 'restoreVoucher']);
                Route::put('/{id}', [VoucherController::class, 'update']);
            });
        });

        // Routes cho instructor
        Route::middleware(['role:instructor'])->group(function () {
            Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
            Route::get('courses/{id}', [CourseController::class, 'showOne'])->name('courses.showOne');
            Route::put('courses/{id}', [CourseController::class, 'update'])->name('courses.update');
            Route::get('courses/restore/{id}', [CourseController::class, 'restore'])->name('courses.restore');
            Route::delete('courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');

            // // Route Chương
            Route::controller(SectionController::class)->group(function () {
                Route::get('section/{id}', 'showOne')->name('courses.showOne');
                Route::post('section', 'store')->name('section.store');
                Route::post('section/{id}', 'update')->name('section.update');
                Route::delete('section/{id}', 'delete')->name('section.delete');
                Route::post('section/sort', 'sort')->name('section.sort');
            });

            // // Lesson route
            // Route::controller(LectureController::class)->group(function () {
            //     Route::post('lesson/store', 'store')->name('lesson.store');
            //     Route::post('lesson/update', 'update')->name('lesson.update');
            //     Route::get('lesson/delete/{id}', 'delete')->name('lesson.delete');
            //     Route::post('lesson/sort', 'sort')->name('lesson.sort');
            // });
        });

        // Routes cho student
        Route::middleware(['role:student'])->group(function () {
            //chat message
            Route::get('/message/private/{receiverId}', [ChatController::class, 'index']);
            Route::get('/chat/users', [ChatController::class, 'getUsers']);
            Route::post('/messages/{receiverId}', [ChatController::class, 'store']);

            // Các route dành cho student có thể thêm tại đây

            // ...

            // Cart
            Route::prefix('cart')->group(function () {
                Route::get('/', [CartController::class, 'index']);
                Route::post('/', [CartController::class, 'store']);
                Route::delete('/all', [CartController::class, 'destroyAll']);
                Route::delete('/{course_id}', [CartController::class, 'destroy']);
                Route::post('apply-voucher', [CartController::class, 'applyVoucher']);
            });

            // Order
            Route::prefix('orders')->group(function () {
                Route::get('/', [OrderController::class, 'index']);
                Route::post('/', [OrderController::class, 'store']);
                Route::get('/{id}', [OrderController::class, 'show']);
                Route::patch('/{id}', [OrderController::class, 'cancel']);
                Route::patch('/{id}/restore', [OrderController::class, 'restore']);
            });
        });
    });
});

// Order webhook
Route::get('/orders/verify-payment', [OrderController::class, 'verifyPayment']);
Route::post('/stripe/webhook', [OrderController::class, 'handleWebhook']);

Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('categories/{id}', [CategoryController::class, 'show'])->name('categories.show');

Route::get('course-levels', [CourseLevelController::class, 'index'])->name('courselevels.index');
Route::get('course-levels/{id}', [CourseLevelController::class, 'show'])->name('courselevels.show');

Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
Route::get('languages/{id}', [LanguageController::class, 'show'])->name('languages.show');

Route::get('courses', [CourseController::class, 'search'])->name('courses.search');
Route::get('courses/{id}', [CourseController::class, 'detail'])->name('courses.detail');
Route::get('get-popular-courses', [CourseController::class, 'getPopularCourses'])->name('courses.getPopularCourses');
Route::get('get-new-courses', [CourseController::class, 'getNewCourses'])->name('courses.getNewCourses');
Route::get('get-top-rated-courses', [CourseController::class, 'getTopRatedCourses'])->name('courses.getTopRatedCourses');
Route::get('get-favourite-courses', [CourseController::class, 'getFavouriteCourses'])->name('courses.getFavouriteCourses');

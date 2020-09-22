<?php


// Public routes
Route::get('me', 'User\MeController@getMe');

// get Designs
Route::get('designs', 'DesignController@index');

Route::get('designs/{id}', 'DesignController@findDesign');

// get Users    
Route::get('users', 'User\UserController@index');


// Route group for authenticated user only
Route::group(['middleware' => ['auth:api']], function(){
    Route::post('logout', 'Auth\LoginController@logout');
    Route::put('settings/profile', 'User\SettingsController@updateProfile');
    Route::put('settings/password', 'User\SettingsController@updatePassword');

    // Upload Designs
    Route::post('designs', 'DesignController@upload');
    Route::put('designs/{id}', 'DesignController@update');
    Route::delete('designs/{id}', 'DesignController@destroy');

    // Likes and UnLikes
    Route::post('designs/{id}/like', 'DesignController@like');
    Route::get('designs/{id}/liked', 'DesignController@checkIfUserHasLiked');


    // Comments
    Route::post('designs/{id}/comments', 'CommentController@store');
    Route::put('comments/{id}', 'CommentController@update');
    Route::delete('comments/{id}', 'CommentController@destroy');
});

// Route group for guests user only
Route::group(['middleware' => ['guest:api']], function(){
    Route::post('register', 'Auth\RegisterController@register');
    Route::post('verification/verify/{user}', 'Auth\VerificationController@verify')->name('verification.verify');
    Route::post('verification/resend', 'Auth\VerificationController@resend');
    Route::post('login', 'Auth\LoginController@login');
    Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
    Route::post('password/reset', 'Auth\ResetPasswordController@reset');

    

});

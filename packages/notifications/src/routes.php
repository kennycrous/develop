<?php

Route::group(['middleware' => ['web'], 'prefix' => 'notifications', 'namespace' => 'App\Http\Controllers'], function() {
       Route::post('/support/message','NotificationsController@supportMail')->name('support.mail');  
});
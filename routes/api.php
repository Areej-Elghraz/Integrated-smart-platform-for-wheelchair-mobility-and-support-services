<?php

use App\Http\Controllers\Auth\ForgetPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResendOtpController;
use App\Http\Controllers\Auth\ResendVerificationCodeController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\Socialite\ProviderCallbackController;
use App\Http\Controllers\Auth\Socialite\ProviderRedirectController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\Profile\ChangePasswordController;
use App\Http\Controllers\Profile\UpdateDataController;
use App\Http\Controllers\ChatBot\MessageController;
use App\Http\Controllers\BuildingController;
use App\Enums\TokenAbilityEnum;
use Illuminate\Support\Facades\Route;

// authentication
Route::post('/signup', RegisterController::class)->name('auth.register');
Route::post('/login', LoginController::class)->name('auth.login');
Route::post('/refresh-token', RefreshTokenController::class)->name('auth.refresh-token')->middleware(['auth:sanctum', 'ability:' . TokenAbilityEnum::REMEMBER_TOKEN->value]);

Route::post('/verify-otp', \App\Http\Controllers\Auth\VerifyOtpController::class)->name('auth.verify-otp');
Route::post('/resend-otp', \App\Http\Controllers\Auth\ForgotPasswordController::class)->name('auth.forgot-password');
Route::post('/forgot-password', \App\Http\Controllers\Auth\ForgotPasswordController::class)->name('auth.forgot-password');
Route::post('/reset-password', \App\Http\Controllers\Auth\ResetPasswordController::class)->name('auth.reset-password');


Route::get('/medical-conditions', [\App\Http\Controllers\MedicalConditionController::class, 'index'])->name('medical-conditions.index');
Route::get('/languages', [\App\Http\Controllers\LanguageController::class, 'index'])->name('languages.index');

Route::post('/support', [\App\Http\Controllers\SupportController::class, 'store'])->name('support.store');

Route::middleware(['auth:sanctum', 'ability:' . TokenAbilityEnum::ACCESS_TOKEN->value, 'verified_user'])->group(function () {
  // authentication
  Route::post('/logout', LogoutController::class)->name('auth.logout');
  Route::get('/profile', [\App\Http\Controllers\Profile\ProfileController::class, 'show'])->name('auth.profile.show');
  Route::put('/profile/update', UpdateDataController::class)->name('auth.profile.updata_data');
  Route::put('/profile/change-password', ChangePasswordController::class)->name('auth.profile.change_password');
  Route::delete('/profile', [\App\Http\Controllers\Profile\DeleteAccountController::class, 'destroy'])->name('profile.destroy');
  Route::put('/profile/language', [\App\Http\Controllers\Profile\LanguageController::class, 'update'])->name('profile.language.update');
  Route::get('/profile/favorites', [\App\Http\Controllers\FavoriteController::class, 'index'])->name('favorites.index');

  // Categories
  Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);

  // Organization
  Route::apiResource('organizations', \App\Http\Controllers\OrganizationController::class);
  Route::post('/organizations/{organization}/reviews', [\App\Http\Controllers\ReviewController::class, 'storeOrganization'])->name('organizations.reviews.store');
  Route::post('/organizations/{organization}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggleOrganization'])->name('organizations.favorites.toggle');
  Route::post('/organizations/{organization}/visit', [\App\Http\Controllers\OrganizationController::class, 'visit'])->name('organizations.visit');

  // Places - Nested under Floor for list & create, direct ID for show/update/delete
  Route::get('/floors/{floor}/places', [\App\Http\Controllers\PlaceController::class, 'indexForFloor'])->name('floors.places.index');
  Route::post('/floors/{floor}/places', [\App\Http\Controllers\PlaceController::class, 'storeForFloor'])->name('floors.places.store');
  Route::get('/places/last-visited', [\App\Http\Controllers\PlaceController::class, 'lastVisited'])->name('places.last_visited');
  Route::apiResource('places', \App\Http\Controllers\PlaceController::class)->only(['index', 'show', 'update', 'destroy']);
  Route::post('/places/{place}/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
  Route::post('/places/{place}/favorite', [\App\Http\Controllers\FavoriteController::class, 'toggle'])->name('favorites.toggle');
  Route::post('/places/{place}/visit', [\App\Http\Controllers\PlaceController::class, 'visit'])->name('places.visit');

  // Buildings - Nested under Organization for list & create, direct ID for show/update/delete
  Route::get('/organizations/{organization}/buildings', [BuildingController::class, 'indexForOrganization'])->name('organizations.buildings.index');
  Route::post('/organizations/{organization}/buildings', [BuildingController::class, 'storeForOrganization'])->name('organizations.buildings.store');
  Route::apiResource('buildings', BuildingController::class)->only(['show', 'update', 'destroy']);

  // Floors - Nested under Building for list & create, direct ID for show/update/delete
  Route::get('/buildings/{building}/floors', [\App\Http\Controllers\FloorController::class, 'indexForBuilding'])->name('buildings.floors.index');
  Route::post('/buildings/{building}/floors', [\App\Http\Controllers\FloorController::class, 'storeForBuilding'])->name('buildings.floors.store');
  Route::apiResource('floors', \App\Http\Controllers\FloorController::class)->only(['show', 'update', 'destroy']);

  // Maps - Nested under Floor (one-to-one)
  Route::post('/floors/{floor}/map', [\App\Http\Controllers\MapController::class, 'store'])->name('floors.map.store');
  Route::get('/floors/{floor}/map', [\App\Http\Controllers\MapController::class, 'show'])->name('floors.map.show');
  Route::delete('/floors/{floor}/map', [\App\Http\Controllers\MapController::class, 'destroy'])->name('floors.map.destroy');

  Route::delete('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'destroy'])->name('reviews.destroy');

  // Locations
  Route::apiResource('countries', \App\Http\Controllers\CountryController::class);
  Route::apiResource('cities', \App\Http\Controllers\CityController::class);
  // SOS
  Route::post('/sos', [\App\Http\Controllers\SosController::class, 'trigger'])->name('sos.trigger');
  Route::post('/sos/cancel', [\App\Http\Controllers\SosController::class, 'cancel'])->name('sos.cancel');


  // Wheelchairs
  Route::post('/wheelchairs/initialize-location', [\App\Http\Controllers\WheelchairController::class, 'initializeLocation'])->name('wheelchairs.initialize_location');
  Route::get('/wheelchairs/current', [\App\Http\Controllers\WheelchairController::class, 'current'])->name('wheelchairs.current');
  Route::get('/wheelchairs/mapping-permission', [\App\Http\Controllers\WheelchairController::class, 'checkMappingPermission'])->name('wheelchairs.mapping_permission');
  Route::post('/wheelchairs/connect', [\App\Http\Controllers\WheelchairController::class, 'connect'])->name('wheelchairs.connect');
  Route::post('/wheelchairs/{wheelchairId}/disconnect', [\App\Http\Controllers\WheelchairController::class, 'disconnect'])->name('wheelchairs.disconnect');
  Route::post('/wheelchairs/{wheelchairId}/unassign', [\App\Http\Controllers\WheelchairController::class, 'unassign'])->name('wheelchairs.unassign');

  Route::get('/wheelchairs/{wheelchairId}/health', [\App\Http\Controllers\WheelchairController::class, 'showVitals'])->name('wheelchairs.show_vitals');

  Route::get('/wheelchairs/{wheelchairId}/sensor-readings', [\App\Http\Controllers\SensorReadingController::class, 'index'])->name('sensor_readings.index');
  Route::get('/wheelchairs/{wheelchairId}/events', [\App\Http\Controllers\EventController::class, 'index'])->name('wheelchairs.events.index');

  // Notifications
  Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
  Route::put('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark_all_read');
  Route::put('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark_read');


  // Community
  Route::apiResource('posts', \App\Http\Controllers\Community\PostController::class)->except(['create', 'edit']);
  Route::post('/posts/{post}/share', [\App\Http\Controllers\Community\PostController::class, 'share'])->name('posts.share');
  Route::get('/posts/{post}/likes', [\App\Http\Controllers\Community\PostController::class, 'likes'])->name('posts.likes');
  Route::get('/posts/{post}/shares', [\App\Http\Controllers\Community\PostController::class, 'shares'])->name('posts.shares');
  Route::post('/posts/{post}/like', [\App\Http\Controllers\Community\LikeController::class, 'toggleLike'])->name('posts.like');
  Route::post('/posts/{post}/hide', [\App\Http\Controllers\Community\PostController::class, 'hide'])->name('posts.hide');
  Route::get('/community/users/{user}', [\App\Http\Controllers\Community\ProfileController::class, 'show'])->name('community.profile');

  // Community - Friends
  Route::get('/community/friends', [\App\Http\Controllers\Community\FriendController::class, 'index'])->name('community.friends.index');
  Route::get('/community/friends/requests', [\App\Http\Controllers\Community\FriendController::class, 'requests'])->name('community.friends.requests');
  Route::post('/community/friends/send', [\App\Http\Controllers\Community\FriendController::class, 'send'])->name('community.friends.send');
  Route::post('/community/friends/{user}/handle', [\App\Http\Controllers\Community\FriendController::class, 'handle'])->name('community.friends.handle');
  Route::delete('/community/friends/{user}/remove', [\App\Http\Controllers\Community\FriendController::class, 'remove'])->name('community.friends.remove');

  // Connection Requests (Companion & Doctor)
  Route::post('/connections/send', [\App\Http\Controllers\ConnectionRequestController::class, 'sendRequest'])->name('connections.send');
  Route::post('/connections/{connectionRequest}/handle', [\App\Http\Controllers\ConnectionRequestController::class, 'handleRequest'])->name('connections.handle');
  Route::delete('/connections/{connectedUser}/remove', [\App\Http\Controllers\ConnectionRequestController::class, 'removeConnection'])->name('connections.remove');
  Route::get('/connections/pending', [\App\Http\Controllers\ConnectionRequestController::class, 'indexPending'])->name('connections.pending');
  Route::get('/connections/companions', [\App\Http\Controllers\ConnectionRequestController::class, 'indexConnectedCompanions'])->name('connections.companions');
  Route::get('/connections/doctor', [\App\Http\Controllers\ConnectionRequestController::class, 'getConnectedDoctor'])->name('connections.doctor');

  // Comments CRUD and engagement
  Route::post('/posts/{post}/comments', [\App\Http\Controllers\CommentController::class, 'storePost'])->name('posts.comments.store');
  Route::get('/posts/{post}/comments', [\App\Http\Controllers\CommentController::class, 'indexPost'])->name('posts.comments.index');
  Route::put('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'update'])->name('comments.update');
  Route::delete('/comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])->name('comments.destroy');
  Route::post('/comments/{comment}/like', [\App\Http\Controllers\Community\LikeController::class, 'toggleCommentLike'])->name('comments.like');
  Route::get('/comments/{comment}/likes', [\App\Http\Controllers\CommentController::class, 'likes'])->name('comments.likes');

  // Chats
  Route::get('/chats', [\App\Http\Controllers\ChatController::class, 'index'])->name('chats.index');
  Route::get('/chats/{user}', [\App\Http\Controllers\ChatController::class, 'show'])->name('chats.show');
  Route::post('/chats/{user}', [\App\Http\Controllers\ChatController::class, 'store'])->name('chats.store');
  Route::delete('/chats/{user}', [\App\Http\Controllers\ChatController::class, 'destroy'])->name('chats.destroy');

  Route::put('/messages/{message}', [\App\Http\Controllers\ChatController::class, 'updateMessage'])->name('messages.update');
  Route::delete('/messages/{message}', [\App\Http\Controllers\ChatController::class, 'deleteMessage'])->name('messages.destroy');

  Route::get('/chatbot/sessions', [\App\Http\Controllers\ChatBot\MessageController::class, 'index'])->name('chatbot.sessions.index');
  Route::post('/chatbot/sessions', [\App\Http\Controllers\ChatBot\MessageController::class, 'storeSession'])->name('chatbot.sessions.store');
  Route::get('/chatbot/sessions/{session}', [\App\Http\Controllers\ChatBot\MessageController::class, 'showSession'])->name('chatbot.sessions.show');
  Route::delete('/chatbot/sessions/{session}', [\App\Http\Controllers\ChatBot\MessageController::class, 'destroySession'])->name('chatbot.sessions.destroy');

  Route::post('/chatbot/sessions/{session}/chat', [\App\Http\Controllers\ChatBot\MessageController::class, 'chat'])->name('chatbot.chat');
  Route::post('/chatbot/messages/{message}/reaction', [\App\Http\Controllers\ChatBot\MessageController::class, 'reactToMessage'])->name('chatbot.messages.reaction');

  Route::get('/wheelchairs/{wheelchairId}/movement-status', [\App\Http\Controllers\WheelchairController::class, 'getMovementStatus'])->name('wheelchairs.movement_status_get');
  Route::get('/wheelchairs/{wheelchairId}/trips', [\App\Http\Controllers\TripController::class, 'index'])->name('wheelchairs.trips.index');
  Route::post('/wheelchairs/{wheelchairId}/trips', [\App\Http\Controllers\TripController::class, 'startTrip'])->name('trips.start');
  Route::get('/trips/{tripId}', [\App\Http\Controllers\TripController::class, 'show'])->name('trips.show');
  Route::post('/trips/{tripId}/end', [\App\Http\Controllers\TripController::class, 'endTrip'])->name('trips.end');
  Route::post('/trips/{tripId}/fail', [\App\Http\Controllers\TripController::class, 'failTrip'])->name('trips.fail');

  // Dashboards
  Route::get('/dashboard/user', [\App\Http\Controllers\DashboardController::class, 'userDashboard'])->name('dashboard.user');
  Route::get('/dashboard/companion', [\App\Http\Controllers\DashboardController::class, 'companionDashboard'])->name('dashboard.companion');
  Route::get('/dashboard/doctor', [\App\Http\Controllers\DashboardController::class, 'doctorDashboard'])->name('dashboard.doctor');
  Route::get('/dashboard/org-admin', [\App\Http\Controllers\DashboardController::class, 'orgAdminDashboard'])->name('dashboard.org-admin');

  // Live Locations
  Route::post('/location/user', [\App\Http\Controllers\LocationController::class, 'userLocation'])->name('location.user');
  Route::get('/location/companion/user', [\App\Http\Controllers\LocationController::class, 'companionLocation'])->name('location.companion.user');
});

// IoT Routes (Hardware/Wheelchair)
Route::middleware(['verify_wheelchair_api_key'])->group(function () {
  Route::post('/iot/movement/update', [\App\Http\Controllers\WheelchairController::class, 'updateMovementStatus'])->name('iot.wheelchair.movement.update');
  Route::post('/iot/movement/events', [\App\Http\Controllers\EventController::class, 'storeTripEvent'])->name('iot.trip.events.store');
  Route::post('/iot/trip/end', [\App\Http\Controllers\TripController::class, 'endTripIot'])->name('iot.trip.end');
  Route::post('/iot/trip/fail', [\App\Http\Controllers\TripController::class, 'failTripIot'])->name('iot.trip.fail');
  Route::post('/iot/health/update', [\App\Http\Controllers\WheelchairController::class, 'updateCurrentVitalState'])->name('iot.wheelchair.health.update');
  Route::post('/iot/floors/{floor}/map', [\App\Http\Controllers\IoT\MapController::class, 'store'])->name('iot.maps.store');
  Route::post('/iot/sensor-readings', [\App\Http\Controllers\SensorReadingController::class, 'store'])->name('iot.sensor_readings.store');
});
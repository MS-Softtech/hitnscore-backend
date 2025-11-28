<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Dashboard\LiveMatchController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\U18\U18ProfileController;
use App\Http\Controllers\Api\U18\U18AdminController;
use App\Http\Controllers\Api\U18\U18MatchController;
use App\Http\Controllers\Api\Dashboard\LiveMatchListController;
use App\Http\Controllers\Api\Corporate\CorporateAdsController;
use App\Http\Controllers\Api\Corporate\CorporateEnrollController;
use App\Http\Controllers\Api\Auction\AuctionReadController;
use App\Http\Controllers\Api\Auction\AuctionBidController;
use App\Http\Controllers\Api\Turf\TurfController;
use App\Http\Controllers\Api\Addon\AddonController;
use App\Http\Controllers\Api\Referral\ReferralController;
use App\Http\Controllers\Auth\SocialAuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);  // public
    Route::post('/login',    [AuthController::class, 'login']);     // public
    Route::get('/me',        [AuthController::class, 'me'])->middleware('jwt'); // protected

    Route::post('/u18/apply',  [U18ProfileController::class, 'apply'])->middleware('jwt');   // multipart/form-data (file or media_asset_id + dob)
    Route::post('/corporate/enroll', [CorporateEnrollController::class, 'enroll'])->middleware('jwt');
    Route::post('/referral/new-code', [ReferralController::class, 'newCode'])->middleware('jwt');
    Route::get('/dashboard/referral/summary', [ReferralController::class, 'summary'])->middleware('jwt');
    Route::get('/u18/status',  [U18ProfileController::class, 'status'])->middleware('jwt');  // returns {eligible_by_dob, approval_status, ...}
    Route::get('/dashboard/corporate/ads', [CorporateAdsController::class, 'index'])->middleware('jwt');
    Route::get('/auctions/live', [AuctionReadController::class, 'livePlayers'])->middleware('jwt');
    Route::get('/auctions/{auctionId}/players', [AuctionReadController::class, 'players'])->middleware('jwt');
    Route::get('/auctions/player/{playerId}', [AuctionReadController::class, 'player'])->middleware('jwt');// one player card
    Route::get('/dashboard/live-matches', [LiveMatchController::class, 'index'])->middleware('jwt');
    Route::get('/dashboard/live-match-list', [LiveMatchListController::class, 'index'])->middleware('jwt');
    Route::get('/dashboard/u18-matches', [U18MatchController::class, 'list'])->middleware('jwt');
    Route::post('/auctions/bid', [AuctionBidController::class, 'placeBid'])->middleware('jwt');
    Route::get('/dashboard/turfs', [TurfController::class, 'index'])->middleware('jwt');
    Route::get('/dashboard/addons', [AddonController::class, 'index'])->middleware('jwt');
    Route::post('/social-login', [SocialAuthController::class, 'login']);
    Route::post('/auth/social/facebook', [SocialAuthController::class, 'facebook']);
});
Route::middleware('jwt')  // adapt your gate/middleware
    ->prefix('/admin/u18')
    ->group(function () {
        Route::get ('/pending',          [U18AdminController::class, 'pending']);
        Route::post('/approve/{id}',     [U18AdminController::class, 'approve']); // {id}=approvals.id
        Route::post('/reject/{id}',      [U18AdminController::class, 'reject']);  // body: notes
    });
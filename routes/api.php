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
use App\Http\Controllers\Auth\SocialAuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);  // public
    Route::post('/login',    [AuthController::class, 'login']);     // public
    Route::post('/u18/apply',  [U18ProfileController::class, 'apply']);   // multipart/form-data (file or media_asset_id + dob)
    Route::post('/corporate/enroll', [CorporateEnrollController::class, 'enroll']);
    Route::post('/referral/new-code', [ReferralController::class, 'newCode']);
    Route::get('/dashboard/referral/summary', [ReferralController::class, 'summary']);
    Route::get('/u18/status',  [U18ProfileController::class, 'status']);  // returns {eligible_by_dob, approval_status, ...}
    Route::get('/dashboard/corporate/ads', [CorporateAdsController::class, 'index']);
    Route::get('/auctions/live', [AuctionReadController::class, 'livePlayers']); // list of live players
    Route::get('/auctions/{auctionId}/players', [AuctionReadController::class, 'players']); // by auction
    Route::get('/auctions/player/{playerId}', [AuctionReadController::class, 'player']); // one player card
    Route::get('/me',        [AuthController::class, 'me'])->middleware('jwt'); // protected
    Route::get('/dashboard/live-matches', [LiveMatchController::class, 'index'])->middleware('jwt');
    Route::get('/dashboard/live-match-list', [LiveMatchListController::class, 'index'])->middleware('jwt');
    Route::get('/dashboard/u18-matches', [U18MatchController::class, 'list'])->middleware('jwt');
    Route::post('/auctions/bid', [AuctionBidController::class, 'placeBid']);
    Route::get('/dashboard/turfs', [TurfController::class, 'index']);
    Route::get('/dashboard/addons', [AddonController::class, 'index']);
    Route::post('/social-login', [SocialAuthController::class, 'login']);
});
Route::middleware(['auth:users', 'can:admin'])  // adapt your gate/middleware
    ->prefix('/admin/u18')
    ->group(function () {
        Route::get ('/pending',          [U18AdminController::class, 'pending']);
        Route::post('/approve/{id}',     [U18AdminController::class, 'approve']); // {id}=approvals.id
        Route::post('/reject/{id}',      [U18AdminController::class, 'reject']);  // body: notes
    });
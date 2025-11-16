<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Connector\Dashboard\LiveMatchListInterface;
use App\Http\Repository\Dashboard\LiveMatchListRepository;
use App\Http\Connector\U18\U18Interface;
use App\Http\Repository\U18\U18Repository;
use App\Http\Connector\Corporate\CorporateInterface;
use App\Http\Repository\Corporate\CorporateRepository;
use App\Http\Connector\Referral\ReferralInterface;
use App\Http\Repository\Referral\ReferralRepository;
use App\Http\Connector\Auction\AuctionInterface;
use App\Http\Repository\Auction\AuctionRepository;
use App\Http\Connector\Turf\TurfInterface;
use App\Http\Repository\Turf\TurfRepository;
use App\Http\Connector\Addon\AddonInterface;
use App\Http\Repository\Addon\AddonRepository;
/**
 * Class RepositoryServiceProvider
 *
 * Binds dashboard interfaces to concrete repositories.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(LiveMatchListInterface::class, LiveMatchListRepository::class);
        $this->app->bind(U18Interface::class, U18Repository::class);
        $this->app->bind(ReferralInterface::class, ReferralRepository::class);
        $this->app->bind(CorporateInterface::class, CorporateRepository::class);
        $this->app->bind(ReferralInterface::class, ReferralRepository::class);
        $this->app->bind(AuctionInterface::class, AuctionRepository::class);
        $this->app->bind(TurfInterface::class, TurfRepository::class);
        $this->app->bind(AddonInterface::class, AddonRepository::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ApplicationRepository;
use App\Repositories\Contracts\ApplicationRepositoryContract;
use App\Repositories\Contracts\HubnetClientRepositoryContract;
use App\Repositories\Contracts\HubnetOAuthRepositoryContract;
use App\Repositories\Contracts\HubnetUserRepositoryContract;
use App\Repositories\Contracts\IntegrationLogRepositoryContract;
use App\Repositories\Contracts\MemberRepositoryContract;
use App\Repositories\Contracts\SsoTokenRepositoryContract;
use App\Repositories\HubnetClientRepository;
use App\Repositories\HubnetOAuthRepository;
use App\Repositories\HubnetUserRepository;
use App\Repositories\IntegrationLogRepository;
use App\Repositories\MemberRepository;
use App\Repositories\SsoTokenRepository;
use App\Services\Contracts\IntegrationLoggerContract;
use App\Services\Contracts\PldNotificationClientContract;
use App\Services\Contracts\TrackingPayloadServiceContract;
use App\Services\IntegrationLogger;
use App\Services\PldNotificationClient;
use App\Services\TrackingPayloadService;
use Illuminate\Support\ServiceProvider;

/**
 * Mengikat seluruh kontrak ke implementasinya.
 *
 * Terpisah dari AppServiceProvider supaya daftar ini terbaca sebagai satu berkas
 * "inilah bentuk domainnya" — dan supaya menambah kontrak baru tidak berarti
 * menyunting berkas yang juga mengurusi hal lain.
 */
final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const BINDINGS = [
        MemberRepositoryContract::class => MemberRepository::class,
        ApplicationRepositoryContract::class => ApplicationRepository::class,
        IntegrationLogRepositoryContract::class => IntegrationLogRepository::class,
        SsoTokenRepositoryContract::class => SsoTokenRepository::class,

        HubnetUserRepositoryContract::class => HubnetUserRepository::class,
        HubnetOAuthRepositoryContract::class => HubnetOAuthRepository::class,
        HubnetClientRepositoryContract::class => HubnetClientRepository::class,

        TrackingPayloadServiceContract::class => TrackingPayloadService::class,
        IntegrationLoggerContract::class => IntegrationLogger::class,
        PldNotificationClientContract::class => PldNotificationClient::class,
    ];

    public function register(): void
    {
        foreach (self::BINDINGS as $contract => $implementation) {
            $this->app->bind($contract, $implementation);
        }
    }
}

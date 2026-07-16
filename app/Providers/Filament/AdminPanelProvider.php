<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminCustomDashboard;
use App\Filament\Resources\BroadsheetResource\Pages\ViewBroadsheet;
use App\Models\Setting;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{



    public function panel(Panel $panel): Panel
    {
        $schoolDetails = getSchoolDetails();

        return $panel
            ->default()
            ->id('admin')
            ->darkMode(false)
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Yellow,
                'purple' => Color::Purple,
            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName($schoolDetails['school_name'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                //Pages\Dashboard::class,
                AdminCustomDashboard::class,
                // ViewBroadsheet::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('Cummulative Reports')
                    ->icon('heroicon-o-document-text')
                    ->group('Examinations')
                    ->url(fn() => route('cumulative-reports.select'))  
                    ->sort(1)
                    ->isActiveWhen(fn(): bool => request()->routeIs('cumulative-reports.select')),
                NavigationItem::make('Subject Rankings')
                    ->icon('heroicon-o-chart-bar')
                    ->group('Examinations')
                    ->url(fn() => route('subject-ranking.select'))  
                    ->sort(2)
                    ->isActiveWhen(fn(): bool => request()->routeIs('subject-ranking.select')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

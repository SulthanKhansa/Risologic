<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('Risologic')
            ->login()
            ->colors([
                'primary' => Color::Orange, // Action Color 10%
                'secondary' => Color::Slate, // Secondary (Navy-like)
                'gray' => Color::Zinc, // Neutral Gray / White
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>
                    /* 30% Secondary Color: Navy Header & Sidebar */
                    .fi-sidebar { background-color: #0f172a !important; border-right: none !important; }
                    .fi-sidebar-item-label, .fi-sidebar-group-label { color: #cbd5e1 !important; }
                    .fi-sidebar-item-icon { color: #94a3b8 !important; }
                    
                    /* Sidebar Active State (Orange/Emas highlight on Navy) */
                    .fi-sidebar-item-active { background-color: #1e293b !important; }
                    .fi-sidebar-item-active .fi-sidebar-item-label { color: #f59e0b !important; font-weight: bold; }
                    .fi-sidebar-item-active .fi-sidebar-item-icon { color: #f59e0b !important; }
                    
                    /* Topbar Navy */
                    .fi-topbar { background-color: #0f172a !important; border-bottom: none !important; }
                    .fi-topbar * { color: #e2e8f0 !important; }

                    /* 60% Primary Color Background */
                    .fi-main { background-color: #f8fafc !important; } /* Netral Putih/Abu-Abu Terang */
                </style>',
            )
            ->darkMode(false)
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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

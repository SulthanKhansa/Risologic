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
use Filament\Support\Enums\MaxWidth;
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
            ->brandLogo(fn () => view('filament.branding.logo'))
            ->favicon(asset('favicon.ico'))
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::Amber, // Golden Orange for Action
                'secondary' => Color::Slate, // Professional Navy
                'gray' => Color::Zinc, // Clean White/Gray
            ])
            ->font('Outfit', url: 'https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<style>
                    /* Premium Look & Rule 60-30-10 */
                    :root {
                        --sidebar-width: 280px;
                    }
                    
                    /* 30% Secondary Color: Deep Professional Navy */
                    .fi-sidebar { 
                        background-color: #0f172a !important; 
                        box-shadow: 4px 0 24px rgba(0,0,0,0.1);
                        border-right: none !important;
                    }
                    .fi-sidebar-header {
                        padding: 1.5rem !important;
                        border-bottom: 1px solid rgba(255,255,255,0.05);
                    }
                    .fi-sidebar-item-label, .fi-sidebar-group-label { 
                        color: #94a3b8 !important; 
                        letter-spacing: 0.025em;
                    }
                    .fi-sidebar-item-icon { 
                        color: #64748b !important; 
                        transition: color 0.2s;
                    }
                    
                    /* Active Menu Item: Glowing Amber/Gold */
                    .fi-sidebar-item-active { 
                        background: linear-gradient(to right, rgba(245, 158, 11, 0.1), transparent) !important;
                        border-right: 3px solid #f59e0b;
                    }
                    .fi-sidebar-item-active .fi-sidebar-item-label { 
                        color: #f59e0b !important; 
                        font-weight: 700;
                    }
                    .fi-sidebar-item-active .fi-sidebar-item-icon { 
                        color: #f59e0b !important; 
                    }
                    
                    /* Topbar Premium Styling */
                    .fi-topbar { 
                        background-color: rgba(255,255,255,0.8) !important; 
                        backdrop-filter: blur(12px);
                        border-bottom: 1px solid #f1f5f9 !important;
                    }
                    .fi-topbar-header * { color: #1e293b !important; }

                    /* 60% Primary Color: Neutral & Clean Canvas */
                    .fi-main { 
                        background-color: #f8fafc !important; 
                        min-height: 100vh;
                    }

                    /* Rounded UI Elements for Modern Look */
                    .fi-section, .fi-card, .fi-modal-window { 
                        border-radius: 1rem !important;
                        border: 1px solid #f1f5f9 !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1) !important;
                    }
                    
                    /* Smooth Transitions */
                    * { transition: all 0.2s ease-in-out; }
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

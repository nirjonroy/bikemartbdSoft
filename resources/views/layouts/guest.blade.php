<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $businessSetting->display_name }} | Staff Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @include('layouts.partials.vite-assets')

        <style>
            .guest-auth-shell {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1rem;
                background: #f1f5f9;
            }

            .guest-auth-container {
                width: 100%;
                max-width: 30rem;
            }

            .guest-brand-block {
                text-align: center;
            }

            .guest-brand-link {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 1rem;
                text-decoration: none;
            }

            .guest-brand-logo-frame {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 96px;
                height: 96px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                background: #fff;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            .guest-brand-logo-frame img {
                display: block;
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .guest-brand-caption {
                display: block;
                font-size: 0.75rem;
                font-weight: 600;
                letter-spacing: 0.35em;
                text-transform: uppercase;
                color: #64748b;
            }

            .guest-brand-name {
                display: block;
                margin-top: 0.5rem;
                font-size: 2rem;
                font-weight: 600;
                color: #0f172a;
            }

            .guest-auth-panel {
                width: 100%;
                margin-top: 1.5rem;
                padding: 1.5rem 1.75rem;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                background: #fff;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            }

            @media (max-width: 640px) {
                .guest-auth-shell {
                    padding: 1.25rem 0.75rem;
                }

                .guest-auth-panel {
                    padding: 1.25rem;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="guest-auth-shell">
            <div class="guest-auth-container">
                <div class="guest-brand-block">
                    <a href="{{ url('/') }}" class="guest-brand-link">
                        <span class="guest-brand-logo-frame">
                            <img src="{{ $businessSetting->logo_url }}" alt="{{ $businessSetting->display_name }} logo">
                        </span>
                        <span>
                            <span class="guest-brand-caption">Store Access</span>
                            <span class="guest-brand-name">{{ $businessSetting->display_name }}</span>
                        </span>
                    </a>
                </div>

                <div class="guest-auth-panel">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('POS Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden rounded-lg bg-slate-900 p-6 text-white shadow-sm lg:col-span-2">
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-300">Ready for checkout</p>
                    <h3 class="mt-3 text-3xl font-semibold">Welcome back, {{ auth()->user()->name }}.</h3>
                    <p class="mt-3 max-w-2xl text-sm text-slate-200">
                        Your authentication flow is active. The next POS modules can be added behind this dashboard.
                    </p>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold text-slate-900">System status</h3>
                        <p class="mt-3 text-sm text-slate-600">Breeze authentication is installed and your account session is working.</p>
                        <dl class="mt-6 space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Auth guard</dt>
                                <dd class="font-medium text-slate-900">web</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-slate-500">Current user</dt>
                                <dd class="font-medium text-slate-900">{{ auth()->user()->email }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

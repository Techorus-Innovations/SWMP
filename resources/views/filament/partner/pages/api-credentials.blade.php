<x-filament-panels::page>
    @if ($plainSecret)
        <div class="p-4 mb-6 rounded-xl border border-warning-400 bg-warning-50 dark:bg-warning-950/30 text-warning-800 dark:text-warning-200">
            <div class="flex items-start gap-3">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-6 h-6 text-warning-600 dark:text-warning-400 shrink-0" />
                <div class="flex-1">
                    <h3 class="font-bold text-sm">Save your Client Secret immediately!</h3>
                    <p class="text-xs mt-1 text-warning-700 dark:text-warning-300">
                        This is the only time your <strong>Client Secret</strong> will be displayed in plaintext. It is stored securely as a cryptographic hash.
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <code class="px-3 py-1.5 bg-white dark:bg-gray-900 border border-warning-300 dark:border-warning-700 rounded-lg text-sm font-mono text-gray-900 dark:text-gray-100 select-all">
                            {{ $plainSecret }}
                        </code>
                        <button type="button" 
                                onclick="navigator.clipboard.writeText('{{ $plainSecret }}'); alert('Client Secret copied to clipboard!');"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-warning-600 hover:bg-warning-700 text-white shadow-sm transition">
                            Copy Secret
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($this->client)
        <div class="space-y-6">
            <div class="p-6 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Integration Credentials</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Use these credentials to request Bearer tokens from the SWMP API.</p>
                    </div>
                    <div>
                        @if ($this->client->is_active)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                Deactivated
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Client ID
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ $this->client->client_id }}" 
                                   class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-mono text-gray-900 dark:text-gray-100 select-all focus:outline-none" />
                            <button type="button" 
                                    onclick="navigator.clipboard.writeText('{{ $this->client->client_id }}'); alert('Client ID copied!');"
                                    class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 transition"
                                    title="Copy Client ID">
                                <x-filament::icon icon="heroicon-o-clipboard-document" class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Client Secret
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="password" readonly value="••••••••••••••••••••••••••••••••" 
                                   class="w-full px-3 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm font-mono text-gray-500 select-none focus:outline-none" />
                            <span class="text-xs text-gray-400 dark:text-gray-500 italic">Hashed in database</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Created:</span> 
                        {{ $this->client->created_at?->format('Y-m-d H:i') ?? 'N/A' }}
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Token Lifetime:</span> 
                        24 Hours
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">Last Used:</span> 
                        {{ $this->client->last_used_at?->diffForHumans() ?? 'Never' }}
                    </div>
                </div>
            </div>

            <div class="p-6 bg-gray-900 text-gray-100 rounded-2xl border border-gray-800 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-code-bracket" class="w-4 h-4 text-primary-400" />
                        Obtaining an Access Token
                    </h3>
                    <a href="{{ route('api.documentation') }}" target="_blank" class="text-xs text-primary-400 hover:text-primary-300 underline font-medium">
                        Open Swagger Documentation &rarr;
                    </a>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Make a <code class="text-primary-300">POST</code> request to exchange your Client ID and Client Secret for a Bearer token:
                </p>
                <pre class="p-3 bg-gray-950 rounded-xl text-xs font-mono overflow-x-auto text-emerald-400"><code>curl -X POST {{ url('/api/v1/auth/token') }} \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "{{ $this->client->client_id }}",
    "client_secret": "YOUR_CLIENT_SECRET"
  }'</code></pre>
            </div>
        </div>
    @else
        <div class="p-12 text-center bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm space-y-4 max-w-xl mx-auto">
            <div class="w-14 h-14 mx-auto rounded-full bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                <x-filament::icon icon="heroicon-o-key" class="w-7 h-7" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">No API Credentials Found</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                    Generate an API client credential to allow external software or webhooks to integrate with your SWMP account.
                </p>
            </div>
            <div class="pt-2">
                <button type="button" 
                        wire:click="generateCredentials"
                        class="px-4 py-2 text-xs font-semibold rounded-xl bg-primary-600 hover:bg-primary-700 text-white shadow-sm transition">
                    Generate API Credentials
                </button>
            </div>
        </div>
    @endif
</x-filament-panels::page>

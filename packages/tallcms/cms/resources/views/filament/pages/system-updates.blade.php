<x-filament-panels::page>
    {{-- Current Version Info --}}
    <x-filament::section>
        <x-slot name="heading">{{ __('tallcms::ui.tallcms_core') }}</x-slot>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('tallcms::ui.current_version_label') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">v{{ $currentVersion }}</p>
                </div>

                @if($updateAvailable && $latestRelease)
                    <div class="text-right">
                        <x-filament::badge color="success" size="lg">
                            {{ __('tallcms::ui.update_available_version', ['version' => $latestRelease['version']]) }}
                        </x-filament::badge>
                        @if($latestRelease['published_at'])
                            <p class="text-xs text-gray-500 mt-1">
                                {{ __('tallcms::ui.released_on', ['date' => \Carbon\Carbon::parse($latestRelease['published_at'])->translatedFormat('j M Y')]) }}
                            </p>
                        @endif
                    </div>
                @else
                    <x-filament::badge color="gray" size="lg">
                        {{ __('tallcms::ui.up_to_date') }}
                    </x-filament::badge>
                @endif
            </div>

            @if($updateAvailable && $latestRelease && $latestRelease['body'])
                <div class="pt-4 border-t border-gray-200 dark:border-white/10">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">{{ __('tallcms::ui.whats_new') }}</h4>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-400">
                        {!! \Illuminate\Support\Str::markdown($latestRelease['body']) !!}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    @if($updateAvailable)
        {{-- Preflight Checks --}}
        <x-filament::section>
            <x-slot name="heading">Pre-Update Checks</x-slot>

            <div class="space-y-3">
                @foreach($preflightChecks as $check => $result)
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-white/5 last:border-0">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ match($check) {
                                'sodium' => 'Signature Verification',
                                'public_key' => 'Public Key Configuration',
                                'disk_space' => 'Disk Space',
                                'lock' => 'Update Lock',
                                'exec' => 'Background Execution',
                                'queue' => 'Queue System',
                                default => ucfirst(str_replace('_', ' ', $check))
                            } }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if($result['status'] === 'pass')
                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                            @elseif($result['status'] === 'warn')
                                <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500" />
                            @else
                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500" />
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate" title="{{ $result['message'] }}">
                                {{ $result['message'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Clear stale lock button --}}
            @if(($preflightChecks['lock']['status'] ?? '') === 'fail')
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-white/10">
                    <x-filament::button
                        wire:click="clearStaleLock"
                        color="warning"
                        size="sm"
                    >
                        Clear Stale Lock
                    </x-filament::button>
                </div>
            @endif
        </x-filament::section>

        {{-- Database Backup Info --}}
        <x-filament::section>
            <x-slot name="heading">Database Backup</x-slot>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Driver</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ ucfirst($dbBackupCapability['driver'] ?? 'unknown') }}</span>
                </div>

                @if($dbBackupCapability['size_mb'] !== null)
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Database Size</span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $dbBackupCapability['size_mb'] }} MB</span>
                    </div>
                @endif

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Backup Method</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $dbBackupCapability['method'] ?? 'Not available' }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Auto-backup</span>
                    @if($dbBackupCapability['capable'])
                        <x-filament::badge color="success" size="sm">Available</x-filament::badge>
                    @else
                        <x-filament::badge color="warning" size="sm">Not Available</x-filament::badge>
                    @endif
                </div>

                @php
                    $dbCapable = $dbBackupCapability['capable'] ?? false;
                    $dbWarning = $dbBackupCapability['warning'] ?? null;
                @endphp

                @if(!$dbCapable)
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <div class="flex items-start gap-3">
                            <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" />
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-amber-700 dark:text-amber-300">
                                    Automatic database backup is not available
                                </p>
                                @if($dbWarning)
                                    <p class="text-sm text-amber-600 dark:text-amber-400">
                                        {{ $dbWarning }}
                                    </p>
                                @endif

                                @if(config('tallcms.updates.require_db_backup', true))
                                    <div class="pt-2 border-t border-amber-200 dark:border-amber-700">
                                        <p class="text-sm text-amber-700 dark:text-amber-300 mb-2">
                                            <strong>Before updating:</strong> Please create a manual backup of your database using your hosting control panel (cPanel, Plesk) or contact your hosting provider.
                                        </p>

                                        <label class="flex items-start gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                wire:model.live="skipDbBackup"
                                                class="mt-1 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                                            >
                                            <span class="text-sm text-amber-700 dark:text-amber-300">
                                                I understand that my database will <strong>not</strong> be backed up automatically. I have made a manual backup or accept the risk.
                                            </span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @elseif($dbWarning)
                    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <p class="text-sm text-amber-700 dark:text-amber-300">
                            {{ $dbWarning }}
                        </p>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Update Action --}}
        <div class="flex justify-end gap-4">
            @php
                $canUpdate = collect($preflightChecks)->every(fn($c) => $c['status'] !== 'fail');
                $requireDbBackup = config('tallcms.updates.require_db_backup', true);
                $dbCapable = $dbBackupCapability['capable'] ?? false;
                $dbBackupBlocking = $requireDbBackup && !$dbCapable && !$skipDbBackup;
            @endphp

            @if($dbBackupBlocking)
                <p class="text-sm text-amber-600 dark:text-amber-400 self-center">
                    Please acknowledge the database backup warning above to continue.
                </p>
            @endif

            <x-filament::button
                wire:click="startUpdate"
                size="lg"
                :disabled="!$canUpdate || $dbBackupBlocking"
            >
                <x-heroicon-s-arrow-down-tray class="w-5 h-5 mr-2" />
                Update to v{{ $latestRelease['version'] }}
            </x-filament::button>
        </div>
    @else
        {{-- Up to date message --}}
        <div class="flex flex-col items-center justify-center p-12 text-center">
            <div class="rounded-full bg-green-100 dark:bg-green-900/20 p-3 mb-4">
                <x-heroicon-o-check-circle class="w-8 h-8 text-green-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">{{ __('tallcms::ui.youre_up_to_date') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ __('tallcms::ui.latest_version_message', ['version' => 'v'.$currentVersion]) }}
            </p>
        </div>
    @endif
</x-filament-panels::page>

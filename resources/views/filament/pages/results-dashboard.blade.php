<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section
            heading="Edycja wyników"
            description="Agregaty są liczone z zaakceptowanych kart głosowania, zgodnie z regułami legacy."
        >
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Edycja</span>
                    <select
                        wire:model.live="budgetEditionId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        @foreach ($editionOptions as $editionId => $label)
                            <option value="{{ $editionId }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                @if ($budgetEditionId)
                    @can('export-reports')
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button
                            tag="a"
                            :href="route('admin.reports.vote-cards', $budgetEditionId)"
                            color="gray"
                        >
                            Karty CSV
                        </x-filament::button>
                        <x-filament::button
                            tag="a"
                            :href="route('admin.reports.category-comparison', $budgetEditionId)"
                            color="gray"
                        >
                            Kategorie CSV
                        </x-filament::button>
                    </div>
                    @endcan
                @endif
            </div>
        </x-filament::section>

        @if (empty($summary))
            <x-filament::section heading="Brak danych">
                <p class="text-sm text-gray-600 dark:text-gray-300">Brak skonfigurowanej edycji budżetu obywatelskiego.</p>
            </x-filament::section>
        @else
            <div class="grid gap-4 md:grid-cols-4">
                <x-filament::section compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Punkty</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($summary['total_points'], 0, ',', ' ') }}</p>
                </x-filament::section>
                <x-filament::section compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Projekty z głosami</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $summary['projects_count'] }}</p>
                </x-filament::section>
                <x-filament::section compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Remisy</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ count($summary['tie_groups']) }}</p>
                </x-filament::section>
                <x-filament::section compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Publikacja publiczna</p>
                    <p class="mt-1 text-lg font-semibold {{ $summary['published'] ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">
                        {{ $summary['published'] ? 'aktywna' : 'nieaktywna' }}
                    </p>
                </x-filament::section>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(360px,1fr)]">
                <x-filament::section heading="Ranking projektów">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                            <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4">Nr los.</th>
                                <th class="py-2 pr-4">Projekt</th>
                                <th class="py-2 pr-4">Obszar</th>
                                <th class="py-2 text-right">Punkty</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                            @forelse ($summary['top_projects'] as $project)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $project['number_drawn'] ?? '-' }}</td>
                                    <td class="py-2 pr-4 font-medium text-gray-950 dark:text-white">{{ $project['title'] }}</td>
                                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $project['area'] }}</td>
                                    <td class="py-2 text-right font-semibold text-gray-950 dark:text-white">{{ $project['points'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-gray-600 dark:text-gray-300">Brak zaakceptowanych głosów.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

                <div class="space-y-6">
                    <x-filament::section heading="Statusy kart">
                        <dl class="space-y-3">
                            @foreach ($summary['status_counts'] as $status => $count)
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-sm text-gray-600 dark:text-gray-300">{{ $status }}</dt>
                                    <dd class="text-sm font-semibold text-gray-950 dark:text-white">{{ $count }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </x-filament::section>

                    @can('export-reports')
                        <x-filament::section heading="Eksporty administracyjne">
                            <div class="flex flex-col gap-2">
                                <x-filament::button tag="a" :href="route('admin.reports.submitted-projects')" color="gray">
                                    Projekty złożone CSV
                                </x-filament::button>
                                <x-filament::button tag="a" :href="route('admin.reports.verification-manifest')" color="gray">
                                    Manifest weryfikacji CSV
                                </x-filament::button>
                                <x-filament::button tag="a" :href="route('admin.reports.project-history')" color="gray">
                                    Historia projektów CSV
                                </x-filament::button>
                            </div>
                        </x-filament::section>
                    @endcan
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-filament::section heading="Punkty po obszarach">
                    <div class="space-y-3">
                        @forelse ($summary['area_totals'] as $area)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-2 last:border-b-0 dark:border-white/10">
                                <div>
                                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $area['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $area['is_local'] ? 'lokalny' : 'ogólnomiejski' }}</p>
                                </div>
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $area['points'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600 dark:text-gray-300">Brak punktów po obszarach.</p>
                        @endforelse
                    </div>
                </x-filament::section>

                <x-filament::section heading="Punkty po kategoriach">
                    <div class="space-y-3">
                        @forelse ($summary['category_totals'] as $category)
                            <div class="flex items-center justify-between gap-4 border-b border-gray-100 pb-2 last:border-b-0 dark:border-white/10">
                                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $category['name'] }}</p>
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $category['points'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-600 dark:text-gray-300">Brak punktów po kategoriach.</p>
                        @endforelse
                    </div>
                </x-filament::section>
            </div>

            <x-filament::section heading="Remisy i decyzje manualne">
                @forelse ($summary['tie_groups'] as $group)
                    <div wire:key="tie-group-{{ $group['form_key'] }}" class="mb-4 rounded-lg border border-warning-200 bg-warning-50 p-4 last:mb-0 dark:border-warning-500/30 dark:bg-warning-500/10">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-warning-800 dark:text-warning-200">{{ $group['points'] }} pkt</p>
                            @if (! $group['requires_manual_decision'])
                                <span class="rounded-md bg-success-100 px-2 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/20 dark:text-success-200">
                                    decyzja zapisana
                                </span>
                            @endif
                        </div>
                        <ul class="mt-2 space-y-1 text-sm text-warning-900 dark:text-warning-100">
                            @foreach ($group['projects'] as $project)
                                <li>#{{ $project['project_id'] }} · {{ $project['number_drawn'] ?? '-' }} · {{ $project['title'] }}</li>
                            @endforeach
                        </ul>

                        @if ($group['decision'])
                            @php($winner = collect($group['projects'])->firstWhere('project_id', $group['decision']['winner_project_id']))
                            <div class="mt-3 rounded-md border border-success-200 bg-white/70 p-3 text-sm text-success-800 dark:border-success-500/30 dark:bg-gray-950/40 dark:text-success-200">
                                <p class="font-semibold">
                                    Zwycięzca remisu: {{ $winner['title'] ?? 'Projekt #'.$group['decision']['winner_project_id'] }}
                                </p>
                                @if ($group['decision']['notes'])
                                    <p class="mt-1 text-xs">{{ $group['decision']['notes'] }}</p>
                                @endif
                            </div>
                        @elseif ($this->canResolveResultTies())
                            <form wire:submit.prevent="resolveTieDecision('{{ $group['form_key'] }}')" class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] md:items-end">
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-warning-800 dark:text-warning-200">Zwycięski projekt</span>
                                    <select
                                        wire:model="tieDecisionWinners.{{ $group['form_key'] }}"
                                        class="w-full rounded-lg border-warning-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-warning-500/40 dark:bg-gray-950"
                                    >
                                        <option value="">Wybierz projekt</option>
                                        @foreach ($group['projects'] as $project)
                                            <option value="{{ $project['project_id'] }}">
                                                {{ $project['number_drawn'] ?? '-' }} · {{ $project['title'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="mb-1 block text-xs font-semibold uppercase tracking-wide text-warning-800 dark:text-warning-200">Notatka</span>
                                    <input
                                        type="text"
                                        wire:model="tieDecisionNotes.{{ $group['form_key'] }}"
                                        class="w-full rounded-lg border-warning-200 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-warning-500/40 dark:bg-gray-950"
                                    >
                                </label>
                                <x-filament::button type="submit">
                                    Zapisz decyzję
                                </x-filament::button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">Brak remisów w punktowanych projektach.</p>
                @endforelse
            </x-filament::section>

            <x-filament::section heading="Różnice kategorii głównej i wielu kategorii">
                @forelse ($summary['category_differences'] as $category)
                    <div class="mb-3 flex items-center justify-between gap-4 border-b border-gray-100 pb-3 last:mb-0 last:border-b-0 dark:border-white/10">
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $category['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                główna: {{ $category['primary_points'] }} · wiele kategorii: {{ $category['multi_category_points'] }}
                            </p>
                        </div>
                        <p class="text-sm font-semibold {{ $category['difference'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            {{ $category['difference'] > 0 ? '+' : '' }}{{ $category['difference'] }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-300">Brak różnic między kategorią główną a wielokrotnymi kategoriami.</p>
                @endforelse
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>

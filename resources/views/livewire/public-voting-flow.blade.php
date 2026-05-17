<div>
    <section class="panel">
        @if ($edition)
            <p>Okno głosowania: {{ $edition->voting_start->format('Y-m-d H:i') }} - {{ $edition->voting_end->format('Y-m-d H:i') }}</p>
        @else
            <p>Brak skonfigurowanej edycji głosowania.</p>
        @endif
    </section>

    @if ($statusMessage)
        <p class="notice">{{ $statusMessage }}</p>
    @endif

    @if ($edition)
        <section class="panel">
            <h2>Kod SMS</h2>
            <form wire:submit="issueToken">
                @csrf
                @error('token')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="token_pesel">PESEL</label>
                <input id="token_pesel" wire:model="pesel" required maxlength="11">
                @error('pesel')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="token_first_name">Imię</label>
                <input id="token_first_name" wire:model="firstName" required>
                @error('first_name')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="token_last_name">Nazwisko</label>
                <input id="token_last_name" wire:model="lastName" required>
                @error('last_name')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="token_mother_last_name">Nazwisko rodowe matki</label>
                <input id="token_mother_last_name" wire:model="motherLastName" required>
                @error('mother_last_name')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="token_phone">Telefon</label>
                <input id="token_phone" wire:model="phone" required>
                @error('phone')
                    <p class="error">{{ $message }}</p>
                @enderror

                <p><button type="submit">Wyślij kod</button></p>
            </form>
        </section>

        <section class="panel">
            <h2>Oddaj głos</h2>
            <form wire:submit="cast">
                @csrf
                @error('vote')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="vote_pesel">PESEL</label>
                <input id="vote_pesel" wire:model="pesel" required maxlength="11">

                <label for="vote_first_name">Imię</label>
                <input id="vote_first_name" wire:model="firstName" required>

                <label for="vote_last_name">Nazwisko</label>
                <input id="vote_last_name" wire:model="lastName" required>

                <label for="vote_mother_last_name">Nazwisko rodowe matki</label>
                <input id="vote_mother_last_name" wire:model="motherLastName" required>

                <label for="vote_phone">Telefon</label>
                <input id="vote_phone" wire:model="phone" required>

                <label for="sms_token">Kod SMS</label>
                <input id="sms_token" wire:model="smsToken" required maxlength="6">
                @error('sms_token')
                    <p class="error">{{ $message }}</p>
                @enderror

                <label for="local_project_id">Projekt lokalny</label>
                <select id="local_project_id" wire:model="localProjectId">
                    <option value="">Bez głosu lokalnego</option>
                    @foreach ($localProjects as $project)
                        <option value="{{ $project->id }}">
                            {{ $project->number_drawn ?? $project->number }}. {{ $project->title }}
                        </option>
                    @endforeach
                </select>

                <label for="citywide_project_id">Projekt ogólnomiejski</label>
                <select id="citywide_project_id" wire:model="citywideProjectId">
                    <option value="">Bez głosu ogólnomiejskiego</option>
                    @foreach ($citywideProjects as $project)
                        <option value="{{ $project->id }}">
                            {{ $project->number_drawn ?? $project->number }}. {{ $project->title }}
                        </option>
                    @endforeach
                </select>

                <label for="citizen_confirm">Oświadczenie</label>
                <select id="citizen_confirm" wire:model="citizenConfirm">
                    <option value="">Jestem w rejestrze mieszkańców</option>
                    <option value="2">Mieszkam w Szczecinie</option>
                    <option value="3">Uczę się, studiuję albo pracuję w Szczecinie</option>
                </select>

                <label>
                    <input wire:model="confirmMissingCategory" type="checkbox">
                    Potwierdzam świadomy brak głosu w jednej kategorii.
                </label>

                <label for="parent_name">Imię i nazwisko rodzica/opiekuna</label>
                <input id="parent_name" wire:model="parentName">

                <label>
                    <input wire:model="parentConfirm" type="checkbox">
                    Rodzic/opiekun potwierdza udział osoby niepełnoletniej.
                </label>

                <p><button type="submit">Oddaj głos</button></p>
            </form>
        </section>
    @endif

    <h2>Projekty lokalne</h2>
    <div class="grid">
        @forelse ($localProjects as $project)
            <article class="item">
                <p class="muted">{{ $project->area?->name }} · nr {{ $project->number_drawn ?? $project->number }}</p>
                <h2>{{ $project->title }}</h2>
            </article>
        @empty
            <p class="panel">Brak projektów lokalnych na liście do głosowania.</p>
        @endforelse
    </div>

    <h2>Projekty ogólnomiejskie</h2>
    <div class="grid">
        @forelse ($citywideProjects as $project)
            <article class="item">
                <p class="muted">{{ $project->area?->name }} · nr {{ $project->number_drawn ?? $project->number }}</p>
                <h2>{{ $project->title }}</h2>
            </article>
        @empty
            <p class="panel">Brak projektów ogólnomiejskich na liście do głosowania.</p>
        @endforelse
    </div>
</div>

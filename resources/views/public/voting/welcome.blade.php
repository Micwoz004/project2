<x-public.layout title="Głosowanie">
    <h1>Głosowanie</h1>

    <section class="panel">
        @if ($edition)
            <p>Okno głosowania: {{ $edition->voting_start->format('Y-m-d H:i') }} - {{ $edition->voting_end->format('Y-m-d H:i') }}</p>
        @else
            <p>Brak skonfigurowanej edycji głosowania.</p>
        @endif
    </section>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($edition)
        <section class="panel">
            <h2>Kod SMS</h2>
            <form method="post" action="{{ route('public.voting.token') }}">
                @csrf
                <label for="token_pesel">PESEL</label>
                <input id="token_pesel" name="pesel" value="{{ old('pesel') }}" required maxlength="11">

                <label for="token_first_name">Imię</label>
                <input id="token_first_name" name="first_name" value="{{ old('first_name') }}" required>

                <label for="token_last_name">Nazwisko</label>
                <input id="token_last_name" name="last_name" value="{{ old('last_name') }}" required>

                <label for="token_mother_last_name">Nazwisko rodowe matki</label>
                <input id="token_mother_last_name" name="mother_last_name" value="{{ old('mother_last_name') }}" required>

                <label for="token_phone">Telefon</label>
                <input id="token_phone" name="phone" value="{{ old('phone') }}" required>

                <p><button type="submit">Wyślij kod</button></p>
            </form>
        </section>

        <section class="panel">
            <h2>Oddaj głos</h2>
            <form method="post" action="{{ route('public.voting.cast') }}">
                @csrf
                <input type="hidden" name="budget_edition_id" value="{{ $edition->id }}">

                <label for="vote_pesel">PESEL</label>
                <input id="vote_pesel" name="pesel" value="{{ old('pesel') }}" required maxlength="11">

                <label for="vote_first_name">Imię</label>
                <input id="vote_first_name" name="first_name" value="{{ old('first_name') }}" required>

                <label for="vote_last_name">Nazwisko</label>
                <input id="vote_last_name" name="last_name" value="{{ old('last_name') }}" required>

                <label for="vote_mother_last_name">Nazwisko rodowe matki</label>
                <input id="vote_mother_last_name" name="mother_last_name" value="{{ old('mother_last_name') }}" required>

                <label for="vote_phone">Telefon</label>
                <input id="vote_phone" name="phone" value="{{ old('phone') }}" required>

                <label for="sms_token">Kod SMS</label>
                <input id="sms_token" name="sms_token" value="{{ old('sms_token') }}" required maxlength="6">

                <label for="local_project_id">Projekt lokalny</label>
                <select id="local_project_id" name="local_project_id">
                    <option value="">Bez głosu lokalnego</option>
                    @foreach ($localProjects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('local_project_id') === $project->id)>
                            {{ $project->number_drawn ?? $project->number }}. {{ $project->title }}
                        </option>
                    @endforeach
                </select>

                <label for="citywide_project_id">Projekt ogólnomiejski</label>
                <select id="citywide_project_id" name="citywide_project_id">
                    <option value="">Bez głosu ogólnomiejskiego</option>
                    @foreach ($citywideProjects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('citywide_project_id') === $project->id)>
                            {{ $project->number_drawn ?? $project->number }}. {{ $project->title }}
                        </option>
                    @endforeach
                </select>

                <label for="citizen_confirm">Oświadczenie</label>
                <select id="citizen_confirm" name="citizen_confirm">
                    <option value="">Jestem w rejestrze mieszkańców</option>
                    <option value="2" @selected(old('citizen_confirm') === '2')>Mieszkam w Szczecinie</option>
                    <option value="3" @selected(old('citizen_confirm') === '3')>Uczę się, studiuję albo pracuję w Szczecinie</option>
                </select>

                <label>
                    <input name="confirm_missing_category" type="checkbox" value="1" @checked(old('confirm_missing_category'))>
                    Potwierdzam świadomy brak głosu w jednej kategorii.
                </label>

                <label for="parent_name">Imię i nazwisko rodzica/opiekuna</label>
                <input id="parent_name" name="parent_name" value="{{ old('parent_name') }}">

                <label>
                    <input name="parent_confirm" type="checkbox" value="1" @checked(old('parent_confirm'))>
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
</x-public.layout>

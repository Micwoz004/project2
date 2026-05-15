<x-public.layout title="Zgłoś projekt">
    <h1>Zgłoś projekt</h1>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form class="panel" method="post" action="{{ route('public.projects.store') }}">
        @csrf

        <label for="budget_edition_id">Edycja</label>
        <select id="budget_edition_id" name="budget_edition_id" required>
            @if ($edition)
                <option value="{{ $edition->id }}" @selected((int) old('budget_edition_id') === $edition->id)>
                    SBO {{ $edition->propose_start->format('Y') }}
                </option>
            @endif
        </select>

        <label for="project_area_id">Obszar</label>
        <select id="project_area_id" name="project_area_id" required>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((int) old('project_area_id') === $area->id)>
                    {{ $area->name }}
                </option>
            @endforeach
        </select>

        <label for="title">Tytuł</label>
        <input id="title" name="title" value="{{ old('title') }}" required maxlength="600">

        <label for="localization">Lokalizacja</label>
        <textarea id="localization" name="localization" required>{{ old('localization') }}</textarea>

        <label for="description">Opis</label>
        <textarea id="description" name="description" required>{{ old('description') }}</textarea>

        <label for="goal">Cel</label>
        <textarea id="goal" name="goal" required>{{ old('goal') }}</textarea>

        <label for="argumentation">Uzasadnienie</label>
        <textarea id="argumentation" name="argumentation" required>{{ old('argumentation') }}</textarea>

        <label for="availability">Dostępność</label>
        <textarea id="availability" name="availability" required>{{ old('availability') }}</textarea>

        <label for="recipients">Odbiorcy</label>
        <textarea id="recipients" name="recipients" required>{{ old('recipients') }}</textarea>

        <label for="free_of_charge">Bezpłatność</label>
        <textarea id="free_of_charge" name="free_of_charge" required>{{ old('free_of_charge') }}</textarea>

        <label for="cost_description">Pozycja kosztorysu</label>
        <input id="cost_description" name="cost_description" value="{{ old('cost_description') }}" required>

        <label for="cost_amount">Kwota</label>
        <input id="cost_amount" name="cost_amount" value="{{ old('cost_amount') }}" type="number" step="0.01" min="0" required>

        <label>
            <input name="support_list" type="checkbox" value="1" @checked(old('support_list')) required>
            Potwierdzam dołączenie listy poparcia zgodnie z regulaminem.
        </label>

        <p><button type="submit">Złóż projekt</button></p>
    </form>
</x-public.layout>

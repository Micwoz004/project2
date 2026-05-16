<x-public.layout title="Zgłoś projekt">
    <h1>Zgłoś projekt</h1>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @php
        $mapDataValue = old('map_data', '');
        if (is_array($mapDataValue)) {
            $mapDataValue = json_encode($mapDataValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    @endphp

    <form class="panel" method="post" action="{{ route('public.projects.store') }}" enctype="multipart/form-data">
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

        <label for="category_id">Kategoria</label>
        <select id="category_id" name="category_id" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>

        <label for="title">Tytuł</label>
        <input id="title" name="title" value="{{ old('title') }}" required maxlength="600">

        <label for="localization">Lokalizacja</label>
        <textarea id="localization" name="localization" required>{{ old('localization') }}</textarea>

        <label for="address">Adres</label>
        <input id="address" name="address" value="{{ old('address') }}" maxlength="300">

        <label for="plot">Działka</label>
        <textarea id="plot" name="plot">{{ old('plot') }}</textarea>

        <label for="lat">Szerokość geograficzna</label>
        <input id="lat" name="lat" value="{{ old('lat') }}" type="number" step="0.0000001" min="-90" max="90">

        <label for="lng">Długość geograficzna</label>
        <input id="lng" name="lng" value="{{ old('lng') }}" type="number" step="0.0000001" min="-180" max="180">

        <label for="map_lng_lat">Współrzędne mapy</label>
        <textarea id="map_lng_lat" name="map_lng_lat">{{ old('map_lng_lat') }}</textarea>

        <label for="map_data">Dane mapy JSON</label>
        <textarea id="map_data" name="map_data">{{ $mapDataValue }}</textarea>

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

        <h2>Współautorzy</h2>
        @for ($index = 0; $index < 2; $index++)
            <fieldset>
                <legend>Współautor {{ $index + 1 }}</legend>

                <label for="coauthor_{{ $index }}_first_name">Imię</label>
                <input id="coauthor_{{ $index }}_first_name" name="coauthors[{{ $index }}][first_name]" value="{{ old("coauthors.$index.first_name") }}" maxlength="127">

                <label for="coauthor_{{ $index }}_last_name">Nazwisko</label>
                <input id="coauthor_{{ $index }}_last_name" name="coauthors[{{ $index }}][last_name]" value="{{ old("coauthors.$index.last_name") }}" maxlength="127">

                <label for="coauthor_{{ $index }}_email">E-mail</label>
                <input id="coauthor_{{ $index }}_email" name="coauthors[{{ $index }}][email]" value="{{ old("coauthors.$index.email") }}" type="email" maxlength="127">

                <label for="coauthor_{{ $index }}_phone">Telefon</label>
                <input id="coauthor_{{ $index }}_phone" name="coauthors[{{ $index }}][phone]" value="{{ old("coauthors.$index.phone") }}" maxlength="30">

                <label for="coauthor_{{ $index }}_post_code">Kod pocztowy</label>
                <input id="coauthor_{{ $index }}_post_code" name="coauthors[{{ $index }}][post_code]" value="{{ old("coauthors.$index.post_code") }}" maxlength="6">

                <label for="coauthor_{{ $index }}_city">Miejscowość</label>
                <input id="coauthor_{{ $index }}_city" name="coauthors[{{ $index }}][city]" value="{{ old("coauthors.$index.city") }}" maxlength="127">

                <label>
                    <input name="coauthors[{{ $index }}][read_confirm]" type="checkbox" value="1" @checked(old("coauthors.$index.read_confirm"))>
                    Współautor potwierdził zapoznanie się z informacją.
                </label>

                <label>
                    <input name="coauthors[{{ $index }}][email_agree]" type="checkbox" value="1" @checked(old("coauthors.$index.email_agree"))>
                    Współautor zgadza się na kontakt e-mail.
                </label>

                <label>
                    <input name="coauthors[{{ $index }}][phone_agree]" type="checkbox" value="1" @checked(old("coauthors.$index.phone_agree"))>
                    Współautor zgadza się na kontakt telefoniczny.
                </label>
            </fieldset>
        @endfor

        <label>
            <input name="support_list" type="checkbox" value="1" @checked(old('support_list')) required>
            Potwierdzam dołączenie listy poparcia zgodnie z regulaminem.
        </label>

        <label for="support_list_file">Plik listy poparcia</label>
        <input id="support_list_file" name="support_list_file" type="file" required>

        <label for="owner_agreement_files">Zgody właściciela</label>
        <input id="owner_agreement_files" name="owner_agreement_files[]" type="file" multiple>

        <label for="map_files">Załączniki mapy</label>
        <input id="map_files" name="map_files[]" type="file" multiple>

        <label for="parent_agreement_files">Zgody rodzica lub opiekuna</label>
        <input id="parent_agreement_files" name="parent_agreement_files[]" type="file" multiple>

        <label for="attachment_files">Pozostałe załączniki</label>
        <input id="attachment_files" name="attachment_files[]" type="file" multiple>

        <p><button type="submit">Złóż projekt</button></p>
    </form>
</x-public.layout>

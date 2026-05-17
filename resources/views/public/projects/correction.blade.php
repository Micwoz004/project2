<x-public.layout title="Korekta projektu">
    <h1>Korekta projektu</h1>

    <div class="panel">
        <p><strong>{{ $project->title }}</strong></p>
        <p class="muted">Termin korekty: {{ $correction->correction_deadline->format('Y-m-d H:i') }}</p>
        @if ($correction->notes)
            <p>{{ $correction->notes }}</p>
        @endif
    </div>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @php
        $allowed = $correction->allowed_fields;
        $mapDataValue = old('map_data', $project->map_data ?? []);
        if (is_array($mapDataValue)) {
            $mapDataValue = json_encode($mapDataValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $costItemsValue = old('cost_items', $project->costItems->map(fn ($item) => [
            'description' => $item->description,
            'amount' => $item->amount,
        ])->all());
        if ($costItemsValue === []) {
            $costItemsValue = [['description' => '', 'amount' => '']];
        }
    @endphp

    <form class="panel" method="post" action="{{ route('public.projects.corrections.update', $project) }}">
        @csrf
        @method('put')

        @if (in_array('project_area_id', $allowed, true))
            <label for="project_area_id">Obszar</label>
            <select id="project_area_id" name="project_area_id" required>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}" @selected((int) old('project_area_id', $project->project_area_id) === $area->id)>
                        {{ $area->name }}
                    </option>
                @endforeach
            </select>
        @endif

        @if (in_array('category_id', $allowed, true))
            <label for="category_id">Kategoria</label>
            <select id="category_id" name="category_id" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((int) old('category_id', $project->category_id) === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        @endif

        @if (in_array('title', $allowed, true))
            <label for="title">Tytuł</label>
            <input id="title" name="title" value="{{ old('title', $project->title) }}" required maxlength="600">
        @endif

        @if (in_array('localization', $allowed, true))
            <label for="localization">Lokalizacja</label>
            <textarea id="localization" name="localization" required>{{ old('localization', $project->localization) }}</textarea>
        @endif

        @if (in_array('map_data', $allowed, true))
            <label for="map_data">Dane mapy JSON</label>
            <textarea id="map_data" name="map_data">{{ $mapDataValue }}</textarea>
        @endif

        @if (in_array('description', $allowed, true))
            <label for="description">Opis</label>
            <textarea id="description" name="description" required>{{ old('description', $project->description) }}</textarea>
        @endif

        @if (in_array('goal', $allowed, true))
            <label for="goal">Cel</label>
            <textarea id="goal" name="goal" required>{{ old('goal', $project->goal) }}</textarea>
        @endif

        @if (in_array('argumentation', $allowed, true))
            <label for="argumentation">Uzasadnienie</label>
            <textarea id="argumentation" name="argumentation" required>{{ old('argumentation', $project->argumentation) }}</textarea>
        @endif

        @if (in_array('availability', $allowed, true))
            <label for="availability">Dostępność</label>
            <textarea id="availability" name="availability" required>{{ old('availability', $project->availability) }}</textarea>
        @endif

        @if (in_array('recipients', $allowed, true))
            <label for="recipients">Odbiorcy</label>
            <textarea id="recipients" name="recipients" required>{{ old('recipients', $project->recipients) }}</textarea>
        @endif

        @if (in_array('free_of_charge', $allowed, true))
            <label for="free_of_charge">Bezpłatność</label>
            <textarea id="free_of_charge" name="free_of_charge" required>{{ old('free_of_charge', $project->free_of_charge) }}</textarea>
        @endif

        @if (in_array('cost', $allowed, true))
            <h2>Kosztorys</h2>
            @foreach ($costItemsValue as $index => $costItem)
                <fieldset>
                    <legend>Pozycja {{ $index + 1 }}</legend>

                    <label for="cost_items_{{ $index }}_description">Opis</label>
                    <input id="cost_items_{{ $index }}_description" name="cost_items[{{ $index }}][description]" value="{{ $costItem['description'] ?? '' }}" required maxlength="1000">

                    <label for="cost_items_{{ $index }}_amount">Kwota</label>
                    <input id="cost_items_{{ $index }}_amount" name="cost_items[{{ $index }}][amount]" value="{{ $costItem['amount'] ?? '' }}" type="number" step="0.01" min="0" required>
                </fieldset>
            @endforeach
        @endif

        @if (in_array('support_attachment', $allowed, true))
            <label for="support_list_files">Listy poparcia</label>
            <input id="support_list_files" name="support_list_files[]" type="file" multiple>
        @endif

        @if (in_array('agreement_attachment', $allowed, true))
            <label for="owner_agreement_files">Zgody właściciela</label>
            <input id="owner_agreement_files" name="owner_agreement_files[]" type="file" multiple>
        @endif

        @if (in_array('map_attachment', $allowed, true))
            <label for="map_files">Załączniki mapy</label>
            <input id="map_files" name="map_files[]" type="file" multiple>
        @endif

        @if (in_array('parent_agreement_attachment', $allowed, true))
            <label for="parent_agreement_files">Zgody rodzica lub opiekuna</label>
            <input id="parent_agreement_files" name="parent_agreement_files[]" type="file" multiple>
        @endif

        @if (in_array('attachments', $allowed, true))
            <label for="attachment_files">Pozostałe załączniki</label>
            <input id="attachment_files" name="attachment_files[]" type="file" multiple>
        @endif

        <p><button type="submit">Zapisz korektę</button></p>
    </form>
</x-public.layout>

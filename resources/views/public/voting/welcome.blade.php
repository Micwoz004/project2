<x-public.layout title="Głosowanie">
    <h1>Głosowanie</h1>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <livewire:public-voting-flow />
</x-public.layout>

<x-public.layout title="Głosowanie">
    <section class="page-hero">
        <div>
            <h1 class="page-title">Głosowanie</h1>
            <p class="page-summary">Wydaj kod SMS, wybierz projekt lokalny i ogólnomiejski, a następnie zapisz kartę głosowania przez ten sam silnik domenowy co w legacy.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="panel">
            @foreach ($errors->all() as $error)
                <p class="error">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <livewire:public-voting-flow />
</x-public.layout>

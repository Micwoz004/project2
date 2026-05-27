<x-public.layout :title="$title">
    <section class="page-hero">
        <div>
            <h1 class="page-title">{{ $title }}</h1>
            <p class="page-summary">Informacje publiczne dotyczące przebiegu budżetu obywatelskiego.</p>
        </div>
    </section>

    <article class="panel content-body">
        {!! $body !!}
    </article>
</x-public.layout>

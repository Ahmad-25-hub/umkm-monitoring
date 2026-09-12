@if ($errors->any())
    <div role="alert" class="mt-5 rounded-xl border border-critical/20 bg-critical/5 px-4 py-3 text-sm leading-6 text-critical">
        <ul class="list-inside list-disc">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

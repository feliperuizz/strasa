@if(session('status'))
    <div x-data="{ show:true }" x-show="show" x-init="setTimeout(()=>show=false, 4000)"
         class="mx-4 mt-3 flex items-center justify-between rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-2 text-sm text-emerald-300">
        <span>{{ session('status') }}</span>
        <button @click="show=false" class="text-emerald-400/70 hover:text-emerald-200">✕</button>
    </div>
@endif

@if($errors->any())
    <div class="mx-4 mt-3 rounded-lg border border-rose-700/50 bg-rose-900/30 px-4 py-2 text-sm text-rose-300">
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

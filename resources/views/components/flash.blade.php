@if(session('success'))
  <div class="mb-3 p-3 rounded bg-green-50 border border-green-200 text-green-800">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="mb-3 p-3 rounded bg-red-50 border border-red-200 text-red-800">
    <ul class="list-disc list-inside">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

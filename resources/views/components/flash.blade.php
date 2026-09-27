@if(session('success'))
  <div class="mb-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm font-medium text-success-800">{{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="mb-3 rounded-xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm font-medium text-danger-800">
    <ul class="list-disc list-inside">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

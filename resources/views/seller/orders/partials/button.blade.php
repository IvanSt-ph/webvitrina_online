<form method="POST"
      action="{{ route('seller.orders.updateStatus', $order) }}"
      class="mb-3">
    @csrf
    <input type="hidden" name="status" value="{{ $status }}">

    <button class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
        {{ $text }}
    </button>
</form>

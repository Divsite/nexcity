<div class="d-flex gap-1 flex-wrap">
    @if($row->status !== 'paid')
        <form method="POST" action="{{ route('rt.dues.bills.update', $row->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="paid">
            <button type="submit" class="btn btn-sm btn-success">
                <i class="ri-check-line align-bottom"></i> {{ __('messages.dues_mark_paid') }}
            </button>
        </form>
    @endif

    @if($row->status !== 'waived')
        <form method="POST" action="{{ route('rt.dues.bills.update', $row->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="waived">
            <button type="submit" class="btn btn-sm btn-soft-info">
                {{ __('messages.dues_mark_waived') }}
            </button>
        </form>
    @endif

    @if($row->status !== 'pending')
        <form method="POST" action="{{ route('rt.dues.bills.update', $row->id) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="pending">
            <button type="submit" class="btn btn-sm btn-soft-secondary">
                {{ __('messages.dues_mark_pending') }}
            </button>
        </form>
    @endif
</div>

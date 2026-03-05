<div id="charity-expense-root">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="fw-semibold">{{ __('messages.finance') }}</div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <div id="finance-year-picker" data-year="{{ $year }}" wire:ignore></div>
            <input type="hidden" id="finance-year-sync" wire:model.live="year">
            
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6 col-lg-3">
            <div class="border rounded p-3 h-100">
                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.total_income') }}</div>
                <div class="fs-6 fw-semibold mb-1">{{ \Cknow\Money\Money::IDR($summary['income_money'] ?? 0)->format(app()->getLocale()) }}</div>
                <div class="text-muted fs-13">{{ __('messages.total_rice') }}: {{ \Illuminate\Support\Number::format($summary['income_rice'] ?? 0, 2, 2, app()->getLocale()) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="border rounded p-3 h-100">
                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.distribution_required') }}</div>
                <div class="fs-6 fw-semibold mb-1">{{ \Cknow\Money\Money::IDR($summary['distribution_money'] ?? 0)->format(app()->getLocale()) }}</div>
                <div class="text-muted fs-13">{{ __('messages.total_rice') }}: {{ \Illuminate\Support\Number::format($summary['distribution_rice'] ?? 0, 2, 2, app()->getLocale()) }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="border rounded p-3 h-100">
                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.expenses') }}</div>
                <div class="fs-6 fw-semibold mb-1">{{ \Cknow\Money\Money::IDR($summary['expense_money'] ?? 0)->format(app()->getLocale()) }}</div>
                <div class="text-muted fs-13">{{ __('messages.year') }}: {{ $year }}</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="border rounded p-3 h-100">
                <div class="text-muted text-uppercase fs-12 mb-1">{{ __('messages.remaining_overall') }}</div>
                <div class="fs-6 fw-semibold mb-1">{{ \Cknow\Money\Money::IDR($summary['remaining_money'] ?? 0)->format(app()->getLocale()) }}</div>
                <div class="text-muted fs-13">{{ __('messages.total_rice') }}: {{ \Illuminate\Support\Number::format($summary['remaining_rice'] ?? 0, 2, 2, app()->getLocale()) }}</div>
            </div>
        </div>
    </div>

    <div class="card border mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">{{ __('messages.distribution_summary') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.status') }}</th>
                            <th class="text-end">{{ __('messages.total_money') }}</th>
                            <th class="text-end">{{ __('messages.total_rice') }}</th>
                            <th class="text-end">{{ __('messages.recipients') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ __('messages.distributed') }}</td>
                            <td class="text-end">{{ \Cknow\Money\Money::IDR($summary['distributed_money'] ?? 0)->format(app()->getLocale()) }}</td>
                            <td class="text-end">{{ \Illuminate\Support\Number::format($summary['distributed_rice'] ?? 0, 2, 2, app()->getLocale()) }}</td>
                            <td class="text-end">{{ $summary['distributed_recipients'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.redirected') }}</td>
                            <td class="text-end">{{ \Cknow\Money\Money::IDR($summary['redirected_money'] ?? 0)->format(app()->getLocale()) }}</td>
                            <td class="text-end">{{ \Illuminate\Support\Number::format($summary['redirected_rice'] ?? 0, 2, 2, app()->getLocale()) }}</td>
                            <td class="text-end">{{ $summary['redirected_recipients'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td>{{ __('messages.failed') }}</td>
                            <td class="text-end">{{ \Cknow\Money\Money::IDR($summary['failed_money'] ?? 0)->format(app()->getLocale()) }}</td>
                            <td class="text-end">{{ \Illuminate\Support\Number::format($summary['failed_rice'] ?? 0, 2, 2, app()->getLocale()) }}</td>
                            <td class="text-end">{{ $summary['failed_recipients'] ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small">
                {{ __('messages.distribution_required') }} = {{ __('messages.total_recipients') }} x {{ __('messages.get_money') }}/{{ __('messages.get_rice') }}
            </div>
        </div>
    </div>

    <div class="card border mb-3">
        <div class="card-body">
            <h6 class="card-title mb-3">{{ __('messages.summary') }}</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('messages.charity_type') }}</th>
                            <th class="text-end">{{ __('messages.total_income') }}</th>
                            <th class="text-end">{{ __('messages.allocated_amount') }}</th>
                            <th class="text-end">{{ __('messages.expenses') }}</th>
                            <th class="text-end">{{ __('messages.remaining') }}</th>
                            <th class="text-end">{{ __('messages.total_rice') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summary['type_breakdown'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['name'] ?? '-' }}</td>
                                <td class="text-end">{{ \Cknow\Money\Money::IDR($row['income_money'] ?? 0)->format(app()->getLocale()) }}</td>
                                <td class="text-end">{{ \Cknow\Money\Money::IDR($row['allocated_money'] ?? 0)->format(app()->getLocale()) }}</td>
                                <td class="text-end">{{ \Cknow\Money\Money::IDR($row['expense_money'] ?? 0)->format(app()->getLocale()) }}</td>
                                <td class="text-end">{{ \Cknow\Money\Money::IDR($row['remaining_money'] ?? 0)->format(app()->getLocale()) }}</td>
                                <td class="text-end">
                                    {{ \Illuminate\Support\Number::format($row['income_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                    · {{ \Illuminate\Support\Number::format($row['allocated_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                    · {{ \Illuminate\Support\Number::format($row['remaining_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($summary['type_totals']))
                        <tfoot>
                            <tr>
                                <th>{{ __('messages.total') }}</th>
                                <th class="text-end">{{ \Cknow\Money\Money::IDR($summary['type_totals']['income_money'] ?? 0)->format(app()->getLocale()) }}</th>
                                <th class="text-end">{{ \Cknow\Money\Money::IDR($summary['type_totals']['allocated_money'] ?? 0)->format(app()->getLocale()) }}</th>
                                <th class="text-end">{{ \Cknow\Money\Money::IDR($summary['type_totals']['expense_money'] ?? 0)->format(app()->getLocale()) }}</th>
                                <th class="text-end">{{ \Cknow\Money\Money::IDR($summary['type_totals']['remaining_money'] ?? 0)->format(app()->getLocale()) }}</th>
                                <th class="text-end">
                                    {{ \Illuminate\Support\Number::format($summary['type_totals']['income_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                    · {{ \Illuminate\Support\Number::format($summary['type_totals']['allocated_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                    · {{ \Illuminate\Support\Number::format($summary['type_totals']['remaining_rice'] ?? 0, 2, 2, app()->getLocale()) }}
                                </th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <div class="text-muted small">
                {{ __('messages.total_rice') }}: {{ __('messages.total_income') }} · {{ __('messages.used_funds') }} · {{ __('messages.remaining') }}
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card border h-100">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('messages.distribution_by_class') }}</h6>
                    <div id="finance-chart-classes"
                         data-chart='@json($summary["charts"]["classes"] ?? [])'
                         style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border h-100">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('messages.recipients_status_chart') }}</h6>
                    <div id="finance-chart-recipients"
                         data-chart='@json($summary["charts"]["recipients"] ?? [])'
                         style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card border">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        {{ $editingId ? __('messages.edit') : __('messages.create') }} {{ __('messages.expenses') }}
                    </h6>
                    <form wire:submit.prevent="save">
                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.source') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('source_type') is-invalid @enderror" wire:model.live="source_type">
                                <option value="charity">{{ __('messages.charity_type') }}</option>
                                <option value="other">{{ __('messages.other') }}</option>
                            </select>
                            @error('source_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if($source_type === 'charity')
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.charity_type') }} <span class="text-danger">*</span></label>
                                <select class="form-select @error('charity_type_id') is-invalid @enderror" wire:model.defer="charity_type_id">
                                    <option value="">{{ __('messages.please_select') }}</option>
                                    @foreach($charityTypes as $type)
                                        <option value="{{ $type['id'] }}">{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('charity_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.source_name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('source_name') is-invalid @enderror" wire:model.defer="source_name">
                                @error('source_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.type') }} <span class="text-danger">*</span></label>
                            <select class="form-select @error('expense_type') is-invalid @enderror" wire:model.live="expense_type">
                                <option value="operational">{{ __('messages.operational_expense') }}</option>
                                <option value="other">{{ __('messages.other') }}</option>
                            </select>
                            @error('expense_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if($expense_type === 'other')
                            <div class="mb-3">
                                <label class="form-label">{{ __('messages.other') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('expense_type_name') is-invalid @enderror" wire:model.defer="expense_type_name">
                                @error('expense_type_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        <div class="mb-3" wire:ignore>
                            <label class="form-label">{{ __('messages.amount') }} <span class="text-danger">*</span></label>
                            <div id="charity-expense-amount"
                                 data-input-id="charity-expense-amount-sync"
                                 data-value="{{ $amount }}"></div>
                            <input type="hidden" id="charity-expense-amount-sync" wire:model.defer="amount">
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3" wire:ignore>
                            <label class="form-label">{{ __('messages.date') }}</label>
                            <input type="text" id="charity-expense-date" class="form-control @error('expense_date') is-invalid @enderror" value="{{ $expense_date }}">
                            <input type="hidden" id="charity-expense-date-sync" wire:model.defer="expense_date">
                            @error('expense_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('messages.notes') }}</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" rows="3" wire:model.defer="notes"></textarea>
                            @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                            @if($editingId)
                                <button type="button" class="btn btn-light" wire:click="resetForm">{{ __('messages.cancel') }}</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border">
                <div class="card-body">
                    <h6 class="card-title mb-3">{{ __('messages.expenses') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('messages.date') }}</th>
                                    <th>{{ __('messages.source') }}</th>
                                    <th>{{ __('messages.type') }}</th>
                                    <th class="text-end">{{ __('messages.amount') }}</th>
                                    <th>{{ __('messages.created_by') }}</th>
                                    <th>{{ __('messages.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                    <tr>
                                        <td>{{ $expense->expense_date?->format('d/m/Y') ?? '-' }}</td>
                                        <td>
                                            @if($expense->source_type === 'charity')
                                                {{ $expense->charityType?->source?->name ?? '-' }}
                                            @else
                                                {{ $expense->source_name ?? '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($expense->expense_type === 'other')
                                                {{ $expense->expense_type_name ?? __('messages.other') }}
                                            @else
                                                {{ __('messages.' . ($expense->expense_type ?? 'operational')) }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ \Cknow\Money\Money::IDR($expense->amount)->format(app()->getLocale()) }}</td>
                                        <td>{{ $expense->createdBy?->name ?? '-' }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-soft-secondary" wire:click="edit({{ $expense->id }})">
                                                {{ __('messages.edit') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-soft-danger" wire:click="delete({{ $expense->id }})">
                                                {{ __('messages.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">{{ __('messages.data_not_found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $expenses->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('assets/libs/flatpickr/flatpickr.min.js') }}"></script>
<script>
    (function () {
        const renderCharts = () => {
            if (!window.ApexCharts) {
                return;
            }

            const classEl = document.getElementById('finance-chart-classes');
            const recipientEl = document.getElementById('finance-chart-recipients');

            if (classEl) {
                const chartData = JSON.parse(classEl.dataset.chart || '{}');
                if (classEl._chart) {
                    classEl._chart.destroy();
                }

                const options = {
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    series: [
                        { name: '{{ __('messages.total_money') }}', data: chartData.money || [] },
                        { name: '{{ __('messages.total_rice') }}', data: chartData.rice || [] },
                    ],
                    xaxis: { categories: chartData.labels || [] },
                    dataLabels: { enabled: false },
                };

                classEl._chart = new ApexCharts(classEl, options);
                classEl._chart.render();
            }

            if (recipientEl) {
                const chartData = JSON.parse(recipientEl.dataset.chart || '{}');
                if (recipientEl._chart) {
                    recipientEl._chart.destroy();
                }

                const options = {
                    chart: { type: 'donut', height: 280 },
                    labels: chartData.labels || [],
                    series: chartData.series || [],
                    legend: { position: 'bottom' },
                };

                recipientEl._chart = new ApexCharts(recipientEl, options);
                recipientEl._chart.render();
            }
        };

        document.addEventListener('livewire:load', renderCharts);

        if (window.Livewire && typeof window.Livewire.hook === 'function') {
            window.Livewire.hook('message.processed', () => {
                renderCharts();
            });
        }
    })();
</script>
@endpush

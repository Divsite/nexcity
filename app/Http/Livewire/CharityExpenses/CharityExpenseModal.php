<?php

namespace App\Http\Livewire\CharityExpenses;

use App\Models\Charities\CharityExpense;
use App\Models\Charities\CharityTransaction;
use App\Models\Distributions\DistributionFundSource;
use App\Models\Distributions\DistributionRecipient;
use App\Models\CharityTypes\CharityType;
use App\Models\DistributionClasses\DistributionClass;
use App\Services\Charities\CharityDistributionService;
use App\Services\Charities\CharityReceiptTotalsService;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class CharityExpenseModal extends Component
{
    use WithPagination;

    public ?int $editingId = null;
    public ?int $organization_id = null;
    public string $search = '';
    public int $year;
    public string $source_type = 'charity';
    public ?int $charity_type_id = null;
    public ?string $source_name = null;
    public string $expense_type = 'operational';
    public ?string $expense_type_name = null;
    public ?float $amount = null;
    public ?string $expense_date = null;
    public ?string $notes = null;

    public bool $isPartner = false;

    protected $paginationTheme = 'bootstrap';

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $context = $this->partnerContext();
        if ($context) {
            $this->isPartner = true;
            $this->organization_id = $context['organization_id'];
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->resetPage();
        $charts = $this->chartPayload((int) $this->year, $this->organization_id);
        $this->dispatch('finance:charts-refresh');
        $this->dispatch('finance:charts-data', [
            'charts' => $charts,
            'year' => (int) $this->year,
        ]);
    }

    protected function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'source_type' => ['required', 'string', 'max:50'],
            'charity_type_id' => ['nullable', 'required_if:source_type,charity', 'exists:charity_types,id'],
            'source_name' => ['nullable', 'required_if:source_type,other', 'string', 'max:255'],
            'expense_type' => ['required', 'string', 'max:50'],
            'expense_type_name' => ['nullable', 'required_if:expense_type,other', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function createNew(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $model = CharityExpense::findOrFail($id);
        $this->editingId = $model->id;
        $this->organization_id = $model->organization_id;
        $this->year = $model->year;
        $this->source_type = $model->source_type;
        $this->charity_type_id = $model->charity_type_id;
        $this->source_name = $model->source_name;
        $this->expense_type = $model->expense_type;
        $this->expense_type_name = $model->expense_type_name;
        $this->amount = $model->amount;
        $this->expense_date = $model->expense_date?->format('Y-m-d');
        $this->notes = $model->notes;

        $this->dispatch('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'amount' => $this->amount,
            ],
        ]);
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->isPartner && $this->organization_id) {
            $data['organization_id'] = $this->organization_id;
        }

        if ($data['expense_type'] !== 'other') {
            $data['expense_type_name'] = null;
        }

        $data['created_by'] = auth()->id();

        CharityExpense::updateOrCreate(
            ['id' => $this->editingId],
            $data
        );

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $this->editingId ? __('messages.updated_successfully') : __('messages.created_successfully'),
        ]);

        $this->resetForm();
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $model = CharityExpense::find($id);
        if ($model) {
            $model->delete();
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => __('messages.deleted_successfully'),
            ]);
        }

        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->source_type = 'charity';
        $this->charity_type_id = null;
        $this->source_name = null;
        $this->expense_type = 'operational';
        $this->expense_type_name = null;
        $this->amount = null;
        $this->expense_date = null;
        $this->notes = null;

        $this->dispatch('currency-sync', [
            'id' => $this->getId(),
            'values' => [
                'amount' => null,
            ],
        ]);
    }

    public function render()
    {
        $query = CharityExpense::query()
            ->with(['charityType.source', 'createdBy'])
            ->when($this->organization_id, fn (Builder $builder) => $builder->where('organization_id', $this->organization_id))
            ->when($this->year, fn (Builder $builder) => $builder->where('year', $this->year))
            ->when($this->search, function (Builder $builder) {
                $builder->where(function (Builder $sub) {
                    $sub->where('source_name', 'like', '%' . $this->search . '%')
                        ->orWhere('notes', 'like', '%' . $this->search . '%');
                });
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id');

        $expenses = $query->paginate(10);

        $summary = $this->summaryPayload();

        return view('charity-expenses.modal', [
            'expenses' => $expenses,
            'charityTypes' => $this->charityTypeOptions(),
            'summary' => $summary,
        ]);
    }

    protected function summaryPayload(): array
    {
        $year = (int) $this->year;
        $organizationId = $this->organization_id;

        $incomeQuery = CharityTransaction::query()
            ->forOrganization($organizationId)
            ->paid()
            ->createdInYear($year);

        $incomeTotals = app(CharityReceiptTotalsService::class)->totalsForQuery($incomeQuery);
        $distributionSummary = app(CharityDistributionService::class)->distributionSummaryWithFilters(null, $year, null);
        $distributionStatus = $this->distributionStatusSummary($year, $organizationId);
        $expenseTotal = CharityExpense::query()
            ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
            ->where('year', $year)
            ->sum('amount');

        $remainingMoney = (float) $incomeTotals['total_money'] - (float) ($distributionSummary['total_money'] ?? 0) - (float) $expenseTotal;
        $remainingRice = (float) $incomeTotals['total_rice'] - (float) ($distributionSummary['total_rice'] ?? 0);

        $typeRows = $this->typeBreakdown($year, $organizationId);
        $charts = $this->chartPayload($year, $organizationId);

        return [
            'income_money' => (float) $incomeTotals['total_money'],
            'income_rice' => (float) $incomeTotals['total_rice'],
            'distribution_money' => (float) ($distributionSummary['total_money'] ?? 0),
            'distribution_rice' => (float) ($distributionSummary['total_rice'] ?? 0),
            'distributed_money' => $distributionStatus['distributed_money'],
            'distributed_rice' => $distributionStatus['distributed_rice'],
            'redirected_money' => $distributionStatus['redirected_money'],
            'redirected_rice' => $distributionStatus['redirected_rice'],
            'failed_money' => $distributionStatus['failed_money'],
            'failed_rice' => $distributionStatus['failed_rice'],
            'distributed_recipients' => $distributionStatus['distributed_recipients'],
            'redirected_recipients' => $distributionStatus['redirected_recipients'],
            'failed_recipients' => $distributionStatus['failed_recipients'],
            'expense_money' => (float) $expenseTotal,
            'remaining_money' => $remainingMoney,
            'remaining_rice' => $remainingRice,
            'type_breakdown' => $typeRows['rows'],
            'type_totals' => $typeRows['totals'],
            'charts' => $charts,
        ];
    }

    protected function typeBreakdown(int $year, ?int $organizationId): array
    {
        $types = CharityType::query()
            ->with('source')
            ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
            ->where('year', $year)
            ->orderBy('id', 'asc')
            ->get();

        $totalsService = app(CharityReceiptTotalsService::class);

        $allocationMap = DistributionFundSource::query()
            ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
            ->where('year', $year)
            ->where('source_type', 'charity')
            ->select('charity_type_id', \DB::raw('SUM(amount_used) as total_money'), \DB::raw('SUM(amount_used_rice) as total_rice'))
            ->groupBy('charity_type_id')
            ->get()
            ->keyBy('charity_type_id');

        $expenseMap = CharityExpense::query()
            ->when($organizationId, fn (Builder $builder) => $builder->where('organization_id', $organizationId))
            ->where('year', $year)
            ->where('source_type', 'charity')
            ->select('charity_type_id', \DB::raw('SUM(amount) as total_money'))
            ->groupBy('charity_type_id')
            ->get()
            ->keyBy('charity_type_id');

        $rows = [];
        $totals = [
            'income_money' => 0,
            'income_rice' => 0,
            'allocated_money' => 0,
            'allocated_rice' => 0,
            'expense_money' => 0,
            'remaining_money' => 0,
            'remaining_rice' => 0,
        ];

        foreach ($types as $type) {
            $incomeTotals = $totalsService->totalsForQuery(
                CharityTransaction::query()
                    ->forOrganization($organizationId)
                    ->paid()
                    ->createdInYear($year)
                    ->where('charity_type_id', $type->id)
            );
            $allocated = $allocationMap->get($type->id);
            $expense = $expenseMap->get($type->id);

            $incomeMoney = (float) ($incomeTotals['total_money'] ?? 0);
            $incomeRice = (float) ($incomeTotals['total_rice'] ?? 0);
            $allocatedMoney = (float) ($allocated?->total_money ?? 0);
            $allocatedRice = (float) ($allocated?->total_rice ?? 0);
            $expenseMoney = (float) ($expense?->total_money ?? 0);

            $remainingMoney = $incomeMoney - $allocatedMoney - $expenseMoney;
            $remainingRice = $incomeRice - $allocatedRice;

            $rows[] = [
                'id' => $type->id,
                'name' => ($type->source?->name ?? '-') . ' (' . $type->year . ')',
                'income_money' => $incomeMoney,
                'income_rice' => $incomeRice,
                'allocated_money' => $allocatedMoney,
                'allocated_rice' => $allocatedRice,
                'expense_money' => $expenseMoney,
                'remaining_money' => $remainingMoney,
                'remaining_rice' => $remainingRice,
            ];

            $totals['income_money'] += $incomeMoney;
            $totals['income_rice'] += $incomeRice;
            $totals['allocated_money'] += $allocatedMoney;
            $totals['allocated_rice'] += $allocatedRice;
            $totals['expense_money'] += $expenseMoney;
            $totals['remaining_money'] += $remainingMoney;
            $totals['remaining_rice'] += $remainingRice;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    protected function chartPayload(int $year, ?int $organizationId): array
    {
        $recipientQuery = DistributionRecipient::query()
            ->whereHas('distribution', function (Builder $builder) use ($organizationId, $year) {
                $builder->where('year', $year);
                if ($organizationId) {
                    $builder->where('organization_id', $organizationId);
                }
            });

        $statusCounts = $recipientQuery
            ->select('status', \DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusLabels = [
            'distributed' => __('messages.distributed'),
            'redirected' => __('messages.redirected'),
            'failed' => __('messages.failed'),
            'rescheduled' => __('messages.rescheduled'),
            'pending' => __('messages.pending'),
        ];

        $recipientChart = [
            'labels' => [],
            'series' => [],
        ];

        foreach ($statusLabels as $key => $label) {
            if (! $statusCounts->has($key)) {
                continue;
            }
            $recipientChart['labels'][] = $label;
            $recipientChart['series'][] = (int) $statusCounts->get($key);
        }

        $classCounts = DistributionRecipient::query()
            ->select('distribution_class_id', \DB::raw('COUNT(*) as total'))
            ->whereNotNull('distribution_class_id')
            ->whereHas('distribution', function (Builder $builder) use ($organizationId, $year) {
                $builder->where('year', $year);
                if ($organizationId) {
                    $builder->where('organization_id', $organizationId);
                }
            })
            ->groupBy('distribution_class_id')
            ->get();

        $classIds = $classCounts->pluck('distribution_class_id')->filter()->values()->all();
        $classes = DistributionClass::query()
            ->with('source')
            ->whereIn('id', $classIds)
            ->get()
            ->keyBy('id');

        $classChart = [
            'labels' => [],
            'money' => [],
            'rice' => [],
        ];

        foreach ($classCounts as $row) {
            $class = $classes->get($row->distribution_class_id);
            $label = $class?->source?->name ?? ('#' . $row->distribution_class_id);
            $moneyPer = (float) ($class?->get_money ?? 0);
            $ricePer = (float) ($class?->get_rice ?? 0);
            $classChart['labels'][] = $label;
            $classChart['money'][] = (float) $row->total * $moneyPer;
            $classChart['rice'][] = (float) $row->total * $ricePer;
        }

        return [
            'recipients' => $recipientChart,
            'classes' => $classChart,
        ];
    }

    protected function distributionStatusSummary(int $year, ?int $organizationId): array
    {
        $rows = DistributionRecipient::query()
            ->select('distribution_recipients.status', \DB::raw('COUNT(*) as total_count'), \DB::raw('SUM(COALESCE(distribution_classes.get_money,0)) as total_money'), \DB::raw('SUM(COALESCE(distribution_classes.get_rice,0)) as total_rice'))
            ->leftJoin('distribution_classes', 'distribution_classes.id', '=', 'distribution_recipients.distribution_class_id')
            ->whereHas('distribution', function (Builder $builder) use ($organizationId, $year) {
                $builder->where('year', $year);
                if ($organizationId) {
                    $builder->where('organization_id', $organizationId);
                }
            })
            ->groupBy('distribution_recipients.status')
            ->get()
            ->keyBy('status');

        $distributed = $rows->get('distributed');
        $redirected = $rows->get('redirected');
        $failed = $rows->get('failed');

        return [
            'distributed_money' => (float) ($distributed?->total_money ?? 0),
            'distributed_rice' => (float) ($distributed?->total_rice ?? 0),
            'redirected_money' => (float) ($redirected?->total_money ?? 0),
            'redirected_rice' => (float) ($redirected?->total_rice ?? 0),
            'failed_money' => (float) ($failed?->total_money ?? 0),
            'failed_rice' => (float) ($failed?->total_rice ?? 0),
            'distributed_recipients' => (int) ($distributed?->total_count ?? 0),
            'redirected_recipients' => (int) ($redirected?->total_count ?? 0),
            'failed_recipients' => (int) ($failed?->total_count ?? 0),
        ];
    }

    protected function charityTypeOptions(): array
    {
        return CharityType::query()
            ->with('source')
            ->when($this->organization_id, fn (Builder $builder) => $builder->where('organization_id', $this->organization_id))
            ->where('year', $this->year)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (CharityType $type) => [
                'id' => $type->id,
                'name' => $type->source?->name ?? '-',
                'label' => ($type->source?->name ?? '-') . ' (' . $type->year . ')',
            ])
            ->values()
            ->toArray();
    }

    protected function partnerContext(): ?array
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'partnerContext')) {
            return null;
        }

        return $user->partnerContext();
    }
}

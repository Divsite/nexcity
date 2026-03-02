<?php

namespace App\Http\Controllers\Charities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Charities\StoreCharityTransactionRequest;
use App\Http\Requests\Charities\UpdateCharityTransactionRequest;
use App\Http\Resources\Charities\CharityDistributionResource;
use App\Http\Resources\Charities\CharityTransactionFormResource;
use App\Http\Resources\Charities\CharityTransactionSummaryResource;
use App\Models\Charities\CharityTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\Charities\CharityDistributionService;
use App\Services\Charities\CharityTransactionService;

class CharityTransactionController extends Controller
{
    public function __construct(
        protected CharityTransactionService $transactionService,
        protected CharityDistributionService $distributionService
    ) {
        $this->middleware('permission:browse-mosque-charity-transactions')->only(['index', 'dailyRecap', 'summary']);
        $this->middleware('permission:add-mosque-charity-transactions')->only(['create', 'store']);
        $this->middleware('permission:edit-mosque-charity-transactions')->only(['edit', 'update']);
        $this->middleware('permission:delete-mosque-charity-transactions')->only('destroy');
    }

    public function index(): View
    {
        $context = $this->transactionService->partnerContext();
        $summaryPayload = $this->transactionService->summaryViewPayload(
            $context,
            (int) now()->year,
            route('mosque.charity-transactions.summary')
        );

        return view('charities.index', [
            'formPayload' => (new CharityTransactionFormResource(
                $this->transactionService->formPayload(new CharityTransaction(), true)
            ))->toArray(request()),
            'distributionFormPayload' => (new CharityDistributionResource($this->distributionService->formPayload()))
                ->toArray(request()),
            'summaryPayload' => (new CharityTransactionSummaryResource($summaryPayload))
                ->toArray(request()),
            'distributionSummaryPayload' => $this->distributionService->summaryViewPayload(),
            'dailyRecapPrintUrl' => route('mosque.charity-transactions.daily-recap.print'),
        ]);
    }

    public function create(): View
    {
        return view('charities.create', [
            'formPayload' => (new CharityTransactionFormResource(
                $this->transactionService->formPayload(new CharityTransaction())
            ))->toArray(request()),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type_id' => ['nullable', 'integer'],
            'year_type_id' => ['nullable', 'integer'],
            'year' => ['nullable', 'integer', 'min:2000'],
            'payment_method' => ['nullable', 'in:cash,transfer,qris'],
            'year_payment_method' => ['nullable', 'in:cash,transfer,qris'],
        ]);

        $payload = $this->transactionService->summaryPayload(
            $data['type_id'] ?? null,
            $data['year_type_id'] ?? null,
            $data['year'] ?? null,
            $data['payment_method'] ?? null,
            $data['year_payment_method'] ?? null
        );

        return response()->json(
            (new CharityTransactionSummaryResource($payload))->toArray($request)
        );
    }

    public function store(StoreCharityTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $context = $this->transactionService->partnerContext();
        $isModal = $request->input('_mode') === 'modal';

        if ($context) {
            $data['organization_id'] = $context['organization_id'];
        }

        $transaction = $this->transactionService->create($data);

        if ($isModal) {
            return response()->json([
                'success' => true,
                'message' => __('messages.created_successfully'),
                'id' => $transaction->id,
            ]);
        }

        flash()->success(__('messages.created_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-transactions.index'),
        ]);
    }

    public function edit(CharityTransaction $charityTransaction): View
    {
        return view('charities.edit', [
            'formPayload' => (new CharityTransactionFormResource(
                $this->transactionService->formPayload($charityTransaction)
            ))->toArray(request()),
        ]);
    }

    public function update(UpdateCharityTransactionRequest $request, CharityTransaction $charityTransaction): JsonResponse
    {
        $data = $request->validated();
        $context = $this->transactionService->partnerContext();

        if ($context) {
            $data['organization_id'] = $context['organization_id'];
        }

        $this->transactionService->update($charityTransaction, $data);

        flash()->success(__('messages.updated_successfully'));

        return response()->json([
            'redirect' => route('mosque.charity-transactions.index'),
        ]);
    }

    public function dailyRecap(Request $request): View
    {
        $payload = $this->transactionService->dailyRecapData($request->query('date'));

        return view('charities.recap-daily-print', $payload);
    }
}

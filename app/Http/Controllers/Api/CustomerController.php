<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerOpeningBalanceService;
use App\Services\PartyAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    protected $openingService;
    protected $partyAccounts;

    public function __construct(CustomerOpeningBalanceService $openingService, PartyAccountService $partyAccounts)
    {
        $this->openingService = $openingService;
        $this->partyAccounts = $partyAccounts;
    }

    // GET /api/customers
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('per_page') ?: 20);
        $q = $request->string('q')->toString();
        $withTrashed = filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN);
        $onlyTrashed = filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN);

        $query = Customer::query()->search($q)->orderByDesc('id');

        if ($onlyTrashed) {
            $query->onlyTrashed();
        } elseif ($withTrashed) {
            $query->withTrashed();
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->date('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->date('to_date'));
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return CustomerResource::collection($paginator);
    }

    // GET /api/customers/{customer}
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    // POST /api/customers
    public function store(StoreCustomerRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();

            $data['opening_balance'] = $data['opening_balance'] ?? 0;
            $data['opening_balance_date'] = $data['opening_balance_date'] ?? now()->toDateString();

            $customer = Customer::create($data);

            $customerAccount = $this->partyAccounts->createCustomerAccount($customer);
            if ($customerAccount) {
                $customer->chart_account_id = $customerAccount->id;
                $customer->saveQuietly();
            }

            // POST OPENING BALANCE JOURNAL (IF ANY)
            if (
                $customer->opening_balance > 0 &&
                in_array($customer->opening_balance_type, ['debit', 'credit'])
            ) {
                $this->openingService->createOpeningBalanceJournal($customer);
            }

            return (new CustomerResource($customer))
                ->additional(['message' => 'Customer created successfully.']);
        });
    }

    // PUT/PATCH /api/customers/{customer}
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        return DB::transaction(function () use ($request, $customer) {
            $customer->fill($request->validated());
            $customer->save();

            return (new CustomerResource($customer))
                ->additional(['message' => 'Customer updated successfully.']);
        });
    }

    // DELETE /api/customers/{customer}
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Customer deleted (soft).']);
    }

    // POST /api/customers/{id}/restore
    public function restore($id)
    {
        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        return (new CustomerResource($customer))
            ->additional(['message' => 'Customer restored successfully.']);
    }
}

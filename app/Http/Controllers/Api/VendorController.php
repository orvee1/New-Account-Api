<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\VentorOpeningBalanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    /**
     * GET /api/vendors?search=&per_page=20
     */
    protected $openingService;

    public function __construct(VentorOpeningBalanceService $openingService)
    {
        $this->openingService = $openingService;
    }
    public function index(Request $request)
    {
        $user    = $request->user();
        $search  = (string) $request->get('search', '');
        $perPage = (int) $request->integer('per_page', 20);

        $query = Vendor::query()
            ->where('company_id', $user->company_id)
            ->when($search, function (Builder $q) use ($search) {
                $like = '%' . str_replace('%', '\%', $search) . '%';
                $q->where(function (Builder $qq) use ($like) {
                    $qq->where('name', 'like', $like)
                        ->orWhere('display_name', 'like', $like)
                        ->orWhere('vendor_number', 'like', $like)
                        ->orWhere('phone_number', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('id');

        return VendorResource::collection($query->paginate($perPage));
    }

    /**
     * POST /api/vendors
     */
    public function store(StoreVendorRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // tenancy & audit
        $data['company_id'] = $user->company_id;
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;

        $vendor = Vendor::create($data);

        // 🔥 CREATE OPENING BALANCE JOURNAL (Vendor)
        if ($vendor->opening_balance > 0 && in_array($vendor->opening_balance_type, ['debit', 'credit'])) {
            $this->openingService->createOpeningBalanceJournal($vendor);
        }

        return new VendorResource($vendor);
    }

    /**
     * GET /api/vendors/{vendor}
     */
    public function show(Vendor $vendor)
    {
        $this->authorizeCompany($vendor);
        return new VendorResource($vendor);
    }

    /**
     * PUT/PATCH /api/vendors/{vendor}
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $this->authorizeCompany($vendor);

        $data = $request->validated();

        // Immutable fields prevent changes
        unset($data['opening_balance'], $data['opening_balance_date'], $data['vendor_number']);

        $vendor->fill($data)->save();

        return new VendorResource($vendor);
    }

    /**
     * DELETE /api/vendors/{vendor}
     */
    public function destroy(Request $request, Vendor $vendor)
    {
        $this->authorizeCompany($vendor);
        $vendor->delete();

        return response()->json(['status' => true]);
    }

    private function authorizeCompany(Vendor $vendor): void
    {
        $user = Auth::user();
        abort_unless($vendor->company_id === $user->company_id, 403, 'Forbidden');
    }
}

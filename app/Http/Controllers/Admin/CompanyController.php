<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth'); // add gates/policies if you use them
    // }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $companies = Company::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('industry_type', 'like', "%{$term}%")
                        ->orWhere('registration_no', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $filters = [
            'q'      => $request->q,
            'status' => $request->input('status', 'all'),
            'per_page' => $perPage,
        ];

        return view('admin.companies.index', compact('companies', 'filters'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255', 'unique:companies,name'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:255'],
            'address'         => ['nullable', 'string'],
            'logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'industry_type'   => ['nullable', 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'website'         => ['nullable', 'url', 'max:255'],
            'status'          => ['required', 'in:active,inactive,suspended'],
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $data['created_by'] = Auth::id();

        $company = Company::create($data);

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Company created successfully.');
    }

    public function show(Company $company)
    {
        return view('admin.companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    /**
     * PUT/PATCH /admin/companies/{company}
     */
    public function update(Request $request, Company $company)
    {
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255', 'unique:companies,name,' . $company->id],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:255'],
            'address'         => ['nullable', 'string'],
            'logo'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,avif', 'max:2048'],
            'remove_logo'     => ['nullable', 'boolean'],
            'industry_type'   => ['nullable', 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:255'],
            'website'         => ['nullable', 'url', 'max:255'],
            'status'          => ['required', 'in:active,inactive,suspended'],
        ]);

        // Remove existing logo if requested
        if ($request->boolean('remove_logo') && $company->logo) {
            Storage::disk('public')->delete($company->logo);
            $data['logo'] = null;
        }

        // Replace logo if a new file is uploaded
        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('companies/logos', 'public');
        }

        $data['updated_by'] = Auth::id();

        $company->update($data);

        return redirect()
            ->route('companies.edit', $company)
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company)
    {
        // track who deleted
        $company->update(['deleted_by' => Auth::id()]);

        // delete logo file (optional: keep file if you want to restore on soft-delete)
        // Storage::disk('public')->delete($company->logo);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('success', 'Company deleted successfully.');
    }

    public function toggleStatus(Company $company)
    {
        $next = match ($company->status) {
            'active'   => 'inactive',
            'inactive' => 'active',
            default    => 'active',
        };

        $company->update([
            'status'     => $next,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', "Status changed to {$next}.");
    }
}

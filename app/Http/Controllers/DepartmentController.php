<?php

namespace App\Http\Controllers;

use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService,
    ) {}

    public function index(Request $request): View
    {
        return view('departments.index', [
            'departments' => $this->departmentService->paginate(
                $request->only('search', 'status', 'company_id'),
            ),
            'companies' => $this->departmentService->companyFilterOptions(),
        ]);
    }

    public function create(): View
    {
        return view('departments.create', [
            'companies' => $this->departmentService->companyOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $department = $this->departmentService->create($request->validated());

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department): View
    {
        $department->load('company');

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department): View
    {
        return view('departments.edit', [
            'department' => $department->load('company'),
            'companies' => $this->departmentService->companyOptions($department->company_id),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department = $this->departmentService->update($department, $request->validated());

        return redirect()
            ->route('departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $this->departmentService->delete($department);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}

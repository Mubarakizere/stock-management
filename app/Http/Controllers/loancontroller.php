<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    public function index()
    {
        $loans = Loan::with('customer', 'supplier')->latest()->get();
        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        $customers = Customer::all();
        $suppliers = Supplier::all();
        return view('loans.create', compact('customers', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:given,taken',
            'amount' => 'required|numeric|min:1',
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:loan_date',
            'status' => 'required|in:pending,paid',
        ]);

        Loan::create($request->all());

        return redirect()->route('loans.index')->with('success', 'Loan recorded successfully.');
    }

    public function show(Loan $loan)
    {
        $loan->load('customer', 'supplier');
        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $customers = Customer::all();
        $suppliers = Supplier::all();
        return view('loans.edit', compact('loan', 'customers', 'suppliers'));
    }

    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'type' => 'required|in:given,taken',
            'amount' => 'required|numeric|min:1',
            'loan_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:loan_date',
            'status' => 'required|in:pending,paid',
        ]);

        $loan->update($request->all());

        return redirect()->route('loans.index')->with('success', 'Loan updated successfully.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();
        return redirect()->route('loans.index')->with('success', 'Loan deleted successfully.');
    }
}

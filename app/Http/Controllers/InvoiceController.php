<?php
namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Models\InvoiceItem;
use App\Models\Item;
use PDF;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Invoice::with('customer')->latest()->get();
            return DataTables::of($data)
                ->addColumn('customer', fn($row) => $row->customer->name)
                ->addColumn('action', function ($row) {
                    return '
                        <a href="' . route('invoices.edit', $row->id) . '" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>

                        <form action="' . route('invoices.destroy', $row->id) . '" method="POST" style="display:inline-block;">
                            ' . csrf_field() . method_field("DELETE") . '
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>

                        <a href="' . route('invoices.print', $row->id) . '" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>

                        <a href="' . route('invoices.pdf', $row->id) . '" class="btn btn-sm btn-outline-danger" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    ';

                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('invoices.index');
    }

    public function create()
    {
        $customers = Customer::all();
        $items = Item::all();
        return view('invoices.create', compact('customers', 'items'));
    }

    

   public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'date_of_supply' => 'required|date',
            'time_of_supply' => 'required',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.unit_price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.value_of_goods' => 'required|numeric',
            'items.*.sale_tax_rate' => 'required|numeric',
            'items.*.amount_of_saleTax' => 'required|numeric',
            'items.*.further_tax' => 'required|numeric',
            'items.*.total' => 'required|numeric',
        ]);

        // Stock validation
        foreach ($request->items as $i => $itemData) {
            $item = Item::find($itemData['item_id']);
            if (!$item || $item->quantity < $itemData['quantity']) {
                return back()->withInput()->with('items', Item::all())->withErrors([
                    "items.$i.quantity" => "Item '{$item->name}' has only {$item->quantity} in stock.",
                ]);
            }
        }

        $invoice = Invoice::create([
            'customer_id' => $request->customer_id,
            'invoice_no' => 'INV-' . now()->format('YmdHis') . rand(100, 999),
            'date_of_supply' => $request->date_of_supply,
            'time_of_supply' => $request->time_of_supply,
        ]);

        foreach ($request->items as $itemData) {
            $invoice->items()->create($itemData);

            // Subtract from stock
            $item = Item::find($itemData['item_id']);
            $item->quantity -= $itemData['quantity'];
            $item->save();
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }


    public function edit(Invoice $invoice)
    {
        $customers = Customer::all();
        $items = Item::all();
        $invoice->load('items'); // eager load
        return view('invoices.edit', compact('invoice', 'customers', 'items'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $request->validate([
            'customer_id' => 'required',
            'date_of_supply' => 'required|date',
            'time_of_supply' => 'required',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.unit_price' => 'required|numeric',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.value_of_goods' => 'required|numeric',
            'items.*.sale_tax_rate' => 'required|numeric',
            'items.*.amount_of_saleTax' => 'required|numeric',
            'items.*.further_tax' => 'required|numeric',
            'items.*.total' => 'required|numeric',
        ]);

        // Restore previous stock
        foreach ($invoice->items as $oldItem) {
            $item = Item::find($oldItem->item_id);
            if ($item) {
                $item->quantity += $oldItem->quantity;
                $item->save();
            }
        }

        $invoice->update([
            'customer_id' => $request->customer_id,
            'date_of_supply' => $request->date_of_supply,
            'time_of_supply' => $request->time_of_supply,
        ]);

        $invoice->items()->delete(); // remove all old items

        // Check and apply new stock
        foreach ($request->items as $i => $itemData) {
            $item = Item::find($itemData['item_id']);
            if (!$item || $item->quantity < $itemData['quantity']) {
                return back()->withInput()->with('items', Item::all())->withErrors([
                    "items.$i.quantity" => "Item '{$item->name}' has only {$item->quantity} in stock.",
                ]);
            }

            $invoice->items()->create($itemData);

            $item->quantity -= $itemData['quantity'];
            $item->save();
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }



    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('customer', 'items.item');
        return view('invoices.print', compact('invoice'));
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load('customer', 'items.item');
        $pdf = PDF::loadView('invoices.print', compact('invoice'));
        return $pdf->download('invoice_' . $invoice->invoice_no . '.pdf');
    }
}

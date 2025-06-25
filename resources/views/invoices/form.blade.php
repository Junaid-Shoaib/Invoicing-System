<div class="mb-3">
    <label>Customer *</label>
    <select name="customer_id" class="form-control" required>
        <option value="">-- Select Customer --</option>
        @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ old('customer_id', $invoice->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                {{ $customer->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="mb-3">
    <label>Date of Supply *</label>
    <input type="date" name="date_of_supply" class="form-control" value="{{ old('date_of_supply', $invoice->date_of_supply ?? '') }}" required>
</div>

<div class="mb-3">
    <label>Time of Supply *</label>
    <input type="time" name="time_of_supply" class="form-control" value="{{ old('time_of_supply', $invoice->time_of_supply ?? '') }}" required>
</div>

<h5 class="mt-4 mb-2">Invoice Items</h5>
<table class="table table-bordered" id="items-table">
    <thead class="table-light">
        <tr>
            <th>Item</th>
            <th>Unit Price</th>
            <th>Qty</th>
            <th>Value</th>
            <th>ST %</th>
            <th>ST Amount</th>
            <th>Further Tax</th>
            <th>Total</th>
            <th><button type="button" class="btn btn-sm btn-success" id="add-row"><i class="fas fa-plus"></i></button></th>
        </tr>
    </thead>
    <tbody>
        {{-- Old data on validation fail --}}
        @php
            $invoiceItems = old('items', isset($invoice) ? $invoice->items : []);
        @endphp

        @foreach($invoiceItems as $i => $itemRow)
        <tr>
            <td>
                <select name="items[{{ $i }}][item_id]" class="form-control item-select" required>
                    <option value="">Select Item</option>
                    @foreach($items as $item)
                        @php
                            $selectedId = is_object($itemRow) ? $itemRow->item_id : ($itemRow['item_id'] ?? null);
                            $isSelected = $selectedId == $item->id ? 'selected' : '';
                            $itemName = $item->name . ' (Stock: ' . $item->quantity . ')';
                        @endphp
                        <option value="{{ $item->id }}"
                                data-price="{{ $item->unit_price }}"
                                data-st="{{ $item->st_rate }}"
                                {{ $isSelected }}>
                            {{ $itemName }}
                        </option>
                    @endforeach
                </select>

            </td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][unit_price]" class="form-control unit-price" value="{{ is_object($itemRow) ? $itemRow->unit_price : $itemRow['unit_price'] }}" required></td>
            <td><input type="number" name="items[{{ $i }}][quantity]" class="form-control quantity" value="{{ is_object($itemRow) ? $itemRow->quantity : $itemRow['quantity'] }}" required></td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][value_of_goods]" class="form-control value" value="{{ is_object($itemRow) ? $itemRow->value_of_goods : $itemRow['value_of_goods'] }}" readonly></td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][sale_tax_rate]" class="form-control st-rate" value="{{ is_object($itemRow) ? $itemRow->sale_tax_rate : $itemRow['sale_tax_rate'] }}" readonly></td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][amount_of_saleTax]" class="form-control st-amount" value="{{ is_object($itemRow) ? $itemRow->amount_of_saleTax : $itemRow['amount_of_saleTax'] }}" readonly></td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][further_tax]" class="form-control ft" value="{{ is_object($itemRow) ? $itemRow->further_tax : $itemRow['further_tax'] }}" required></td>
            <td><input type="number" step="0.01" name="items[{{ $i }}][total]" class="form-control total" value="{{ is_object($itemRow) ? $itemRow->total : $itemRow['total'] }}" readonly></td>
            <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
        </tr>
        @endforeach

    </tbody>
</table>@push('scripts')
<script>
let rowIndex = {{ count($invoiceItems) }};

// Add new row
$('#add-row').on('click', function () {
    const items = @json($items);
    let options = `<option value="">Select</option>`;
    items.forEach(item => {
        options += `<option value="${item.id}" data-price="${item.unit_price}" data-st="${item.st_rate}" data-stock="${item.quantity}">
                        ${item.name} (Stock: ${item.quantity})
                    </option>`;
    });

    let row = `
    <tr>
        <td><select name="items[${rowIndex}][item_id]" class="form-control item-select" required>${options}</select></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][unit_price]" class="form-control unit-price" required></td>
        <td><input type="number" name="items[${rowIndex}][quantity]" class="form-control quantity" required></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][value_of_goods]" class="form-control value" readonly></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][sale_tax_rate]" class="form-control st-rate" readonly></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][amount_of_saleTax]" class="form-control st-amount" readonly></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][further_tax]" class="form-control ft" value="0"></td>
        <td><input type="number" step="0.01" name="items[${rowIndex}][total]" class="form-control total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>
    `;

    $('#items-table tbody').append(row);
    rowIndex++;
});

// Remove row
$(document).on('click', '.remove-row', function () {
    $(this).closest('tr').remove();
});

// Handle item select
$(document).on('change', '.item-select', function () {
    const selected = $(this).find('option:selected');
    const row = $(this).closest('tr');

    row.find('.unit-price').val(selected.data('price') || 0);
    row.find('.st-rate').val(selected.data('st') || 0);

    const stock = selected.data('stock') || 0;
    const qtyInput = row.find('.quantity');
    qtyInput.attr('max', stock);
    qtyInput.attr('title', 'Max: ' + stock);

    if (parseInt(qtyInput.val()) > stock) {
        alert('Only ' + stock + ' units in stock.');
        qtyInput.val(stock);
    }

    calculateRow(row);
});

// Handle qty/price change
$(document).on('input', '.unit-price, .quantity, .ft', function () {
    const row = $(this).closest('tr');
    const qty = parseInt(row.find('.quantity').val());
    const max = parseInt(row.find('.quantity').attr('max')) || 0;

    if (qty > max) {
        alert('You cannot enter more than ' + max + ' units.');
        row.find('.quantity').val(max);
    }

    calculateRow(row);
});

// Row calculation
function calculateRow(row) {
    const price = parseFloat(row.find('.unit-price').val()) || 0;
    const qty = parseInt(row.find('.quantity').val()) || 0;
    const rate = parseFloat(row.find('.st-rate').val()) || 0;
    const ft = parseFloat(row.find('.ft').val()) || 0;

    const value = price * qty;
    const tax = value * rate / 100;
    const total = value + tax + ft;

    row.find('.value').val(value.toFixed(2));
    row.find('.st-amount').val(tax.toFixed(2));
    row.find('.total').val(total.toFixed(2));
}
</script>
@endpush

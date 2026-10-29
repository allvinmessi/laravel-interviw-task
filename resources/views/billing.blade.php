<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Billing — New Order | Mallow Technologies</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased min-h-screen">

    <div x-data="billingSystem()" x-init="init()" class="max-w-7xl mx-auto p-4 md:p-8">
        <!-- Header -->
        <header class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="bg-indigo-600 text-white p-2.5 rounded-lg shadow-sm">
                        <i class="fa-solid fa-cash-register text-xl"></i>
                    </span>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Store Billing <span class="text-indigo-600 font-normal">— New Order</span></h1>
                        <p class="text-xs text-slate-500">Retail counter inventory sync & order management system</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showManageCatalogModal = true" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition border border-slate-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-boxes-stacked"></i> Manage Catalog
                </button>
                <button @click="openNewProductModal()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-plus"></i> + Add Product
                </button>
                <button @click="showHistoryModal = true" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-lg transition border border-slate-300 flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i> Order History
                </button>
                <div class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded-full flex items-center gap-1.5 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    API Connected
                </div>
            </div>
        </header>

        <!-- Main Workspace Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left / Center Column: Order Creation (8 Cols) -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- Customer Details Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user text-indigo-600"></i> Customer Information
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Email <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <input 
                                    type="email" 
                                    x-model="customer.email" 
                                    @input.debounce.400ms="lookupCustomer()" 
                                    placeholder="e.g. thomas@example.com"
                                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                                <div x-show="isLookingUp" class="absolute right-3 top-2.5 text-slate-400 text-xs">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Existing customers will auto-fill name</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
                            <input 
                                type="text" 
                                x-model="customer.name" 
                                placeholder="auto-filled if email exists" 
                                class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"
                                :class="customerFound ? 'bg-indigo-50/50 border-indigo-200 text-indigo-950 font-medium' : ''">
                            <span x-show="customerFound" class="text-xs text-indigo-600 font-medium mt-1 inline-block">
                                <i class="fa-solid fa-circle-check"></i> Found existing customer
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Product Lines Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 flex items-center gap-2">
                            <i class="fa-solid fa-cart-shopping text-indigo-600"></i> Order Items
                        </h2>
                        <button @click="addProductLine()" type="button" class="text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold px-3 py-1.5 rounded-lg border border-indigo-200 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-plus"></i> Add Line
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-600 uppercase">
                                <tr>
                                    <th class="py-3 px-3">Product</th>
                                    <th class="py-3 px-2 w-24 text-center">Qty</th>
                                    <th class="py-3 px-3 w-28 text-right">Price</th>
                                    <th class="py-3 px-3 w-24 text-center">Tax %</th>
                                    <th class="py-3 px-3 w-28 text-right">Line Total</th>
                                    <th class="py-3 px-2 w-10 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(line, index) in orderLines" :key="index">
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <!-- Product select -->
                                        <td class="py-2.5 px-3">
                                            <select 
                                                x-model.number="line.product_id" 
                                                @change="onProductSelect(line)"
                                                class="w-full text-sm border border-slate-300 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                                <option value="">-- select product --</option>
                                                <template x-for="p in catalog" :key="p.id">
                                                    <option :value="p.id" :disabled="p.stock <= 0" x-text="`${p.name} (Stock: ${p.stock}) - ₹${p.price}`"></option>
                                                </template>
                                            </select>
                                            <div x-show="line.stock !== null" class="text-[11px] text-slate-400 mt-0.5 flex items-center justify-between">
                                                <div class="flex gap-2">
                                                    <span>Code: <b class="font-mono text-slate-600" x-text="line.code"></b></span>
                                                    <span>Avail: <b :class="line.stock <= 5 ? 'text-amber-600' : 'text-slate-600'" x-text="line.stock"></b></span>
                                                </div>
                                                <button type="button" @click="openEditProductModal(line.product_id)" class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px] flex items-center gap-1 hover:underline">
                                                    <i class="fa-solid fa-pen-to-square"></i> Edit
                                                </button>
                                            </div>
                                        </td>
                                        <!-- Qty input -->
                                        <td class="py-2.5 px-2">
                                            <input 
                                                type="number" 
                                                min="1" 
                                                :max="line.stock || 999"
                                                x-model.number="line.quantity" 
                                                @input="recalculateTotals()"
                                                class="w-full text-center border border-slate-300 rounded-md py-1.5 text-sm focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                                        </td>
                                        <!-- Price -->
                                        <td class="py-2.5 px-3 text-right font-medium text-slate-700">
                                            <span x-text="line.price ? '₹' + line.price.toFixed(2) : '-'"></span>
                                        </td>
                                        <!-- Tax % -->
                                        <td class="py-2.5 px-3 text-center text-xs text-slate-500 font-mono">
                                            <span x-text="line.tax_percentage ? line.tax_percentage + '%' : '0%'"></span>
                                        </td>
                                        <!-- Line total -->
                                        <td class="py-2.5 px-3 text-right font-semibold text-slate-900">
                                            <span x-text="line.total ? '₹' + line.total.toFixed(2) : '₹0.00'"></span>
                                        </td>
                                        <!-- Remove line -->
                                        <td class="py-2.5 px-2 text-center">
                                            <button 
                                                type="button" 
                                                @click="removeProductLine(index)" 
                                                :disabled="orderLines.length <= 1"
                                                class="text-slate-400 hover:text-red-600 disabled:opacity-30 disabled:hover:text-slate-400 transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                        <button @click="addProductLine()" type="button" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-plus"></i> + Add Product
                        </button>
                        <span class="text-xs text-slate-500" x-text="`${orderLines.filter(l => l.product_id).length} item(s) selected`"></span>
                    </div>
                </div>

                <!-- Payment & Billing Action Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-indigo-600"></i> Payment & Settlement
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Sub-panel: Amount Given & Denominations -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Amount Given by Customer</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-2.5 text-slate-400 font-semibold">₹</span>
                                    <input 
                                        type="number" 
                                        step="0.5" 
                                        x-model.number="amountGiven" 
                                        @input="calculateChange()"
                                        placeholder="Enter tendered cash" 
                                        class="w-full pl-8 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                </div>
                            </div>

                            <!-- Live Balance to Return Breakdown -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>Balance to Return:</span>
                                    <span class="text-base text-emerald-700 font-bold" x-text="changeToReturn >= 0 ? '₹' + changeToReturn.toFixed(2) : '₹0.00'"></span>
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono" x-show="changeBreakdown">
                                    <i class="fa-solid fa-money-bill-wave text-emerald-600 mr-1"></i>
                                    <span x-text="changeBreakdown"></span>
                                </div>
                                <div class="text-[11px] text-amber-600" x-show="amountGiven !== null && amountGiven < grandTotal && grandTotal > 0">
                                    <i class="fa-solid fa-circle-exclamation mr-1"></i> Tendered amount is less than grand total
                                </div>
                            </div>
                        </div>

                        <!-- Right Sub-panel: Calculation Breakdown & Action -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-5 flex flex-col justify-between">
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-slate-600">
                                    <span>Subtotal</span>
                                    <span class="font-medium" x-text="'₹' + subtotal.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>Tax</span>
                                    <span class="font-medium" x-text="'₹' + taxTotal.toFixed(2)"></span>
                                </div>
                                <div class="border-t border-slate-200 pt-2 flex justify-between text-slate-900 font-bold text-lg">
                                    <span>Grand Total</span>
                                    <span class="text-indigo-700" x-text="'₹' + grandTotal.toFixed(2)"></span>
                                </div>
                            </div>

                            <button 
                                @click="generateBill()" 
                                :disabled="isSubmitting || grandTotal <= 0"
                                class="mt-6 w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg shadow transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="!isSubmitting">
                                    <span><i class="fa-solid fa-receipt mr-1"></i> Generate Bill</span>
                                </template>
                                <template x-if="isSubmitting">
                                    <span><i class="fa-solid fa-spinner fa-spin mr-1"></i> Processing Order...</span>
                                </template>
                            </button>
                            <p class="text-[11px] text-center text-slate-400 mt-2">
                                <i class="fa-solid fa-envelope text-indigo-400"></i> Dispatches queued order confirmation async
                            </p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Low Stock Alerts Sidebar (4 Cols) -->
            <div class="lg:col-span-4 space-y-6">
                
                <div class="bg-amber-50/60 border border-amber-300 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3 border-b border-amber-200/60 pb-2">
                        <h3 class="text-sm font-bold text-amber-900 flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Low Stock Alert
                        </h3>
                        <button @click="loadLowStock()" class="text-xs text-amber-700 hover:text-amber-900 font-medium">
                            <i class="fa-solid fa-arrows-rotate" :class="isRefreshingLowStock ? 'fa-spin' : ''"></i> Refresh
                        </button>
                    </div>

                    <p class="text-xs text-amber-800 mb-3">
                        Products with inventory at or below threshold (<span class="font-semibold font-mono" x-text="lowStockThreshold"></span> units):
                    </p>

                    <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                        <template x-for="item in lowStockItems" :key="item.id">
                            <div @click="openEditProductModal(item.id)" class="bg-white border border-amber-200 rounded-lg p-3 flex items-center justify-between shadow-2xs hover:border-amber-400 hover:bg-amber-50/20 cursor-pointer transition" title="Click to update stock or details">
                                <div>
                                    <div class="text-xs font-bold text-slate-800" x-text="item.name"></div>
                                    <div class="text-[11px] text-slate-400 font-mono" x-text="item.code"></div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold"
                                          :class="item.stock <= 2 ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
                                          x-text="`${item.stock} left`"></span>
                                    <div class="text-[10px] text-indigo-600 font-semibold mt-0.5 flex items-center justify-end gap-1"><i class="fa-solid fa-pen-to-square text-[9px]"></i> Edit</div>
                                </div>
                            </div>
                        </template>

                        <div x-show="lowStockItems.length === 0" class="text-center py-6 text-xs text-amber-700">
                            <i class="fa-solid fa-check-circle text-emerald-500 text-base mb-1 block"></i>
                            All catalog stock levels healthy.
                        </div>
                    </div>
                </div>

                <!-- System Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 text-xs text-slate-600 space-y-3">
                    <h4 class="font-bold text-slate-800 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-indigo-600"></i> Senior Concurrency Safety
                    </h4>
                    <p class="leading-relaxed">
                        Order transactions utilize InnoDB <b>pessimistic row locking</b> (<code class="bg-slate-100 px-1 py-0.5 rounded text-indigo-700">SELECT ... FOR UPDATE</code>) with ordered lock sequencing. Simultaneous requests cannot oversell or cause deadlocks.
                    </p>
                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">Queue Driver:</span>
                        <span class="font-mono font-semibold bg-slate-100 px-2 py-0.5 rounded text-slate-700">database</span>
                    </div>
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-slate-400">Mail Simulation:</span>
                        <span class="font-mono font-semibold bg-slate-100 px-2 py-0.5 rounded text-slate-700">SendOrderConfirmationEmailJob</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bill / Invoice Modal -->
        <div x-show="showInvoiceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showInvoiceModal = false" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 transform transition-all">
                
                <div id="printable-receipt" class="p-6">
                    <!-- Receipt Header -->
                    <div class="text-center border-b border-dashed border-slate-300 pb-4 mb-4">
                        <div class="inline-block p-2 bg-indigo-50 text-indigo-600 rounded-full mb-2">
                            <i class="fa-solid fa-store text-xl"></i>
                        </div>
                        <h2 class="text-xl font-black text-slate-900 tracking-tight">MALLOW RETAIL STORE</h2>
                        <p class="text-xs text-slate-500">Retail Counter Order & Tax Invoice</p>
                        <div class="mt-2 text-xs font-mono font-semibold text-indigo-600" x-text="completedOrder?.order_number"></div>
                        <div class="text-[11px] text-slate-400" x-text="completedOrder?.created_at ? new Date(completedOrder.created_at).toLocaleString() : ''"></div>
                    </div>

                    <!-- Customer Info in Receipt -->
                    <div class="text-xs mb-4 pb-3 border-b border-slate-100 flex justify-between">
                        <div>
                            <span class="text-slate-400">Customer:</span>
                            <span class="font-bold text-slate-800 ml-1" x-text="completedOrder?.customer?.name"></span>
                        </div>
                        <div class="text-slate-500 font-mono" x-text="completedOrder?.customer?.email"></div>
                    </div>

                    <!-- Receipt Items -->
                    <div class="space-y-2 mb-4">
                        <template x-for="item in completedOrder?.items" :key="item.id">
                            <div class="flex justify-between items-start text-xs">
                                <div>
                                    <div class="font-semibold text-slate-800" x-text="item.product_name"></div>
                                    <div class="text-[10px] text-slate-400">
                                        <span x-text="`${item.quantity} x ₹${item.unit_price}`"></span>
                                        <span class="text-slate-300">|</span>
                                        <span x-text="`Tax: ${item.tax_percentage}%`"></span>
                                    </div>
                                </div>
                                <div class="font-bold text-slate-800" x-text="'₹' + item.total.toFixed(2)"></div>
                            </div>
                        </template>
                    </div>

                    <!-- Receipt Totals -->
                    <div class="border-t border-dashed border-slate-300 pt-3 space-y-1.5 text-xs">
                        <div class="flex justify-between text-slate-600">
                            <span>Subtotal:</span>
                            <span class="font-mono" x-text="completedOrder ? '₹' + completedOrder.subtotal.toFixed(2) : ''"></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Tax Amount:</span>
                            <span class="font-mono" x-text="completedOrder ? '₹' + completedOrder.tax_amount.toFixed(2) : ''"></span>
                        </div>
                        <div class="flex justify-between text-slate-900 font-bold text-sm pt-1 border-t border-slate-200">
                            <span>Grand Total:</span>
                            <span class="font-mono text-indigo-700" x-text="completedOrder ? '₹' + completedOrder.grand_total.toFixed(2) : ''"></span>
                        </div>
                        <template x-if="completedOrder?.amount_given">
                            <div>
                                <div class="flex justify-between text-slate-600 pt-1">
                                    <span>Cash Tendered:</span>
                                    <span class="font-mono" x-text="'₹' + completedOrder.amount_given.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between text-emerald-700 font-bold">
                                    <span>Change Returned:</span>
                                    <span class="font-mono" x-text="'₹' + completedOrder.change_returned.toFixed(2)"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Receipt Footer -->
                    <div class="text-center text-[11px] text-slate-400 mt-5 pt-3 border-t border-slate-100">
                        <p class="font-semibold text-emerald-600 mb-0.5">
                            <i class="fa-solid fa-paper-plane mr-1"></i> Order confirmation email queued to customer!
                        </p>
                        <p>Thank you for shopping with us.</p>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="bg-slate-50 px-6 py-4 flex gap-3 border-t border-slate-100">
                    <button @click="window.print()" class="flex-1 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-lg transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-print"></i> Print Receipt
                    </button>
                    <button @click="resetForm(); showInvoiceModal = false;" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-plus"></i> New Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Customer Order History Modal -->
        <div x-show="showHistoryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showHistoryModal = false" class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-indigo-600"></i> Customer Order History
                    </h3>
                    <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Lookup Input -->
                <div class="flex gap-2 mb-4">
                    <input 
                        type="email" 
                        x-model="historyEmail" 
                        @keyup.enter="fetchCustomerHistory()"
                        placeholder="Enter customer email (e.g. thomas@example.com)" 
                        class="flex-1 px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <button @click="fetchCustomerHistory()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition flex items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass" :class="isFetchingHistory ? 'fa-spin' : ''"></i> Search
                    </button>
                </div>

                <!-- History Results -->
                <div class="space-y-3 max-h-[400px] overflow-y-auto">
                    <template x-for="ord in customerHistory" :key="ord.id">
                        <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 hover:bg-slate-50 transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-mono text-xs font-bold text-indigo-700" x-text="ord.order_number"></span>
                                <span class="text-xs text-slate-400" x-text="new Date(ord.created_at).toLocaleString()"></span>
                            </div>
                            <div class="space-y-1 mb-2">
                                <template x-for="it in ord.items" :key="it.id">
                                    <div class="text-xs text-slate-600 flex justify-between">
                                        <span x-text="`${it.quantity}x ${it.product_name}`"></span>
                                        <span class="font-mono font-medium" x-text="'₹' + it.total.toFixed(2)"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="border-t border-slate-200 pt-2 flex justify-between text-xs font-bold text-slate-900">
                                <span>Grand Total:</span>
                                <span class="text-indigo-600" x-text="'₹' + ord.grand_total.toFixed(2)"></span>
                            </div>
                        </div>
                    </template>

                    <div x-show="customerHistory.length === 0 && !isFetchingHistory && historySearched" class="text-center py-8 text-xs text-slate-400">
                        No previous orders found for this email.
                    </div>
                </div>
            </div>
        </div>

        <!-- New Product Catalog Modal -->
        <div x-show="showNewProductModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showNewProductModal = false" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-box-open text-indigo-600"></i> Add New Product to Catalog
                    </h3>
                    <button @click="showNewProductModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="saveNewProduct()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="newProduct.name" required placeholder="e.g. Britannia Good Day" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code (SKU) <span class="text-red-500">*</span></label>
                            <input type="text" x-model="newProduct.code" required placeholder="e.g. BISC-002" class="w-full px-3 py-2 text-sm uppercase font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Stock on Hand <span class="text-red-500">*</span></label>
                            <input type="number" min="0" x-model.number="newProduct.stock" required placeholder="e.g. 25" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Price per Unit (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" x-model.number="newProduct.price" required placeholder="e.g. 35.00" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tax Percentage (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" x-model.number="newProduct.tax_percentage" required placeholder="e.g. 8.00" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Low-Stock Alert Threshold (optional)</label>
                        <input type="number" min="0" x-model.number="newProduct.low_stock_threshold" placeholder="Leave empty for global default (5)" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Products below this count appear in the right-side alert.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" @click="showNewProductModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 text-sm hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" :disabled="isSavingProduct" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-1.5 disabled:opacity-50">
                            <span x-show="!isSavingProduct"><i class="fa-solid fa-save mr-1"></i> Save Product</span>
                            <span x-show="isSavingProduct"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Manage Catalog Modal -->
        <div x-show="showManageCatalogModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showManageCatalogModal = false" class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full p-6 border border-slate-100 max-h-[90vh] flex flex-col">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-indigo-600"></i> Manage Catalog & Inventory
                        </h3>
                        <p class="text-xs text-slate-500">Update stock levels, unit prices, GST tax percentages, and product details</p>
                    </div>
                    <button @click="showManageCatalogModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Search Filter & Actions -->
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="relative flex-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        <input 
                            type="text" 
                            x-model="catalogSearch" 
                            placeholder="Search by product name or code (e.g. Toothpaste, BISC-001)..." 
                            class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <button @click="openNewProductModal()" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1.5 shrink-0">
                        <i class="fa-solid fa-plus"></i> New Product
                    </button>
                </div>

                <!-- Products Catalog Table -->
                <div class="overflow-y-auto flex-1 border border-slate-200 rounded-xl">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 font-semibold text-slate-600 uppercase tracking-wider sticky top-0">
                            <tr>
                                <th class="py-2.5 px-3">Product Name</th>
                                <th class="py-2.5 px-3 font-mono">Code / SKU</th>
                                <th class="py-2.5 px-3 text-right">Price (₹)</th>
                                <th class="py-2.5 px-3 text-center">GST (%)</th>
                                <th class="py-2.5 px-3 text-center">Stock</th>
                                <th class="py-2.5 px-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="p in filteredCatalog" :key="p.id">
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="py-2.5 px-3 font-medium text-slate-900" x-text="p.name"></td>
                                    <td class="py-2.5 px-3 font-mono text-slate-500" x-text="p.code"></td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-800" x-text="'₹' + Number(p.price).toFixed(2)"></td>
                                    <td class="py-2.5 px-3 text-center font-mono text-slate-600" x-text="p.tax_percentage + '%'"></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold"
                                              :class="p.stock <= 5 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-800'"
                                              x-text="p.stock"></span>
                                    </td>
                                    <td class="py-2.5 px-3 text-center">
                                        <button @click="openEditProductModal(p.id)" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold rounded text-xs transition flex items-center gap-1 mx-auto">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredCatalog.length === 0">
                                <td colspan="6" class="text-center py-8 text-slate-400">
                                    No products found matching "<span x-text="catalogSearch"></span>".
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center text-xs text-slate-500">
                    <span x-text="`Showing ${filteredCatalog.length} of ${catalog.length} product(s)`"></span>
                    <button @click="showManageCatalogModal = false" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div x-show="showEditProductModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="showEditProductModal = false" class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-indigo-600"></i> Edit Product
                        </h3>
                        <p class="text-xs text-slate-500" x-text="`Update stock, GST, and pricing for ID #${editProduct.id}`"></p>
                    </div>
                    <button @click="showEditProductModal = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="saveProductUpdate()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="editProduct.name" required placeholder="e.g. Colgate Toothpaste" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Product Code (SKU) <span class="text-red-500">*</span></label>
                            <input type="text" x-model="editProduct.code" required placeholder="e.g. TOOTH-001" class="w-full px-3 py-2 text-sm uppercase font-mono border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <p class="text-[10px] text-slate-400 mt-0.5">Strict duplicate check enforced</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Stock on Hand <span class="text-red-500">*</span></label>
                            <input type="number" min="0" x-model.number="editProduct.stock" required placeholder="e.g. 20" class="w-full px-3 py-2 text-sm font-semibold text-slate-900 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <p class="text-[10px] text-indigo-600 mt-0.5">Change to restock or adjust</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Price per Unit (₹) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0.01" x-model.number="editProduct.price" required placeholder="e.g. 50.00" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">GST / Tax Percentage (%) <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" min="0" max="100" x-model.number="editProduct.tax_percentage" required placeholder="e.g. 18.00" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Low-Stock Alert Threshold</label>
                        <input type="number" min="0" x-model.number="editProduct.low_stock_threshold" placeholder="Leave empty for global default (5)" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <p class="text-[11px] text-slate-400 mt-1">Products below this count trigger the low-stock alert.</p>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex justify-end gap-2">
                        <button type="button" @click="showEditProductModal = false" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 text-sm hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" :disabled="isUpdatingProduct" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-1.5 disabled:opacity-50">
                            <span x-show="!isUpdatingProduct"><i class="fa-solid fa-check mr-1"></i> Update Product</span>
                            <span x-show="isUpdatingProduct"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Updating...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Application Script -->
    <script>
        const API_BASE = "{{ url('api') }}";
        function getApiUrl(endpoint) {
            const clean = endpoint.replace(/^\/+/, '');
            if (API_BASE && API_BASE.startsWith('http')) {
                return `${API_BASE}/${clean}`;
            }
            const base = window.location.pathname.replace(/\/index\.php\/?$/, '').replace(/\/+$/, '');
            return `${window.location.origin}${base}/api/${clean}`;
        }

        function billingSystem() {
            return {
                catalog: [],
                lowStockItems: [],
                lowStockThreshold: 5,
                customer: {
                    name: '',
                    email: ''
                },
                customerFound: false,
                isLookingUp: false,
                orderLines: [
                    { product_id: '', code: '', price: 0, tax_percentage: 0, stock: null, quantity: 1, total: 0 }
                ],
                amountGiven: null,
                changeToReturn: 0,
                changeBreakdown: '',
                subtotal: 0,
                taxTotal: 0,
                grandTotal: 0,
                isSubmitting: false,
                isRefreshingLowStock: false,
                showInvoiceModal: false,
                completedOrder: null,
                showHistoryModal: false,
                historyEmail: '',
                customerHistory: [],
                isFetchingHistory: false,
                historySearched: false,
                showNewProductModal: false,
                isSavingProduct: false,
                newProduct: {
                    name: '',
                    code: '',
                    price: '',
                    tax_percentage: 5,
                    stock: 15,
                    low_stock_threshold: null
                },
                showManageCatalogModal: false,
                catalogSearch: '',
                showEditProductModal: false,
                isUpdatingProduct: false,
                editProduct: {
                    id: null,
                    name: '',
                    code: '',
                    price: '',
                    tax_percentage: '',
                    stock: 0,
                    low_stock_threshold: null
                },

                get filteredCatalog() {
                    const q = (this.catalogSearch || '').toLowerCase().trim();
                    if (!q) return this.catalog;
                    return this.catalog.filter(p => 
                        (p.name && p.name.toLowerCase().includes(q)) ||
                        (p.code && p.code.toLowerCase().includes(q))
                    );
                },

                async init() {
                    await this.loadCatalog();
                    await this.loadLowStock();
                },

                async loadCatalog() {
                    try {
                        const res = await fetch(getApiUrl('products'));
                        const json = await res.json();
                        this.catalog = json.data || [];
                    } catch (e) {
                        console.error('Failed to load products catalog:', e);
                    }
                },

                async loadLowStock() {
                    this.isRefreshingLowStock = true;
                    try {
                        const res = await fetch(getApiUrl('products/low-stock'));
                        const json = await res.json();
                        this.lowStockItems = json.products || [];
                        this.lowStockThreshold = json.effective_threshold || 5;
                    } catch (e) {
                        console.error('Failed to load low stock products:', e);
                    } finally {
                        this.isRefreshingLowStock = false;
                    }
                },

                async lookupCustomer() {
                    const email = (this.customer.email || '').trim();
                    if (!email || !email.includes('@')) {
                        this.customerFound = false;
                        return;
                    }

                    this.isLookingUp = true;
                    try {
                        const res = await fetch(getApiUrl(`customers/lookup?email=${encodeURIComponent(email)}`));
                        const data = await res.json();
                        if (data.exists && data.customer) {
                            this.customer.name = data.customer.name;
                            this.customerFound = true;
                        } else {
                            this.customerFound = false;
                        }
                    } catch (e) {
                        this.customerFound = false;
                    } finally {
                        this.isLookingUp = false;
                    }
                },

                addProductLine() {
                    this.orderLines.push({
                        product_id: '',
                        code: '',
                        price: 0,
                        tax_percentage: 0,
                        stock: null,
                        quantity: 1,
                        total: 0
                    });
                },

                removeProductLine(index) {
                    if (this.orderLines.length > 1) {
                        this.orderLines.splice(index, 1);
                        this.recalculateTotals();
                    }
                },

                onProductSelect(line) {
                    const prod = this.catalog.find(p => p.id === Number(line.product_id));
                    if (prod) {
                        line.code = prod.code;
                        line.price = prod.price;
                        line.tax_percentage = prod.tax_percentage;
                        line.stock = prod.stock;
                        line.quantity = 1;
                    } else {
                        line.code = '';
                        line.price = 0;
                        line.tax_percentage = 0;
                        line.stock = null;
                        line.total = 0;
                    }
                    this.recalculateTotals();
                },

                recalculateTotals() {
                    let sub = 0;
                    let tax = 0;

                    this.orderLines.forEach(line => {
                        if (line.product_id && line.price) {
                            const lineSub = line.price * (line.quantity || 1);
                            const lineTax = lineSub * ((line.tax_percentage || 0) / 100);
                            line.total = lineSub + lineTax;

                            sub += lineSub;
                            tax += lineTax;
                        } else {
                            line.total = 0;
                        }
                    });

                    this.subtotal = Math.round(sub * 100) / 100;
                    this.taxTotal = Math.round(tax * 100) / 100;
                    this.grandTotal = Math.round((sub + tax) * 100) / 100;

                    this.calculateChange();
                },

                calculateChange() {
                    if (this.amountGiven === null || this.amountGiven === '' || this.amountGiven < this.grandTotal) {
                        this.changeToReturn = 0;
                        this.changeBreakdown = '';
                        return;
                    }

                    const change = Math.round((this.amountGiven - this.grandTotal) * 100) / 100;
                    this.changeToReturn = change;

                    // Denomination breakdown algorithm (exact wireframe representation: ₹22.80 -> 1x20 + 1x2 + 1x1)
                    this.changeBreakdown = this.getDenominationBreakdown(change);
                },

                getDenominationBreakdown(amount) {
                    if (amount <= 0) return '';
                    let remaining = Math.round(amount * 100);
                    const denominations = [50000, 20000, 10000, 5000, 2000, 1000, 500, 200, 100, 50];
                    const parts = [];

                    for (const denom of denominations) {
                        if (remaining >= denom) {
                            const count = Math.floor(remaining / denom);
                            remaining = remaining % denom;
                            const label = denom >= 100 ? (denom / 100) : (denom / 100).toFixed(2);
                            parts.push(`${count}x${label}`);
                        }
                    }

                    return parts.length > 0 ? parts.join(' + ') : '₹' + amount.toFixed(2);
                },

                async generateBill() {
                    if (!this.customer.email) {
                        alert('Please enter a customer email.');
                        return;
                    }
                    if (!this.customer.name) {
                        alert('Please enter a customer name.');
                        return;
                    }

                    const validItems = this.orderLines
                        .filter(l => l.product_id && l.quantity > 0)
                        .map(l => ({
                            product_id: Number(l.product_id),
                            quantity: Number(l.quantity)
                        }));

                    if (validItems.length === 0) {
                        alert('Please add at least one valid product line.');
                        return;
                    }

                    this.isSubmitting = true;

                    try {
                        const payload = {
                            customer: {
                                email: this.customer.email,
                                name: this.customer.name
                            },
                            items: validItems,
                            amount_given: this.amountGiven ? Number(this.amountGiven) : null
                        };

                        const res = await fetch(getApiUrl('orders'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json().catch(() => null);

                        if (!res.ok) {
                            const errMsg = (data && data.message) ? data.message : 'Failed to place order.';
                            alert(`Error: ${errMsg}`);
                            return;
                        }

                        this.completedOrder = data.data;
                        this.showInvoiceModal = true;

                        // Refresh catalog & low stock counts
                        await this.loadCatalog();
                        await this.loadLowStock();

                    } catch (e) {
                        alert('A network error occurred while generating bill: ' + (e.message || ''));
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                resetForm() {
                    this.orderLines = [
                        { product_id: '', code: '', price: 0, tax_percentage: 0, stock: null, quantity: 1, total: 0 }
                    ];
                    this.amountGiven = null;
                    this.changeToReturn = 0;
                    this.changeBreakdown = '';
                    this.subtotal = 0;
                    this.taxTotal = 0;
                    this.grandTotal = 0;
                },

                async fetchCustomerHistory() {
                    const email = (this.historyEmail || '').trim();
                    if (!email) return;

                    this.isFetchingHistory = true;
                    this.historySearched = true;
                    try {
                        const res = await fetch(getApiUrl(`customers/${encodeURIComponent(email)}/orders`));
                        const json = await res.json().catch(() => null);
                        this.customerHistory = (json && json.orders) ? json.orders : [];
                    } catch (e) {
                        this.customerHistory = [];
                    } finally {
                        this.isFetchingHistory = false;
                    }
                },

                openNewProductModal() {
                    this.newProduct = {
                        name: '',
                        code: 'SKU-' + Math.floor(1000 + Math.random() * 9000),
                        price: '',
                        tax_percentage: 5.00,
                        stock: 15,
                        low_stock_threshold: null
                    };
                    this.showNewProductModal = true;
                },

                async saveNewProduct() {
                    const name = (this.newProduct.name || '').trim();
                    const code = (this.newProduct.code || '').trim().toUpperCase();
                    const price = parseFloat(this.newProduct.price);
                    const tax = parseFloat(this.newProduct.tax_percentage);
                    const stock = parseInt(this.newProduct.stock, 10);
                    const threshold = this.newProduct.low_stock_threshold ? parseInt(this.newProduct.low_stock_threshold, 10) : null;

                    if (!name || !code || isNaN(price) || isNaN(tax) || isNaN(stock)) {
                        alert('Please fill in all required fields (Name, Code, Price, Tax, and Stock) with valid values.');
                        return;
                    }

                    this.isSavingProduct = true;
                    try {
                        const payload = {
                            name: name,
                            code: code,
                            price: price,
                            tax_percentage: tax,
                            stock: stock,
                            low_stock_threshold: threshold
                        };

                        const res = await fetch(getApiUrl('products'), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json().catch(() => null);

                        if (!res.ok) {
                            let errorMsg = 'Failed to save product.';
                            if (data && data.errors) {
                                errorMsg = Object.values(data.errors).flat().join('\n');
                            } else if (data && data.message) {
                                errorMsg = data.message;
                            } else {
                                errorMsg = `Server responded with status ${res.status}`;
                            }
                            alert(errorMsg);
                            return;
                        }

                        // Refresh catalog & low stock lists
                        await this.loadCatalog();
                        await this.loadLowStock();

                        this.showNewProductModal = false;
                        alert(`Product '${data.data.name}' added to catalog successfully!`);

                    } catch (e) {
                        alert('A network error occurred while saving the product: ' + (e.message || ''));
                    } finally {
                        this.isSavingProduct = false;
                    }
                },

                openEditProductModal(productId) {
                    const prod = this.catalog.find(p => p.id === Number(productId));
                    if (!prod) {
                        alert('Product not found in catalog.');
                        return;
                    }
                    this.editProduct = {
                        id: prod.id,
                        name: prod.name,
                        code: prod.code,
                        price: prod.price,
                        tax_percentage: prod.tax_percentage,
                        stock: prod.stock,
                        low_stock_threshold: prod.low_stock_threshold ?? ''
                    };
                    this.showEditProductModal = true;
                },

                async saveProductUpdate() {
                    const name = (this.editProduct.name || '').trim();
                    const code = (this.editProduct.code || '').trim().toUpperCase();
                    const price = parseFloat(this.editProduct.price);
                    const tax = parseFloat(this.editProduct.tax_percentage);
                    const stock = parseInt(this.editProduct.stock, 10);
                    const threshold = (this.editProduct.low_stock_threshold !== '' && this.editProduct.low_stock_threshold !== null && !isNaN(this.editProduct.low_stock_threshold))
                        ? parseInt(this.editProduct.low_stock_threshold, 10) 
                        : null;

                    if (!name || !code || isNaN(price) || isNaN(tax) || isNaN(stock)) {
                        alert('Please fill in all required fields (Name, Code, Price, GST/Tax, and Stock) with valid values.');
                        return;
                    }

                    this.isUpdatingProduct = true;
                    try {
                        const payload = {
                            name: name,
                            code: code,
                            price: price,
                            tax_percentage: tax,
                            stock: stock,
                            low_stock_threshold: threshold
                        };

                        const res = await fetch(getApiUrl(`products/${this.editProduct.id}`), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json().catch(() => null);

                        if (!res.ok) {
                            let errorMsg = 'Failed to update product.';
                            if (data && data.errors) {
                                errorMsg = Object.values(data.errors).flat().join('\n');
                            } else if (data && data.message) {
                                errorMsg = data.message;
                            } else {
                                errorMsg = `Server responded with HTTP status ${res.status}`;
                            }
                            alert(`Duplicate check / Validation error:\n${errorMsg}`);
                            return;
                        }

                        // Refresh catalog & low stock lists
                        await this.loadCatalog();
                        await this.loadLowStock();

                        // Synchronize any active order line that has this product selected
                        this.orderLines.forEach(line => {
                            if (Number(line.product_id) === this.editProduct.id) {
                                line.code = data.data.code;
                                line.price = data.data.price;
                                line.tax_percentage = data.data.tax_percentage;
                                line.stock = data.data.stock;
                            }
                        });
                        this.recalculateTotals();

                        this.showEditProductModal = false;
                        alert(`Product '${data.data.name}' updated successfully!\nStock: ${data.data.stock} | Price: ₹${data.data.price} | GST: ${data.data.tax_percentage}%`);

                    } catch (e) {
                        alert('Network error while updating product: ' + (e.message || ''));
                    } finally {
                        this.isUpdatingProduct = false;
                    }
                }
            };
        }
    </script>
</body>
</html>

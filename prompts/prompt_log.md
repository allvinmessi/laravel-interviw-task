# AI Prompt Log

This project was built following the Mallow Technologies Take-Home Assignment brief using an AI-assisted workflow. Below is the structured log of developer prompts, instructions, architectural considerations, and design decisions provided during development.

---

### Prompt 1: Initial Requirement Analysis & Task Briefing
```
here do this task here and this my interview so do it clearly and make the db strucure should be clear and correct compalte all the points what ahve give in the pdf and do this correctly importsant note think you as senoir developer and do this
[Attached: Mallow Technologies Laravel Developer Mini Task PDF]
```

#### Senior Architectural Intent:
- Establish a normalized relational schema (`customers`, `products`, `orders`, `order_items`).
- Ensure ACID compliance with database transactions (`DB::transaction`) and pessimistic row locking (`lockForUpdate()`) to strictly avoid race conditions and overselling.
- Apply deadlock prevention by sorting product IDs in ascending numerical order before acquiring exclusive write locks.
- Preserve immutable historical order item snapshots (`unit_price`, `tax_percentage`, `product_name`, `product_code`) to ensure invoice integrity if catalog prices later change.
- Follow Laravel best practices: thin controllers, domain logic encapsulated in dedicated service classes (`OrderService`, `InventoryService`), and Form Requests (`CreateOrderRequest`) for strict request validation.
- Dispatch an asynchronous queued job (`SendOrderConfirmationEmailJob`) on order placement with graceful failure/logging.
- Build a responsive POS counter UI matching the PDF wireframe, featuring customer email auto-fill, dynamic product row entry, live subtotal/tax/grand total calculations, cash tendered denomination breakdown, and low-stock alerts.
- Write a 100% passing automated test suite (PHPUnit/Feature & Unit) covering happy paths, validation edge cases, insufficient stock, and concurrent race-condition scenarios.

---

### Prompt 2: Concurrency & Pessimistic Locking Strategy
```
Ensure the stock check-and-deduct step is safe under concurrent requests — if two orders for the same product arrive at nearly the same time and only one unit is left, exactly one should succeed and the other should fail cleanly (no overselling).
```

#### Senior Architectural Intent:
- Use InnoDB row locking:
  ```php
  $productIds = array_keys($consolidatedItems);
  sort($productIds); // Prevents cross-transaction deadlock cycles

  $products = Product::whereIn('id', $productIds)
      ->lockForUpdate()
      ->get()
      ->keyBy('id');
  ```
- Re-verify stock inside the exclusive transaction lock:
  ```php
  if ($product->stock < $requiredQuantity) {
      throw new InsufficientStockException($product, $requiredQuantity, $product->stock);
  }
  $product->decrement('stock', $requiredQuantity);
  ```
- Throwing `InsufficientStockException` triggers an automatic rollback of the transaction, leaving stock untouched, and renders an informative HTTP 422 JSON response.

---

### Prompt 3: UI POS Counter & Denomination Breakdown Implementation
```
Implement the POS screen matching the wireframe in Page 2 of the PDF:
- Customer email with auto-fill if customer exists
- Product lines with add/remove capability
- Real-time Subtotal, Tax, Grand Total
- Cash given & Balance to return with denomination breakdown (e.g., ₹22.80 -> 1x20 + 1x2 + 1x1)
- Right column: Low stock alerts
- Generate Bill modal with printable receipt
```

#### Senior Architectural Intent:
- Implemented lightweight reactive frontend using Alpine.js and Tailwind CSS.
- Implemented greedy coin/currency change breakdown algorithm covering all Indian Rupee denominations (₹500, ₹200, ₹100, ₹50, ₹20, ₹10, ₹5, ₹2, ₹1, ₹0.50).
- Created printable invoice view and customer order history lookup modal.

---

### Prompt 4: Dynamic Product Catalog Management (Add Product)
```
here how can i add product
```

#### Senior Architectural Intent:
- Built `POST /api/products` endpoint validated via `CreateProductRequest` with unique SKU validation.
- Created an interactive `+ Add Product` modal in the POS header.
- Dynamic URL resolution via `getApiUrl()` to seamlessly handle both XAMPP subfolder paths (`http://localhost/interview-task/public/`) and standalone development servers (`php artisan serve`).

---

### Prompt 5: Product Update with Duplicate SKU Protection
```
here i need the product upade for example i can upde the stock an gst and price and make sure the duplicate check for the product
```

#### Senior Architectural Intent:
- Built `PUT /api/products/{id}` endpoint with `UpdateProductRequest`.
- Utilized `Rule::unique('products', 'code')->ignore($productId)` to allow updating the current product with its own code while rejecting duplicate codes from other products with an HTTP 422 error.
- Integrated a full "Manage Catalog" modal with live search, stock adjustment, price adjustment, and GST adjustment directly from the POS interface.
- Added 4 feature tests in `ProductUpdateTest` confirming duplicate prevention, self-code persistence, and data type constraints.

---

### Prompt 6: User Experience Enhancement — Inline Field Validation & Floating Toasts
```
see this dont show all like this alert for all show sucess and validaitons meesage inside the blade file and show the validation message beloew that field itself
```

#### Senior Architectural Intent:
- Replaced all intrusive browser `alert(...)` popups with inline field error messages:
  - Form validation errors parsed from Laravel's 422 response (`response.data.errors`) and bound to `<p x-show="errors.field" class="text-rose-500 text-xs mt-1">` directly beneath each input element.
  - Success confirmations and critical warnings routed to a non-blocking, auto-dismissing floating toast system (`toasts: []`, `notify(type, message, title)`).

---

### Prompt 7: Receipt Print Layout & Formatting Fix
```
see this when i give the print means its showing like this
[User screenshot showing blank preview / 2-page split in print preview]
```

#### Senior Architectural Intent:
- Root cause: When `window.print()` was invoked directly on a visible modal, Tailwind's `fixed inset-0 overflow-y-auto` modal wrapper and print media hide rules conflicted with browser layout engines, producing empty or split pages.
- Senior Solution: Implemented `printReceipt()` with an isolated, dedicated hidden `<iframe>`. When the user clicks "Print Receipt", the formatted receipt HTML with dedicated thermal POS styling (80mm width, high-contrast monospace typography, clear dashed separators, and tax summaries) is injected into the iframe and printed cleanly without interference from page styles.

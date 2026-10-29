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

# Laravel Task – E‑Commerce Cart API

This project is a small e‑commerce backend built on Laravel 12. It provides:

- An admin web panel for managing products.
- A token‑based API for customer authentication.
- Cart and checkout APIs with stock handling.
- Feature tests covering cart merge and failed checkout behavior.

---

## Requirements

- PHP 8.2+
- Composer
- Node.js and npm (only needed for asset building; not required for this task)

The project uses SQLite by default.

---

## Installation and Setup

From the `app` directory:

```bash
composer install
cp .env.example .env    # Or copy manually on Windows
php artisan key:generate
php artisan migrate --seed
```

This will:

- Create the SQLite database `database/database.sqlite`.
- Run all migrations (users, products, carts, cart_items, Sanctum tokens).
- Seed:
  - An admin user: `admin@example.com` / password `password`
  - A customer user: `customer@example.com` / password `password`
  - 15 sample products.

---

## Running the Application

From the `app` directory:

```bash
php artisan serve
```

The app will be available at `http://127.0.0.1:8000`.

### Admin Panel

- Login page: `GET /admin/login`
- Credentials (seeded):
  - Email: `admin@example.com`
  - Password: `password`

After login:

- Product list (with search): `GET /admin/products`
- Create product: `GET /admin/products/create`
- Edit product: `GET /admin/products/{id}/edit`
- Delete product: `DELETE /admin/products/{id}`
- Toggle active/inactive: `PATCH /admin/products/{id}/toggle`

Search is done client‑side via a small JavaScript snippet that calls the index route with `?search=...` and replaces the table HTML.

---

## API – Authentication and Cart

Base URL (when using `php artisan serve`):

- `http://127.0.0.1:8000`

All API routes are under `/api`.

### Auth Endpoints

- `POST /api/register`
  - Body: `name`, `email`, `password`
  - Response: `user` and `token` (Sanctum personal access token).

- `POST /api/login`
  - Body: `email`, `password`
  - Response: `user` and `token`.

- `POST /api/logout`
  - Auth: Bearer token.
  - Revokes the current access token.

### Cart Endpoints

All cart endpoints require `Authorization: Bearer {token}`.

- `POST /api/cart/items`
  - Body:
    - `product_id` (int, existing, active product)
    - `qty` (int, ≥ 1)
  - Behavior:
    - If the product is already in the user’s cart, quantities are merged (added together).
    - If not present, a new cart item is created.
  - Response: JSON with `items` and `total`.

- `GET /api/cart`
  - Returns the current cart with `items` (product info, qty, price_at_time, subtotal) and `total`.

- `PATCH /api/cart/items/{product}`
  - URL parameter `{product}` is the product ID.
  - Body: `qty` (int, ≥ 0)
  - Behavior:
    - If `qty` is `0`, the item is removed from the cart.
    - Otherwise, the cart item’s quantity is set to the given value.

- `DELETE /api/cart/items/{product}`
  - Removes the specified product from the user’s cart.

- `POST /api/cart/checkout`
  - Behavior:
    - Loads the cart with its items and locks rows for update.
    - Validates for each item:
      - Product exists.
      - Product is active.
      - Product stock is **at least** the cart quantity.
    - If **any** item fails validation:
      - Returns HTTP `422` with an error message.
      - **Does not** change any product stock.
      - **Does not** clear the cart or its items.
    - If all items are valid:
      - Decrements each product’s stock by the item quantity.
      - Computes the total as `sum(qty * price_at_time)`.
      - Deletes all cart items and the cart row.
      - Returns HTTP `200` with `total`.

---

## Models and Data Structure

- `User`
  - Uses Sanctum (`HasApiTokens`).
  - Extra column: `is_admin` (boolean).

- `Product`
  - Columns: `id`, `name`, `sku` (unique), `price`, `stock`, `is_active`, timestamps.

- `Cart`
  - Columns: `id`, `user_id` (unique per user), timestamps.
  - Relationships:
    - `user()`
    - `items()`

- `CartItem`
  - Columns: `id`, `cart_id`, `product_id`, `qty`, `price_at_time`, timestamps.
  - Relationships:
    - `cart()`
    - `product()`

Prices at checkout use `price_at_time` stored when the item is added to the cart.

---

## Tests

Run the full test suite from the `app` directory:

```bash
php artisan test
```

Implemented tests:

- `tests/Feature/CartTest.php`
  - `test_add_to_cart_merges_duplicates`
    - Asserts that adding the same product twice merges into a single `cart_items` row with summed quantity.
  - `test_checkout_fails_when_stock_insufficient_and_does_not_change_state`
    - Creates a product with `stock = 2`.
    - Adds quantity `3` to the cart.
    - Calls checkout.
    - Asserts:
      - HTTP `422`.
      - Product stock remains unchanged.
      - Cart and its single item still exist.

- `tests/Feature/ExampleTest.php`
  - Checks that `GET /admin/login` returns HTTP `200`.

All tests currently pass.

---

## Postman Collection

A Postman collection file is included at the project root:

- `postman_collection.json`

Usage:

1. Import `postman_collection.json` into Postman.
2. Set the `base_url` variable in the collection (for example, `http://127.0.0.1:8000`).
3. Use the `Register` or `Login` requests to obtain a token.
4. Set the `token` variable in the collection (the raw token string).
5. Use the Cart folder requests; they automatically send the `Authorization: Bearer {{token}}` header.

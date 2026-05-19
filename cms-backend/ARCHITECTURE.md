# Single Vendor eCommerce CMS (With Multi-Vendor Mode)
**Stack:** Laravel 10/11, Filament V3, MySQL, React (Front - Future)

## 1. Core Modules (MVP)
The system is designed primarily as a **Single Vendor E-commerce** platform. However, it contains an architectural toggle to switch to a **Multi-Vendor E-commerce** model (Marketplace mode) without relying on strict database multi-tenancy. 

1.  **Core Configuration:** A global setting/config to toggle `MULTI_VENDOR_MODE` (true/false).
2.  **Settings Module:** Centralized `SiteSettings` containing Title, Tag Line, Branding (Logos/Favicon), and Mail Server credentials managed via dashboard.
3.  **Catalog Module:** Products, Categories, Prices, Brands, Inventory.
4.  **Customer Module:** Storefront users (customers).
5.  **Order Module:** Carts, Orders, Payments, Fulfillment.
6.  **Analytics Module:** Custom dashboard reporting (Traffic, Sessions, Device Usage, and Traffic Sources).
7.  **Vendor Module (Optional):** Active only if Multi-Vendor mode is enabled. Allows third-party sellers to list products on the same single platform.

---

## 2. Database Structure (Schema & Relationships)

### A. Settings & Global
*   **`site_settings`** (Single Row)
    *   `id`, `site_title`, `tag_line`, `language`, `timezone`, `site_icon`, `logo`, `contact_email`, `contact_phone`
    *   **Mail Configs:** `mail_mailer`, `mail_host`, `mail_port`, `mail_username`, `mail_password`, `mail_encryption`, `mail_from_address`, `mail_from_name`

### B. Users & Vendors
*   **`users`** (Admins/Store Managers/Vendors)
    *   `id`, `name`, `email`, `password`, `role` (super_admin, vendor)
*   **`vendors`** (Used only in Multi-Vendor Mode)
    *   `id`, `user_id`, `shop_name`, `slug`, `description`, `logo`, `commission_rate`, `status` (active/pending/suspended).
    *   *Note:* In Single Vendor mode, this table is largely ignored, and the platform owner is the sole seller.

### C. Catalog
*   **`categories`**
    *   `id`, `parent_id`, `name`, `slug`, `is_active`
*   **`brands`**
    *   `id`, `name`, `slug`, `website`
*   **`products`**
    *   `id`, `vendor_id` (nullable - links to `vendors.id` if Multi-Vendor), `brand_id`, `name`, `slug`, `sku`, `description`, `price` (decimal), `compare_at_price`, `stock_qty`, `is_visible`, `type`
*   **`product_images`**
    *   `id`, `product_id`, `path`, `sort_order`

### D. Customers
*   **`customers`**
    *   `id`, `first_name`, `last_name`, `email`, `password` (hashed), `phone`
*   **`addresses`**
    *   `id`, `customer_id`, `type` (billing/shipping), `line1`, `city`, `zip`, `country`

### E. Orders
*   **`orders`**
    *   `id`, `customer_id`, `number` (ORD-001), `status` (pending, processing, completed, cancelled), `total_price`, `payment_status`, `payment_method`
*   **`order_items`**
    *   `id`, `order_id`, `product_id`, `vendor_id` (nullable - to split payouts in multi-vendor mode), `product_name_snapshot`, `price_snapshot`, `quantity`

---

## 3. Implementation Roadmap (Feature-by-Feature)

**Phase 1: Foundation (Single Vendor)**
1.  Setup base `User` roles.
2.  Setup `SiteSetting` database structure and UI management page.
3.  Configure global settings to toggle `MULTI_VENDOR_MODE` (e.g., in `.env` or `config/shop.php`).

**Phase 2: The Catalog (PIM)**
1.  Create `Category`, `Brand`, and `Product` models + Filament Resources.
2.  If `MULTI_VENDOR_MODE` is true, automatically apply global scopes so vendors only see their own products. Admin sees all.

**Phase 3: The API Layer (Public & Marketplace)**
1.  Set up `api.php` routes.
2.  Create `ProductController` (Index/Show).
3.  If `MULTI_VENDOR_MODE` is true, expose `GET /api/v1/vendors/{slug}/products`.

**Phase 4: Transactions (Orders)**
1.  Create `Customer` and `Order` models.
2.  Build the Checkout API endpoint.
3.  Build the Filament Order Management view. If multi-vendor is active, restrict vendors to see only order items belonging to them.

---

## 4. API Endpoints Required (React Frontend)

**Public**
*   `GET /api/v1/config` -> Store settings (returns if marketplace mode is active + general `site_settings`).
*   `GET /api/v1/products` -> List items (filters: category, price, vendor_id).
*   `GET /api/v1/products/{slug}` -> Single item details.
*   `GET /api/v1/categories` -> Menu tree.
*   `GET /api/v1/vendors` -> List of shops (Multi-vendor mode only).

**Customer (Auth Required)**
*   `POST /api/v1/auth/login`
*   `POST /api/v1/auth/register`
*   `GET /api/v1/orders` -> Customer history.

**Checkout**
*   `POST /api/v1/checkout` -> Validates stock, calculates total, creates Order.

---

## 5. Filament Admin Resources & Panels

1.  **Shop Panel:**
    *   **CategoryResource:** Simple Tree or List view.
    *   **BrandResource:** Entity to group products.
    *   **ProductResource:** Tabs for Info, Pricing, Inventory, Images. (Conditionally show "Vendor" select field for Super Admins in Multi-Vendor mode).
    *   **CustomerResource:** Manage storefront users.
2.  **Order Management:**
    *   **OrderResource:** Status badges, Search (Vendors see only their order items/sub-orders). Invoicing layout, Items table.
3.  **Reports Panel:**
    *   **Analytics Page:** Custom Google Analytics-style dashboard visualizing Session Charts, Traffic Sources, Device Usage, and Traffic Overviews.
4.  **Settings Panel:**
    *   **General Settings Page:** Centralized UI form to mutate the `site_settings` single row containing Branding, Locales, and Mail credentials overrides.
5.  **Vendors (Multi-Vendor Mode only):**
    *   **VendorResource:** Manage vendor approvals, commissions, and payouts.

---

## 6. Scalable Architecture Improvements (Future)

1.  **Payout System:** If multi-vendor mode grows, implement a wallet/payout ledger (Stripe Connect).
2.  **Variants/Attributes:** Move from `Simple Product` to `Product Variants` (SKU per Size/Color).
3.  **Caching:** Implement Redis caching for the `/products` API as it is read-heavy.
4.  **Media:** Move generic storage to AWS S3/Cloudflare R2 immediately if expecting high image volume.
5.  **Search:** Replace SQL `LIKE` queries with Meilisearch or Algolia.

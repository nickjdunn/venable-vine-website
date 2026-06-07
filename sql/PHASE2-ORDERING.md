# Phase 2: Online Ordering Extension Points

This document describes how online ordering will integrate when implemented.

## Database (already created)

### `orders`
| Column | Purpose |
|--------|---------|
| customer_name, customer_email, customer_phone | Customer contact |
| pickup_event_id | FK to `events` — where/when to pick up |
| status | pending, confirmed, completed, cancelled |
| total | Order total |
| notes | Special instructions |

### `order_items`
| Column | Purpose |
|--------|---------|
| order_id | FK to orders |
| menu_item_id | FK to menu_items (nullable if item deleted) |
| item_name | Snapshot of name at order time |
| quantity | Item count |
| unit_price | Price at order time |

## Admin UI

- `/admin/ordering.php` — currently a placeholder; will become order inbox
- Nav link already present in admin sidebar

## Suggested implementation steps

1. **Simple pre-order form** on public site
   - Pull active menu items from `MenuRepository`
   - Select pickup event from `EventRepository::upcoming()`
   - POST to `/api/submit-order.php`
   - Insert into `orders` + `order_items`
   - Email notification via `Mailer`

2. **Admin order management**
   - List orders by status
   - Confirm / complete / cancel
   - Filter by event date

3. **Payment (optional)**
   - Stripe Checkout or Square Web Payments
   - Store payment intent ID on `orders` table (add column in migration)

## Mailchimp (Phase 1.5)

Wire `NewsletterService::syncToMailchimp()` in `includes/NewsletterService.php` when API keys are set in Settings.

# TODO

- [ ] Fix migration 2026_05_30_204848_create_products_table.php: add missing `reference` column and ensure schema matches Product model.
- [x] Fix migration 2026_06_16_000001_update_products_reference_unique.php: make dropping/adding the unique constraint safe (no failing drop when constraint name doesn’t exist).
- [ ] Run migrations to validate (e.g., php artisan migrate or migrate:fresh depending on workflow).
- [ ] Re-run failed deploy step / verify no further SQLSTATE 42703 errors.



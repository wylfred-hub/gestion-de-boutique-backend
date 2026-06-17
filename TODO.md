# TODO

- [ ] Fix migration 2026_05_30_204848_create_products_table.php: add missing `reference` column and ensure schema matches Product model.
- [x] Fix migration 2026_06_16_000001_update_products_reference_unique.php: make dropping/adding the unique constraint safe (no failing drop when constraint name doesn’t exist).
- [x] Fix migration 2026_06_16_194219_change_sale_number_unique_to_per_organization.php: make unique-constraint drop safe.
- [ ] Run migrations to validate (e.g., php artisan migrate or migrate:fresh depending on workflow).
- [x] Fix migration 2026_06_16_201900_add_retour_to_stock_movements_type.php: replace MySQL-only MODIFY COLUMN with Postgres-compatible enum/type replacement.
- [ ] Re-run failed deploy step / verify no further SQLSTATE errors.





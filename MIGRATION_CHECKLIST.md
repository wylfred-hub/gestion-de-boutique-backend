# Checklist - Migration vers Multi-Organisations ✓

## ✅ Complété

### Migrations
- [x] Table `organizations` créée
- [x] Table `organization_user` (Many-to-Many) créée
- [x] Colonnes `organization_id` ajoutées aux tables pertinentes
- [x] Indices et contraintes de clés étrangères configurées

### Modèles
- [x] Modèle `Organization` créé avec relations
- [x] Modèle `User` mis à jour avec relation `organizations()`
- [x] Modèles `Product`, `Category`, `Client`, `Sale`, `StockMovement` mis à jour
- [x] Relations `organization()` ajoutées à tous les modèles

### Traits & Middleware
- [x] Trait `BelongsToOrganization` créé pour filtrage automatique
- [x] Middleware `EnsureOrganizationAccess` créé
- [x] Trait appliqué aux modèles appropriés

### Seeders
- [x] Seeder `OrganizationSeeder` créé (3 organisations de test)
- [x] Seeder `UserSeeder` mis à jour (liens Many-to-Many)
- [x] `DatabaseSeeder` mis à jour pour ordre d'exécution correct

### Sécurité
- [x] Policy `OrganizationPolicy` créée

### Documentation
- [x] Guide `MULTI_ORGANISATIONS.md` créé

---

## ⚠️ À faire après

### 1. Enregistrer le middleware
**Fichier:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    // Ajouter après les middlewares existants
    $middleware->append(\App\Http\Middleware\EnsureOrganizationAccess::class);
})
```

### 2. Exécuter les migrations
```bash
php artisan migrate
```

### 3. Exécuter les seeders
```bash
php artisan db:seed
```

### 4. Mettre à jour les contrôleurs

Pour chaque contrôleur qui récupère des données, ajouter `.forCurrentOrganization()`:

```php
// AVANT
$products = Product::get();

// APRÈS
$products = Product::forCurrentOrganization()->get();
```

Exemples de contrôleurs à mettre à jour:
- `ProductController`
- `CategoryController`
- `ClientController`
- `SaleController`
- `StockMovementController`

### 5. Mettre à jour les seeders existants

Mettre à jour `ProductSeeder`, `CategorySeeder`, `ClientSeeder` pour assigner `organization_id`:

```php
$organization = Organization::where('slug', 'acme-corporation')->first();

Product::create([
    'organization_id' => $organization->id,
    'name' => 'Produit X',
    // ...
]);
```

### 6. Mettre à jour les routes API

Si vous avez une API, ajouter le filtrage par organisation:

```php
// api.php
Route::middleware(['auth:sanctum', 'organization.access'])->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    // ...
});
```

### 7. Tester multi-organisation

Créer des tests pour vérifier:
- ✓ Un utilisateur de ACME ne voit pas les données de Tech Solutions
- ✓ Les créations héritent automatiquement de l'organization_id
- ✓ Le middleware bascule d'organisation si accès révoqué

```php
// Tests/Feature/MultiOrganizationTest.php
test('user can only see their organization data', function () {
    $admin = User::where('email', 'admin@cachet.com')->first();
    $this->actingAs($admin);
    
    $acmeOrg = Organization::where('slug', 'acme-corporation')->first();
    $techOrg = Organization::where('slug', 'tech-solutions')->first();
    
    session(['current_organization_id' => $acmeOrg->id]);
    
    $acmeProducts = Product::forCurrentOrganization()->count();
    // Vérifier que seuls les produits de ACME sont retournés
    
    session(['current_organization_id' => $techOrg->id]);
    $techProducts = Product::forCurrentOrganization()->count();
    // Vérifier que seuls les produits de Tech Solutions sont retournés
});
```

### 8. Frontend - Sélecteur d'organisation

Ajouter un composant pour basculer entre organisations:

```php
// Dans votre layout ou nav
@if(auth()->user() && auth()->user()->organizations->count() > 1)
    <div class="organization-switcher">
        <select onchange="switchOrganization(this.value)">
            @foreach(auth()->user()->organizations as $org)
                <option value="{{ $org->id }}" 
                    {{ $org->id == session('current_organization_id') ? 'selected' : '' }}>
                    {{ $org->name }}
                </option>
            @endforeach
        </select>
    </div>
@endif
```

Avec une route pour changer:

```php
// routes/web.php
Route::post('/organization/switch/{organization}', function (Organization $org) {
    if (! auth()->user()->organizations()->where('id', $org->id)->exists()) {
        abort(403);
    }
    session(['current_organization_id' => $org->id]);
    return redirect()->back();
})->middleware('auth');
```

---

## 📋 Ordre recommandé

1. ✅ Comprendre la structure (lire [MULTI_ORGANISATIONS.md](MULTI_ORGANISATIONS.md))
2. Exécuter migrations et seeders
3. Enregistrer le middleware
4. Mettre à jour les contrôleurs progressivement
5. Mettre à jour les seeders de test
6. Ajouter tests unitaires
7. Tester manuellement avec différents utilisateurs/organisations

---

## 🎯 Points clés à retenir

- **Chaque organisation a ses propres données isolées**
- **Les utilisateurs peuvent appartenir à plusieurs organisations**
- **La session gère l'organisation courante**
- **Le trait automatise le filtrage par organisation**
- **Toujours utiliser `.forCurrentOrganization()` pour les listings**

## 👑 Rôles et permissions

### Rôles globaux (users.role)
- `super_admin` - Vous! Accès à TOUT
- `admin` - Administrateur d'application (gestion utilisateurs)
- `vendeur` - Vendeur (sans accès admin)

### Rôles d'organisation (organization_user.role)
- `owner` - Propriétaire d'une organisation
- `admin` - Administrateur d'une organisation
- `vendeur` - Vendeur dans une organisation

### Utilisateurs de test après seed
```
Email: superadmin@cachet.com
Password: superadmin123
Role: SUPER_ADMIN - Accès à tout!

Email: admin@cachet.com
Password: admin1234
Role: ADMIN - Owner dans ACME, Admin dans Tech Sol et Global Trade

Email: vendeur@cachet.com
Password: vend1234
Role: VENDEUR - Vendeur dans ACME seulement
```

---

**Questions?** Voir [MULTI_ORGANISATIONS.md](MULTI_ORGANISATIONS.md) pour plus de détails.

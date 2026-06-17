# Guide Multi-Organisations

## 🏢 Vue d'ensemble

Votre application est maintenant configurée pour supporter plusieurs organisations/entreprises indépendantes. Chaque organisation a ses propres:

- **Produits** et **Catégories**
- **Clients**
- **Ventes** et **Mouvements de stock**
- **Utilisateurs** avec rôles spécifiques

## 📊 Architecture

### Tables principales

| Table | Description |
|-------|-------------|
| `organizations` | Les organisations/entreprises |
| `organization_user` | Relation Many-to-Many entre utilisateurs et organisations |
| `products`, `categories`, `clients`, `sales`, `stock_movements` | Contiennent `organization_id` pour isolation des données |

### Relations d'utilisateurs

```
User (1) ----> (Many) Organization
     |
     └----> pivot: role, is_active
```

## 👤 Système de rôles à deux niveaux

### Niveau 1: Rôles globaux (colonne `users.role`)

Défini au niveau de l'utilisateur dans la table `users`:

| Rôle | Description | Accès |
|------|-------------|-------|
| **super_admin** | Propriétaire de l'application | ✅ Accès à TOUT, gestion de tous les utilisateurs et organisations |
| **admin** | Administrateur d'application | ✅ Gestion des utilisateurs, accès aux organisations assignées |
| **vendeur** | Vendeur | ❌ Pas d'accès au panneau admin, accès selon l'organisation |

### Niveau 2: Rôles d'organisation (table `organization_user.role`)

Défini pour chaque relation utilisateur-organisation:

| Rôle | Description | Accès |
|------|-------------|-------|
| **owner** | Propriétaire de l'organisation | ✅ Accès complet à l'organisation et ses données |
| **admin** | Administrateur d'organisation | ✅ Accès complet à l'organisation |
| **vendeur** | Vendeur dans l'organisation | ✅ Accès lecture/vente seulement |

### Exemple d'hiérarchie

```
Application (Super Admin - Vous)
├─ Organization 1 (ACME)
│  ├─ User 1 (Owner) - Accès complet ACME
│  ├─ User 2 (Admin) - Accès complet ACME
│  └─ User 3 (Vendeur) - Lecture/Vente uniquement
├─ Organization 2 (Tech Solutions)
│  ├─ User 1 (Admin) - Accès complet Tech Solutions
│  └─ User 4 (Vendeur) - Lecture/Vente uniquement
```

Un utilisateur avec rôle `admin` global peut être `owner` dans une org et `vendeur` dans une autre!

## 🔧 Utilisation dans les contrôleurs

### Filtrer par organisation courante

```php
// Récupérer les produits de l'organisation actuelle
$products = Product::forCurrentOrganization()->get();

// Ou explicitement
$products = Product::ofOrganization(auth()->user()->currentOrganization())->get();

// Ou par ID
$products = Product::ofOrganization(5)->get();
```

### Créer des enregistrements

```php
// L'organization_id est automatiquement assigné de la session courante
$product = Product::create([
    'name' => 'Produit A',
    'price' => 100,
    // organization_id est assigné automatiquement via le trait
]);
```

### Scopes disponibles

```php
// Filtrer par organisation
Product::ofOrganization($organizationId)->get();

// Filtrer par organisation courante
Product::forCurrentOrganization()->get();

// Combiner avec d'autres conditions
Product::forCurrentOrganization()
    ->where('is_active', true)
    ->get();
```

## 🔐 Vérification des permissions

### Vérifier l'accès utilisateur

```php
// Vérifier si l'utilisateur a un rôle spécifique dans une organisation
if (auth()->user()->hasRoleInOrganization($org, 'admin')) {
    // ...
}

// Obtenir le rôle
$role = auth()->user()->getRoleInOrganization($org);

// Vérifier si propriétaire
if (auth()->user()->isOwnerOfOrganization($org)) {
    // ...
}

// Vérifier si admin (owner ou admin)
if (auth()->user()->isAdminOfOrganization($org)) {
    // ...
}
```

## 🌍 Gestion de l'organisation courante

L'organisation courante est stockée en session:

```php
// Définir l'organisation courante
session(['current_organization_id' => $organizationId]);

// Récupérer l'organisation courante
$org = auth()->user()->currentOrganization();
```

### Middleware

Le middleware `EnsureOrganizationAccess` (dans `app/Http/Middleware/`) s'assure que:

1. L'utilisateur a au moins une organisation
2. L'organisation en session est valide et accessible
3. Bascule automatiquement vers la première organisation si accès révoqué

À ajouter dans `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\EnsureOrganizationAccess::class);
})
```

## 📝 Migrations

Trois migrations ont été créées:

1. **`2026_06_03_000000_create_organizations_table.php`** - Table organizations
2. **`2026_06_03_000001_create_organization_user_table.php`** - Relation Many-to-Many
3. **`2026_06_03_000002_add_organization_id_to_tables.php`** - Ajoute organization_id aux tables existantes

Exécuter:
```bash
php artisan migrate
php artisan db:seed
```

## 👥 Utilisateurs de test

Après seed:

| Email | Mot de passe | Rôle Global | Organisations | Rôle dans Org |
|-------|-------------|---------|---|---|
| superadmin@cachet.com | superadmin123 | Super Admin | ACME, Tech Solutions, Global Trade | Owner |
| admin@cachet.com | admin1234 | Admin | ACME, Tech Solutions, Global Trade | Owner, Admin, Admin |
| vendeur@cachet.com | vend1234 | Vendeur | ACME | Vendeur |

## 🚀 Exemple complet: Créer une vente

```php
<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        // Valider les données
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'items' => 'required|array',
            // ...
        ]);

        // La vente sera automatiquement liée à l'organisation courante
        $sale = Sale::create(array_merge($validated, [
            'user_id' => auth()->id(),
            'sale_number' => $this->generateSaleNumber(),
        ]));

        return response()->json($sale, 201);
    }

    private function generateSaleNumber(): string
    {
        $year = date('Y');
        $orgPrefix = auth()->user()->currentOrganization()->slug;
        $count = Sale::forCurrentOrganization()
            ->whereYear('created_at', $year)
            ->count() + 1;
        
        return strtoupper($orgPrefix) . '-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
```

## ⚠️ Points importants

1. **Toujours filtrer par organisation** dans les index/listing
2. **Assigner l'organization_id** lors de la création (automatique avec le trait)
3. **Vérifier les permissions** avant les opérations sensibles
4. **Tester multi-org** en changeant l'organisation en session
5. **API** : Passer l'organization_id en paramètre si nécessaire

## 🔄 Updater les seeders existants

Les seeders `ProductSeeder`, `ClientSeeder`, `CategorySeeder` doivent être mis à jour pour assigner les données à des organisations spécifiques. Exemple:

```php
public function run(): void
{
    $acme = Organization::where('slug', 'acme-corporation')->first();
    $techSol = Organization::where('slug', 'tech-solutions')->first();

    Category::create([
        'organization_id' => $acme->id,
        'name' => 'Électronique',
        'slug' => 'electronique',
    ]);

    Category::create([
        'organization_id' => $techSol->id,
        'name' => 'Logiciels',
        'slug' => 'logiciels',
    ]);
}
```

---

**Besoin d'aide?** Consultez les fichiers:
- [Trait BelongsToOrganization](app/Traits/BelongsToOrganization.php)
- [Middleware EnsureOrganizationAccess](app/Http/Middleware/EnsureOrganizationAccess.php)
- [Modèle Organization](app/Models/Organization.php)

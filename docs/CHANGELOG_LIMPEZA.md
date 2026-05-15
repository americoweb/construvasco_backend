# CHANGELOG — Limpeza estratégica do backend Construvasco

| Campo | Valor |
|-------|--------|
| **Data** | 2026-05-15 |
| **Branch** | `chore/backend-cleanup` |
| **Estado** | **Concluído** (passos 0–8) |

## Objectivo

Remover do backend tudo o que não pertence ao produto **Construvasco Digital**, consolidando **`Project`** como entidade central.

---

## Passo 0 — Preparação ✅

- Branch `chore/backend-cleanup`
- `docs/LIMPEZA_PLANEADA.md`, `docs/CHANGELOG_LIMPEZA.md`

## Passo 1 — Candidates ✅

- Removidos ~40 ficheiros (models, controllers, services, repos, events, enums, factories, AI contracts)
- Migration `2026_05_15_100000_remove_candidate_tables.php`
- `routes/candidate.php` removido

## Passo 2 — Forms EU/USAID ✅

- Removida árvore `app/Services/Forms`, models, policies, observers, middleware, commands, tests
- `FormEngineServiceProvider`, `config/form_engine.php` removidos
- Migration `2026_05_15_110000_remove_form_engine_tables.php`
- Permissões `forms.*` removidas do seeder (Passo 5)

## Passo 3 — E-commerce Amazing ✅

- Removidos: Product, Cart, Design, Order, Checkout (models, controllers, services, repos, providers, routes, seeders, factories, tests)
- **IA refactorizada:** `ArchitecturalAiService` + `GenerateArchitecturalRenderRequest`; removidos `/ai/suggestions`, `/ai/mockup`
- `SuggestionService`, `FallbackSuggestionService`, DTOs produto removidos
- `AdminDashboardController` → métricas de projectos
- `ConstructionProjectSeeder` → `service_categories` + `construction_services`
- Migration `2026_05_15_120000_remove_amazing_commerce_tables.php` (ordem FK: job_cards → cart → orders → designs → products)
- **`payment_proofs` mantida**; fix `PaymentProofController` → `App\Models\Payment\PaymentProof`

## Passo 4 — JobCards ✅

- Removidos JobCard domain completo + `SyncFileToDrive` job
- **Mantidos:** `GoogleDriveService`, `AuthorizeGoogleDrive`, `config/services.google_drive`
- Migration `2026_05_15_130000_remove_job_card_tables.php` (no-op; drops em 120000)

## Passo 5 — Permissões e seeders ✅

- `PermissionRoleSeeder`: removidos `forms.*`, role `designer` dos allowed roles
- `DatabaseSeeder`: removido `DesignerRoleSeeder`
- Seeders órfãos apagados (Product, Cart, Design, Order modules)

## Passo 6 — routes/api.php ✅

Requires finais: `auth`, `user`, `tenants`, `permissions`, `payment`, `ai`, `admin`, `construction`

`bootstrap/providers.php`: apenas `AppServiceProvider`, `AIServiceProvider`

## Passo 7 — Verificação ✅

- `php artisan route:list` — OK
- `php artisan migrate --force` — OK (após fix ordem FK na migration 120000)
- `php artisan test` — 2 passed

## Passo 8 — Relatório ✅

- `docs/RELATORIO_POS_LIMPEZA.md`

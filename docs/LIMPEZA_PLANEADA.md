# LIMPEZA PLANEADA — Backend Construvasco

**Branch:** `chore/backend-cleanup`  
**Data do plano:** 2026-05-15  
**Estado:** Inventário completo — **aguarda aprovação antes de qualquer remoção**

---

## O que NÃO será tocado

| Área | Caminhos / notas |
|------|------------------|
| Domínio construção | `app/Models/Construction/*`, `app/Models/Project.php`, `app/Services/Construction/*`, `app/Http/Controllers/Construction/*`, `routes/construction.php`, migration `2026_05_05_114500_create_construction_domain_tables.php` |
| Auth & tenants | `app/Http/Controllers/Api/AuthController.php`, `routes/auth.php`, `routes/user.php`, `routes/tenants.php`, `User`, `Tenant`, JWT |
| Permissions | Spatie (`PermissionRoleSeeder` — limpeza de órfãos no Passo 5) |
| IA Gemini (core) | `config/ai.php`, `app/Services/AI/GeminiService.php`, `app/Providers/AIServiceProvider.php`, `routes/ai.php`, `SuggestionController` — **refactor** de acoplamento a produtos no Passo 3 |
| Pagamentos gateway | `OnlinePaymentController`, `routes/payment.php`, `ProjectPaymentController` |
| Activity log | migrations e `ActivityLogService` |
| WhatsApp | `config/services.php` → `whatsapp`, `SendWhatsAppNotification`, jobs de checkout/job card (checkout/job card removidos; job WhatsApp em `JobCardService` sai no Passo 4) |
| Admin base | `routes/admin.php`, `AdminDashboardController` (ajustar métricas após remoções) |

---

## Resumo quantitativo (estimativa)

| Passo | Models | Controllers | Route files | Migrations drop | Outros |
|-------|--------|-------------|-------------|-----------------|--------|
| 1 Candidates | 6 + `Skill` | 4 | 1 (não activo) | 1 nova (tabelas possivelmente inexistentes) | ~35 ficheiros PHP |
| 2 Forms | 4 | 2 | 0 (sem routes API) | 1 nova | ~45 ficheiros PHP |
| 3 Amazing | 14 | 12+ | 5 | 1–2 novas (ordem FK) | ~120+ ficheiros PHP |
| 4 JobCards | 4 | 3 | 1 | 1 nova | ~35 ficheiros PHP |
| 5 Seeders/perms | — | — | — | — | 2 seeders + permissões |
| 6 Routes | — | — | `api.php` edit | — | 7 requires removidos |

---

## PASSO 1 — Candidates (recrutamento)

### Situação actual

- `routes/candidate.php` existe mas está **comentado** em `routes/api.php` (linha 37–38).
- **Não há migrations** no repositório para `candidates`, `resumes`, `skills`, etc. O módulo pode nunca ter sido aplicado à BD local — a migration de drop usará `Schema::dropIfExists`.

### Models a remover

```
app/Models/Candidate/Candidate.php
app/Models/Candidate/CandidateProfile.php
app/Models/Candidate/CandidateExperience.php
app/Models/Candidate/CandidateEducation.php
app/Models/Candidate/CandidateSkill.php
app/Models/Candidate/Resume.php
app/Models/Skill.php
```

### Controllers

```
app/Http/Controllers/Candidate/CandidateController.php
app/Http/Controllers/Candidate/CandidateProfileController.php
app/Http/Controllers/Candidate/CandidateMatchingController.php
app/Http/Controllers/Candidate/ResumeController.php
```

### Services, repositories, events, enums, resources, requests

```
app/Services/Candidate/CandidateService.php
app/Services/Candidate/CandidateMatchingService.php
app/Services/Candidate/CandidateSearchService.php
app/Services/Candidate/ResumeParserService.php
app/Repositories/Candidate/CandidateRepository.php
app/Repositories/Candidate/Contracts/CandidateRepositoryInterface.php
app/Events/Candidate/CandidateCreated.php
app/Events/Candidate/CandidateMatched.php
app/Events/Candidate/ResumeUploaded.php
app/Enums/Candidate/CandidateStatus.php
app/Enums/Candidate/ExperienceLevel.php
app/Enums/Candidate/SkillLevel.php
app/Http/Resources/Candidate/* (4 ficheiros)
app/Http/Requests/Candidate/* (3 ficheiros)
app/Contracts/AI/MatchingEngineInterface.php
app/Contracts/AI/ResumeParserInterface.php
app/Services/AI/DefaultMatchingEngine.php
app/Services/AI/DefaultResumeParser.php
```

### Factories & seeders

```
database/factories/Candidate/CandidateFactory.php
database/factories/Candidate/CandidateProfileFactory.php
database/factories/Candidate/ResumeFactory.php
database/factories/ResumeFactory.php
database/factories/SkillFactory.php
```

*(Nenhum `CandidateSeeder` em `DatabaseSeeder` — OK)*

### Routes

- Remover ficheiro: `routes/candidate.php`
- Remover comentário em `routes/api.php` (opcional, linhas 37–38)

### Permissões Spatie

- Pesquisa: **não existem** `candidates.*` em `PermissionRoleSeeder.php`

### Acoplamentos a limpar

| Ficheiro | Acção |
|----------|--------|
| `app/Providers/AppServiceProvider.php` | Remover bind `CandidateRepositoryInterface`, observers Forms mantidos até Passo 2 |
| `app/Services/AI/GeminiService.php` | Verificar imports Candidate — remover se existirem |

### Migration de drop (criar)

`database/migrations/2026_05_15_100000_remove_candidate_tables.php`

Tabelas candidatas (dropIfExists, ordem inversa de FKs se existirem):

- `candidate_skills`, `candidate_education`, `candidate_experiences`, `candidate_profiles`, `resumes`, `candidates`, `skills`

*(Ajustar após inspecção `php artisan db:show` na BD do ambiente.)*

### Testes a remover

- Nenhum em `tests/` dedicado a Candidate (só Forms/Checkout/AI)

---

## PASSO 2 — Forms EU/USAID

### Situação actual

- Motor de formulários completo em `app/Services/Forms/*`, policies, observers, middleware (comentado em `bootstrap/app.php`).
- **Sem `routes/forms.php`** e **sem migrations** no repo — tabelas podem não existir em dev.
- `FormEngineServiceProvider` registado em `bootstrap/providers.php`.

### Models a remover

```
app/Models/Forms/FormTemplate.php
app/Models/Forms/FormInstance.php
app/Models/Forms/FormSubmission.php
app/Models/Forms/FormTemplateVersion.php
```

### Policies

```
app/Policies/Forms/FormTemplatePolicy.php
app/Policies/Forms/FormInstancePolicy.php
```

### Controllers

```
app/Http/Controllers/Api/Forms/FormTemplateController.php
app/Http/Controllers/Api/Forms/FormInstanceController.php
```

### Services (árvore completa)

```
app/Services/Forms/FormTemplateService.php
app/Services/Forms/FormIntelligenceService.php
app/Services/Forms/FormRuleEngine.php
app/Services/Forms/FormPatternEngine.php
app/Services/Forms/SmartFormValidator.php
app/Services/Forms/WorkflowIntegrationService.php
app/Services/Forms/MethodologyAdapterService.php
app/Services/Forms/Methodology/* (4 adapters)
app/Services/Forms/Compliance/* (5 ficheiros)
app/Services/Shared/FormEngineService.php
```

### Observers, middleware, commands, config, provider

```
app/Observers/Forms/FormTemplateObserver.php
app/Observers/Forms/FormInstanceObserver.php
app/Http/Middleware/Forms/FormSessionMiddleware.php
app/Http/Middleware/Forms/FormValidationMiddleware.php
app/Console/Commands/Forms/ValidateFormTemplates.php
app/Console/Commands/Forms/CleanupExpiredForms.php
config/form_engine.php
app/Providers/FormEngineServiceProvider.php
```

### HTTP layer

```
app/Http/Resources/Forms/* (5)
app/Http/Requests/Forms/* (4)
```

### Permissões Spatie (remover no Passo 5)

```
forms.view, forms.create, forms.edit, forms.delete, forms.submit, forms.approve
forms.* (wildcard se existir)
```

Atribuídas hoje a: `admin`, `project_manager`, `customer` em `PermissionRoleSeeder.php`.

### Acoplamentos

| Ficheiro | Acção |
|----------|--------|
| `app/Providers/AppServiceProvider.php` | Remover observers `FormTemplate` / `FormInstance` |
| `bootstrap/providers.php` | Remover `FormEngineServiceProvider` |

### Migration de drop (criar)

`database/migrations/2026_05_15_110000_remove_form_engine_tables.php`

- `form_submissions`, `form_instances`, `form_template_versions`, `form_templates`

### Testes a remover

```
tests/Feature/Forms/FormTemplateTest.php
tests/Feature/Forms/FormInstanceTest.php
tests/Feature/Forms/FormWorkflowTest.php
```

---

## PASSO 3 — E-commerce Amazing (MAIOR PASSO)

### 3.1 Product (catálogo)

**Models (8):** `Product`, `ProductColor`, `ProductPrintArea`, `ProductSize`, `ProductSizeRestriction`, `Category`, `Tag`, `Testimonial`

**Controllers (8):** `app/Http/Controllers/Product/*`

**Services:** `app/Services/Product/*` (6 ficheiros)

**Repositories:** `app/Repositories/Product/*` (10 ficheiros)

**Provider:** `ProductServiceProvider.php`

**Routes:** `routes/product.php` — remover

**Seeders (não no DatabaseSeeder actual, remover ficheiros):**

```
ProductSeeder.php, CategorySeeder.php, TagSeeder.php, TestimonialSeeder.php, ProductModuleSeeder.php
```

**Factories:** `ProductFactory`, `ProductColorFactory`, `ProductPrintAreaFactory`

**Migrations originais (manter ficheiros, drop via nova migration):**

- `products`, `product_colors`, `product_print_areas`, `product_sizes`, `product_size_restrictions`
- `categories`, `tags`, `category_product`, `product_tag`, `testimonials`

---

### 3.2 Cart + Checkout

**Models:** `Cart`, `CartItem`

**Controllers:** `CartController`, `CheckoutController`

**Services:** `CheckoutService`, `CartService`, `ProductPricingService` (avaliar uso só checkout)

**Repositories:** `app/Repositories/Cart/*` (4)

**Providers:** `CartServiceProvider`, `CheckoutServiceProvider`

**Routes:** `routes/cart.php`, `routes/checkout.php`

**Config:** `config/checkout.php`

**Events/Listeners/Jobs:**

```
app/Events/Checkout/*
app/Listeners/Checkout/*
app/Jobs/Checkout/SendOrderNotification.php
app/Console/Commands/ClearExpiredCarts.php
```

**Seeders:** `CartSeeder.php`, `CartModuleSeeder.php`

**Factories:** `CartFactory`, `CartItemFactory`

**Migrations:** `carts`, `cart_items`

---

### 3.3 Design (mockups ligados a produto)

**Models:** `Design`, `DesignRefinement`

**Controllers:** `DesignController`, `DesignRefinementController` (em `app/Http/Controllers/Design/`)

**Repositories:** `app/Repositories/Design/*` (4)

**Provider:** `DesignServiceProvider`

**Routes:** `routes/design.php`

**Seeders:** `DesignSeeder.php`, `DesignModuleSeeder.php`

**Factories:** `DesignFactory`, `DesignRefinementFactory`

**Migrations:** `designs`, `design_refinements` (FK → products)

---

### 3.4 Order (pedidos e-commerce + briefing)

**Models:** `Order`, `OrderItem`, `OrderStatusHistory`

**Enum:** `app/Enums/Order/OrderStatus.php`

**Controllers:** `OrderController`

**Services:** `OrderService`

**Repositories:** `app/Repositories/Order/*` (4)

**Provider:** `OrderServiceProvider`

**Resources:** `app/Http/Resources/Order/*`

**Requests:** `app/Http/Requests/Order/*`

**Routes:** `routes/order.php`

**Seeders:** `OrderSeeder.php`, `OrderModuleSeeder.php`

**Factories:** `OrderFactory`, `OrderItemFactory`

**Migrations:**

- `orders`, `order_items`, `order_status_history`
- `2025_11_19_123445_add_payment_fields_to_orders_table.php`
- `2026_05_08_103800_add_construction_briefing_fields_to_orders_table.php` ⚠️

#### ⚠️ DECISÃO OBRIGATÓRIA ANTES DO DROP `orders`

**Script de inventário (executar na BD antes do Passo 3):**

```sql
SELECT COUNT(*) AS total_orders FROM orders;
SELECT COUNT(*) AS com_briefing FROM orders
WHERE project_type IS NOT NULL
   OR service_type IS NOT NULL
   OR terrain_area_sqm IS NOT NULL
   OR briefing_metadata IS NOT NULL;
```

**Plano de dados:**

| Cenário | Acção |
|---------|--------|
| `com_briefing = 0` | Drop directo (após confirmação) |
| `com_briefing > 0` | Migration de dados → `project_requirements` + `projects`, depois drop |

**Não executar drop de `orders` sem OK explícito do PO.**

---

### 3.5 PaymentProof — ⚠️ DECISÃO PENDENTE

| Facto | Detalhe |
|-------|---------|
| Tabela | `payment_proofs` — **sem FK** para `orders` |
| Model | `app/Models/Payment/PaymentProof.php` |
| Controller | `PaymentProofController` referencia `App\Models\Subscription\PaymentProof` (**namespace errado** — bug pré-existente) |
| Uso | Upload de comprovativo manual; pode servir projectos |

**Recomendação:** **MANTER** tabela + corrigir namespace; associar futuramente a `project_payments`.  
**Alternativa:** remover com módulo order se PO confirmar que só servia checkout.

➡️ **Perguntar ao PO antes do Passo 3.**

---

### 3.6 IA — dependência crítica (NÃO remover endpoints)

| Endpoint | Acoplamento actual |
|----------|-------------------|
| `POST /v1/ai/suggestions` | Produtos + orçamento — **remover ou desactivar** |
| `POST /v1/ai/mockup` | `product_id` obrigatório — **remover ou desactivar** |
| `POST /v1/ai/refine` | Prompt only — pode manter |
| `POST /v1/ai/house` | `product_id` + `generateMockup()` — **MANTER, refactor** |
| `POST /v1/ai/floorplan` | Idem — **MANTER, refactor** |
| `GET /v1/ai/health` | Manter |

**Trabalho incluído no Passo 3 (sub-tarefa 3.A):**

1. Novo request `GenerateArchitecturalRenderRequest` (sem `product_id`; briefing no body).
2. `SuggestionService::generateHouseRender` / `generateFloorPlan` — chamar `GeminiService` directamente, sem `products` table.
3. Remover de `routes/ai.php`: `suggestions`, `mockup` (ou comentar com deprecation).
4. Actualizar `tests/Feature/AI/SuggestionTest.php`.

**Ficheiros a manter:** `GeminiService`, `SuggestionService` (podado), `FallbackSuggestionService` (avaliar), `AIServiceProvider`, DTOs/Resources AI usados.

---

### 3.7 Outros ficheiros Amazing

```
app/Http/Controllers/ProjectController.php  # CRUD legacy projects table (pré-construction) — avaliar vs Construction
config/domain_transition.php               # flags transição comercial — remover ou simplificar
app/Http/Controllers/Admin/AdminDashboardController.php  # métricas orders/job cards — refactor
```

**Migration comercial noop (manter):**

- `2026_05_05_120500_drop_legacy_print_domain_tables.php`
- `2026_05_08_141100_restore_transitional_commercial_tables.php`

**Nova migration de drop (criar):**

`database/migrations/2026_05_15_120000_remove_amazing_commerce_tables.php`

Ordem sugerida de drop (respeitar FKs):

1. `design_refinements`, `designs`
2. `cart_items`, `carts`
3. `order_status_history`, `order_items`, `orders`
4. `payment_proofs` *(só se PO decidir remover)*
5. `testimonials`, `product_tag`, `category_product`
6. `product_size_restrictions`, `product_sizes`, `product_print_areas`, `product_colors`, `products`
7. `tags`, `categories`

---

### 3.8 Testes a remover / actualizar

| Remover | Actualizar |
|---------|------------|
| `tests/Feature/Checkout/CheckoutTest.php` | `tests/Feature/AI/SuggestionTest.php` |
| `tests/Unit/Services/Checkout/CheckoutServiceTest.php` | |
| `tests/Unit/Services/AI/SuggestionServiceTest.php` | (parcial) |

---

## PASSO 4 — JobCards

### Models

```
app/Models/JobCard/JobCard.php
app/Models/JobCard/JobCardItem.php
app/Models/JobCard/JobCardFile.php
app/Models/JobCard/JobCardFeedback.php
```

### Enums

```
app/Enums/JobCard/* (5 ficheiros)
```

### Controllers

```
app/Http/Controllers/Admin/JobCardController.php
app/Http/Controllers/Admin/JobCardFileController.php
app/Http/Controllers/Admin/JobCardDesignController.php
```

### Services, repositories, resources, requests

```
app/Services/JobCard/JobCardService.php
app/Repositories/JobCard/*
app/Http/Resources/JobCard/* (6)
app/Http/Requests/JobCard/* (6)
```

### Jobs & Google Drive

| Ficheiro | Destino |
|----------|---------|
| `app/Jobs/SyncFileToDrive.php` | Remover com JobCards |
| `app/Services/GoogleDriveService.php` | **MANTER** (reuso ProjectDeliverable) |
| `app/Console/Commands/AuthorizeGoogleDrive.php` | **MANTER** |
| `config/services.php` → `google_drive` | **MANTER** |

### Routes

- Remover `routes/job_card.php`
- Remover require em `routes/api.php`

### Acoplamentos

| Ficheiro | Acção |
|----------|--------|
| `app/Models/Order/Order.php` | Remover relação `jobCard()` |
| `app/Providers/AppServiceProvider.php` | Remover `JobCardRepositoryInterface` bind |
| `app/Http/Controllers/Admin/AdminDashboardController.php` | Remover métricas job cards |

### Migrations originais (manter)

`2026_04_10_000001` … `000006`, `2026_04_22_124346`

### Migration de drop (criar)

`database/migrations/2026_05_15_130000_remove_job_card_tables.php`

- `job_card_feedback`, `job_card_files`, `job_card_items`, `job_cards`

---

## PASSO 5 — Permissões e seeders órfãos

### PermissionRoleSeeder.php

Remover permissões e atribuições:

- `forms.*` / `forms.view` … `forms.approve`
- Verificar wildcards órfãos após remoção Amazing (não há `products.*` hoje no seeder)

### DesignerRoleSeeder.php

- Role `designer` usada por admin staff / job cards.
- **Proposta:** remover ficheiro no Passo 5; role `technician` será criada no próximo sprint (fora desta limpeza).
- Remover call em `DatabaseSeeder` se ainda for adicionado no futuro (actualmente **não** está no `DatabaseSeeder` — só `DesignerRoleSeeder` existe como ficheiro avulso).

### DatabaseSeeder.php

Já limpo — apenas: Permission, Designer, User, Tenant, Construction.  
Após limpeza: remover `DesignerRoleSeeder::class` se mantivermos decisão acima.

### Seeders a apagar (ficheiros, não chamados)

Todos os listados nos passos 1–4.

---

## PASSO 6 — `routes/api.php` (estado alvo)

```php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/tenants.php';
require_once __DIR__ . '/permissions.php';
require_once __DIR__ . '/payment.php';
require_once __DIR__ . '/ai.php';
require_once __DIR__ . '/admin.php';
require_once __DIR__ . '/construction.php';
```

**Remover requires:**

- `product.php`, `design.php`, `cart.php`, `checkout.php`, `order.php`, `job_card.php`
- Comentário `candidate.php`

**Actualizar cabeçalho** do ficheiro (remover referência Amazing MVP).

### `bootstrap/providers.php` (estado alvo)

```php
App\Providers\AppServiceProvider::class,
App\Providers\AIServiceProvider::class,
// OrderServiceProvider removido se não houver orders
```

---

## PASSO 7 — Verificação (checklist)

Após cada passo:

```bash
php artisan route:list
composer dump-autoload
php artisan test
php artisan migrate --pretend   # validar migrations de drop
```

Procurar referências órfãs:

```bash
rg "JobCard|Candidate|FormTemplate|Product\\|Cart\\|Order\\" app bootstrap routes config
```

---

## PASSO 8 — Relatório final

Criar `docs/RELATORIO_POS_LIMPEZA.md` com contagens reais, riscos, endpoints quebrados no frontend, `.env.example` órfão.

---

## Ordem de execução e gates

| # | Passo | Gate |
|---|-------|------|
| 0 | Branch + docs | ✅ Feito |
| — | **Aprovação deste plano** | ⏳ **AGUARDAR USER** |
| 1 | Candidates | ✅ Concluído 2026-05-15 |
| 2 | Forms | OK explícito |
| 3 | Amazing (+ 3.A IA) | OK + decisão `orders` + `payment_proofs` |
| 4 | JobCards | OK explícito |
| 5 | Permissões/seeders | OK explícito |
| 6 | routes/api.php | OK explícito |
| 7 | Verificação | Automático |
| 8 | Relatório final | Automático |

---

## Perguntas em aberto para o Product Owner

1. **Drop `orders`:** correr script SQL de contagem na BD de staging/prod — há briefing real?
2. **`payment_proofs`:** manter para projectos ou remover?
3. **`DesignerRoleSeeder`:** remover já ou manter até existir role `technician`?
4. **`POST /v1/ai/suggestions` e `/mockup`:** desactivar já no Passo 3 ou manter temporariamente?

---

*Documento gerado no Passo 0. Nenhum ficheiro de aplicação foi removido.*

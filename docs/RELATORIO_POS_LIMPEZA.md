# Relatório pós-limpeza — Backend Construvasco

| Campo | Valor |
|-------|--------|
| **Data** | 2026-05-15 |
| **Branch** | `chore/backend-cleanup` |
| **Estado** | Limpeza concluída (passos 0–8) |

---

## a) Resumo numérico

| Métrica | Valor |
|---------|--------|
| **Models removidos** | ~39 (Candidate 7, Forms 4, Product 8, Cart 2, Design 2, Order 3, JobCard 4, Skill 1, + legado) |
| **Models que permanecem** | ~22 (User, Tenant, Project, Construction×11, PaymentProof, Activity, traits, invitations) |
| **Migrations de drop criadas** | 5 |
| **Ficheiros de rotas removidos** | 6 (`product`, `design`, `cart`, `checkout`, `order`, `job_card`, `candidate`) |
| **Rotas API activas** | ~55 (auth, user, tenants, permissions, payment, ai×4, admin, construction) |
| **Providers removidos** | 6 (FormEngine, Product, Cart, Checkout, Design, Order) |
| **Seeders removidos** | 11 |
| **Testes removidos** | 6 (Forms×3, Checkout×2, AI Suggestion×1) |
| **Tabelas eliminadas na BD** | 30+ (comércio, job cards, forms, candidates) |

### Migrations de drop

| Ficheiro | Tabelas |
|----------|---------|
| `2026_05_15_100000_remove_candidate_tables.php` | candidates, resumes, skills, … |
| `2026_05_15_110000_remove_form_engine_tables.php` | form_templates, form_instances, … |
| `2026_05_15_120000_remove_amazing_commerce_tables.php` | job_cards*, carts, orders, designs, products, … |
| `2026_05_15_130000_remove_job_card_tables.php` | no-op de segurança (já dropadas em 120000) |

\*Job cards incluídos na migration 120000 por ordem de FKs.

---

## b) O que sobra no backend (estado limpo)

### Domínio Construction (central)
- `Project`, `ServiceCategory`, `ConstructionService`
- `ProjectRequirement`, `ProjectDocument`, `ProjectEstimate`, `ProjectMilestone`
- `ProjectAssignment`, `ProjectDeliverable`, `ProjectPayment`, `ProjectInvoice`, `ProjectPortfolioItem`
- Rotas: `POST/GET projects`, `admin/projects`, `project-managers/...`, `portfolio/projects`

### Auth, tenants, permissions
- JWT (`auth.php`), `user.php`, `tenants.php`, `permissions.php`
- Spatie: roles `admin`, `project_manager`, `customer` (removidos `designer`, `forms.*`)

### IA Gemini (refactorizado)
- `ArchitecturalAiService` — renders **sem** `product_id`
- Endpoints: `POST /v1/ai/house`, `/floorplan`, `/refine`, `GET /health`
- **Removidos:** `/suggestions`, `/mockup`

### Pagamentos
- M-Pesa / e-Mola / proof-upload / webhook (`payment.php`)
- `payment_proofs` **mantida** (namespace corrigido em `PaymentProofController`)

### Admin
- Dashboard com métricas de **projectos** (não orders/job cards)
- Staff CRUD; `GET /admin/staff/designers` → project managers + admins

### Infra mantida
- Activity log, WhatsApp (`config/services.php`), Google Drive (`GoogleDriveService` para futuro `ProjectDeliverable`)

### `routes/api.php` final

```
auth, user, tenants, permissions, payment, ai, admin, construction
```

---

## c) Riscos e pontos de atenção

### Dados
- Tabelas `orders`, `products`, `designs`, `job_cards` **eliminadas** na BD local (migration aplicada com sucesso após correcção de ordem FK).
- Se staging/prod tiver briefing real em `orders`, era necessário migrar para `project_requirements` **antes** do migrate — assumido ambiente de desenvolvimento.

### Frontend (quebra temporária esperada)
Remover chamadas a:
- `/v1/products/*`, `/v1/cart/*`, `/v1/checkout/*`, `/v1/orders/*`
- `/v1/admin/job-cards/*`, `/v1/admin/orders/*`
- `/v1/ai/suggestions`, `/v1/ai/mockup`
- Payload IA: **já não enviar** `product_id` em `/v1/ai/house` e `/floorplan` — usar `design_prompt` (+ imagens opcionais)

### `.env.example`
- Variáveis de checkout/produtos podem ficar órfãs (limpeza cosmética futura).
- Manter: `GEMINI_*`, `PAYMENT_*`, `WHATSAPP_*`, `GOOGLE_DRIVE_*`

### Código legado
- Migrations históricas Amazing **mantidas** (só drops novos).
- `SendWhatsAppNotification` / listeners de checkout removidos com JobCard/Checkout — notificações de projecto a implementar depois.

### Seeder
- `ConstructionProjectSeeder` reescrito para `service_categories` + `construction_services` (já não usa `Product`).

---

## d) Próximo passo recomendado

1. **Role `technician`** com permissões `projects.edit`, entregáveis, marcos.
2. **Créditos** — `credit_packages`, `credit_balances`, `credit_transactions`.
3. **`AiGeneration`** — histórico desacoplado; middleware de débito antes de `/ai/house`.
4. **Templates de fases** — admin CRUD → instanciar `project_milestones`.
5. **Notificações in-app** — tabela + API; reutilizar WhatsApp para eventos de projecto.
6. **Frontend** — alinhar serviços Angular à API limpa; remover módulos admin job-cards/orders/catálogo.

---

## Verificação executada

| Comando | Resultado |
|---------|-----------|
| `php artisan route:list` | OK — sem rotas órfãs |
| `php artisan migrate --force` | OK — 5 migrations de drop |
| `php artisan test` | OK — 2 passed (smoke tests) |

---

*Relatório gerado após conclusão dos passos 1–8. Detalhe ficheiro a ficheiro em `docs/CHANGELOG_LIMPEZA.md`.*

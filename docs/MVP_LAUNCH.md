# Construvasco MVP v1 — Lançamento

## URLs

| Serviço | URL local |
|---------|-----------|
| API Laravel | http://127.0.0.1:8000 |
| Frontend Angular | http://127.0.0.1:4300 |

## Credenciais demo

| Papel | Email | Password |
|-------|-------|----------|
| Admin | admin@construvasco.co.mz | Admin@2026 |
| Gestor | gestor@construvasco.co.mz | Gestor@2026 |
| Técnico | tecnico@construvasco.co.mz | Tecnico@2026 |
| Cliente | cliente@construvasco.co.mz | Cliente@2026 |

## Preparar ambiente

```bash
cd construvasco_laravel_v1
composer install
cp .env.example .env   # se necessário
php artisan key:generate
php artisan jwt:secret
php artisan migrate --seed
php artisan serve
```

```bash
cd construvasco_frontend_v1
npm install
npm start
```

O seeder `DemoFlowSeeder` cria:

- **DEMO-PED-001** — orçamento pendente (cliente pode aceitar em `/conta/orcamentos/:id`)
- **DEMO-PED-002** — projecto já criado após aceite (visível em `/conta/projectos` e `/admin/projectos`)

## Smoke test (4 papéis)

### 1. Gestor — pedido → orçamento

1. Login `gestor@construvasco.co.mz`
2. `/admin/pedidos/lista` — ver pedidos demo
3. Abrir ficha → enviar orçamento OU `/admin/pedidos/novo` criar pedido (redirecciona para ficha)
4. Confirmar estado `quoted`

### 2. Cliente — aceitar orçamento

1. Login `cliente@construvasco.co.mz`
2. `/conta/dashboard` — contadores
3. `/conta/pedidos` — botão «Ver orçamento» no DEMO-PED-001
4. Aceitar → ver projecto em `/conta/projectos`

### 3. Gestor — projecto e técnico

1. `/admin/projectos` — lista
2. Abrir projecto → atribuir técnico

### 4. Técnico

1. Login `tecnico@construvasco.co.mz`
2. Menu: Painel, Meus projectos
3. Ver projectos atribuídos

## Deploy (checklist)

- [ ] `APP_URL`, `JWT_SECRET`, base de dados produção
- [ ] CORS: origem do frontend (porta 4300 ou domínio)
- [ ] `php artisan migrate --force` + seeders (ou só `DemoFlowSeeder` em staging)
- [ ] Build Angular: `environment.prod.ts` com URL da API HTTPS
- [ ] HTTPS na API e no frontend
- [ ] Google OAuth origins (se activo)

## Fora do MVP v1

- Pagamento M-Pesa da obra (`payFinal`)
- IA na UI
- Site público / e-commerce legado

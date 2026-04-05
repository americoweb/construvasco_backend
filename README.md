# Leeva Laravel Tenant Backend

Este projeto é o backend multi-tenant desenvolvido pela Leeva Digital Agency, pronto para integrar com qualquer frontend (Angular, React, etc.) e preparado para aplicações SaaS, plataformas multi-empresa, portais e sistemas de gestão.

## Visão Geral

- **Multi-tenant real:** Todos os dados são isolados por tenant (empresa/organização).
- **Escopo automático:** Todos os modelos relevantes usam a trait `Tenantable`, garantindo que queries, criação e atualização de dados respeitem o tenant ativo.
- **Gestão de tenants:** Usuários podem pertencer a múltiplos tenants, trocar de tenant ativo, e cada tenant pode ter configurações, features e usuários próprios.
- **APIs RESTful:** Endpoints prontos para autenticação, gestão de tenants, projetos, formulários, candidatos, permissões, etc.

## Como funciona a arquitetura multi-tenant

- **Isolamento de dados:** Cada registro relevante possui um campo `tenant_id` e só pode ser acessado pelo tenant correto.
- **Tenant ativo:** O backend determina o tenant ativo via sessão ou token JWT. Toda requisição autenticada retorna dados do tenant correto.
- **Troca de tenant:** O usuário pode trocar de tenant ativo via endpoint específico. O backend atualiza o contexto e todas as queries passam a respeitar o novo tenant.
- **Permissões e papéis:** Cada usuário pode ter diferentes papéis e permissões em cada tenant.

## Principais Endpoints

### Autenticação
- `POST /api/auth/login` — Login do usuário
- `POST /api/auth/logout` — Logout
- `POST /api/auth/refresh` — Refresh do token JWT
- `GET  /api/auth/me` — Dados do usuário autenticado

### Gestão de Tenant
- `GET  /api/user/tenants` — Lista todos os tenants do usuário
- `POST /api/user/switch-tenant` — Troca o tenant ativo do usuário
- `GET  /api/tenants/current` — Dados do tenant ativo
- `GET  /api/tenants/` — Lista de tenants (admin)
- `POST /api/tenants/` — Criação de novo tenant
- `GET  /api/tenants/users` — Lista de usuários do tenant
- `POST /api/tenants/users` — Adiciona usuário ao tenant
- `DELETE /api/tenants/users/{user}` — Remove usuário do tenant

### Permissões e Papéis
- `GET  /api/permissions/roles` — Lista de papéis
- `GET  /api/permissions/user` — Permissões do usuário no tenant
- `PUT  /api/permissions/user` — Atualiza permissões do usuário

### Recursos Multi-tenant
- `GET/POST/PUT/DELETE /api/projects` — Projetos do tenant
- `GET/POST/PUT/DELETE /api/form-templates` — Templates de formulário do tenant
- `GET/POST/PUT/DELETE /api/candidates` — Candidatos do tenant

## Fluxo típico de uso (Frontend)
1. **Usuário faz login** e recebe um token JWT.
2. **Frontend consome `/api/user/tenants`** para listar tenants disponíveis.
3. **Frontend chama `/api/user/switch-tenant`** para definir o tenant ativo (se o usuário tiver mais de um).
4. **Todas as requisições seguintes** retornam dados do tenant ativo automaticamente.
5. **Permissões e papéis** são respeitados conforme o tenant/contexto.

## Boas práticas de integração
- Sempre envie o token JWT no header `Authorization: Bearer <token>`.
- Após login, sempre defina o tenant ativo antes de consumir recursos multi-tenant.
- Use os endpoints de troca de tenant para alternar o contexto do usuário.
- O frontend **NÃO precisa** enviar o `tenant_id` manualmente — o backend gerencia isso.
- Para recursos globais (não atrelados a tenant), use endpoints específicos (ex: `/api/auth/me`).

## Estrutura dos principais modelos
- **User**: Pode pertencer a múltiplos tenants, tem papéis e permissões por tenant.
- **Tenant**: Tem usuários, configurações, features, status ativo/inativo.
- **Project, Candidate, Form, etc.**: Sempre vinculados a um tenant via `tenant_id`.

## Observações
- O backend pode ser integrado com qualquer frontend moderno.
- Toda a lógica de isolamento, permissões e contexto está centralizada aqui.
- Para dúvidas ou suporte, entre em contato: [info@leeva.agency](mailto:info@leeva.agency)

---

**Desenvolvido com ❤️ pela Leeva Digital Agency**

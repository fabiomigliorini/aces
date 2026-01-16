# ACES – Arquitetura, Docker e Operação

> **Projeto:** ACES (Associação Comercial e Empresarial de Sinop)
>
> **Stack principal:** Laravel + Quasar (SPA e SSR) + PostgreSQL + Cloudflare
>
> **Caminho padrão no servidor:** `/opt/www/aces`

---

## 1. Visão Geral da Arquitetura

A arquitetura foi desenhada para:

* suportar **multi-organização / multi-tenant**
* permitir **SEO forte** para o catálogo público
* manter **segurança e governança** no backend
* escalar com **IA, mídia pesada e marketplace**

### Domínios

| Domínio                 | Tipo    | Função                        |
| ----------------------- | ------- | ----------------------------- |
| `api.aces.org.br`       | Backend | API Laravel (core do sistema) |
| `admin.aces.org.br`     | SPA     | Admin da Associação           |
| `associado.aces.org.br` | SPA     | Admin das Empresas            |
| `guia.aces.org.br`      | SSR     | Catálogo público + Carrinho   |

---

## 2. Componentes do Sistema

### 2.1 Backend – Laravel (Core)

Responsável por:

* Autenticação (Sanctum)
* Autorização (roles, policies)
* Organizações e Tenants (filiais)
* Produtos, serviços e catálogo
* Carrinho e pedidos
* Integração com IA
* Integração com Cloudflare (R2 e Stream)
* Jobs assíncronos (IA, mídia)

Banco de dados: **PostgreSQL**

---

### 2.2 Frontend – Quasar

#### Admin da Associação

* SPA
* Sem SEO
* Usuários internos

#### Admin do Associado

* SPA
* Multi-tenant
* Gestão operacional

#### Guia (Catálogo Público)

* **Quasar SSR**
* SEO
* Indexação
* Carrinho de compras
* Login opcional

---

## 3. Estado e Cache

* **Pinia** é usado como cache de navegação
* Pinia **não é cache persistente global**
* Em SSR:

  * estado vive por request
  * após hidratação, comportamento igual SPA

Persistência:

* Cookies (SSR-safe)
* Backend (dados críticos)

---

## 4. Mídia (Imagens e Vídeos)

### Armazenamento

* **Cloudflare R2**

  * imagens
  * arquivos
  * vídeos simples

* **Cloudflare Stream**

  * vídeos públicos
  * vídeos institucionais
  * vídeos de produtos

### Organização de paths (exemplo)

```
organizations/
 └─ {organization_id}/
    ├─ logo/
    ├─ products/
    │  └─ {product_id}/
    │     ├─ images/
    │     └─ videos/
    └─ services/
```

---

## 5. Autenticação

* Laravel Sanctum
* Tokens via cookie HttpOnly
* Compatível com SPA e SSR

Configurações-chave:

```
SESSION_DOMAIN=.aces.org.br
SANCTUM_STATEFUL_DOMAINS=guia.aces.org.br,admin.aces.org.br,associado.aces.org.br
```

---

## 6. Estrutura do Repositório

```
/opt/www/aces
 ├─ laravel/                 # Backend
 ├─ frontend/
 │   ├─ admin/               # Quasar SPA
 │   ├─ associado/           # Quasar SPA
 │   └─ guia/                # Quasar SSR
 ├─ docker/
 │   └─ nginx/
 ├─ docker-compose.yml
 └─ README.md
```

---

## 7. Docker – Ambiente de Desenvolvimento

### 7.1 docker-compose.yml (dev)

```yaml
version: "3.9"

services:
  api:
    build: ./laravel
    volumes:
      - ./laravel:/var/www/html
    env_file:
      - ./laravel/.env
    depends_on:
      - postgres

  queue:
    build: ./laravel
    command: php artisan queue:work
    volumes:
      - ./laravel:/var/www/html
    depends_on:
      - api
      - postgres

  nginx:
    image: nginx:alpine
    ports:
      - "8080:80"
    volumes:
      - ./laravel:/var/www/html
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    depends_on:
      - api

  postgres:
    image: postgres:16-alpine
    environment:
      POSTGRES_DB: aces
      POSTGRES_USER: aces
      POSTGRES_PASSWORD: secret
    volumes:
      - pgdata:/var/lib/postgresql/data

volumes:
  pgdata:
```

---

### 7.2 Rodando em desenvolvimento

```bash
cd /opt/www/aces

docker compose up -d

# instalar dependências backend
docker compose exec api composer install

# gerar chave
docker compose exec api php artisan key:generate

# rodar migrations
docker compose exec api php artisan migrate
```

Frontend (exemplo guia SSR):

```bash
cd frontend/guia
npm install
quasar dev
```

---

## 8. Deploy (Produção)

### Estratégia Geral

* Backend Laravel em containers
* PostgreSQL **gerenciado** (fora do Docker)
* Cloudflare na frente (DNS + CDN)
* Frontends compilados

---

### Backend (Laravel)

1. Build da imagem
2. Variáveis de ambiente de produção
3. `php artisan migrate --force`
4. Rodar workers (queue)

Recomendado:

* Redis
* Laravel Horizon

---

### Frontend

#### Admin / Associado

```bash
quasar build
```

Servir via Nginx ou Cloudflare Pages.

#### Guia (SSR)

```bash
quasar build -m ssr
node dist/ssr/index.js
```

Ou:

* container Node
* Nginx como proxy

---

## 9. Princípios Arquiteturais (não quebrar)

1. Backend é a fonte da verdade
2. Organization é dona dos dados
3. Tenant é contexto operacional
4. IA sugere, humano decide
5. SSR apenas onde há SEO
6. Pinia é cache de navegação
7. Mídia sempre em object storage

---

## 10. Commit Inicial (sugestão)

Mensagem:

```
chore: initial architecture, docker setup and documentation
```

Inclui:

* estrutura de pastas
* docker-compose
* documentação

---

## 11. Próximos Passos

* Diagrama ER final
* Modelagem de usuários e roles
* Integração Cloudflare R2
* Integração Cloudflare Stream
* Pipelines CI/CD

---

**Este documento é a referência oficial da arquitetura do projeto ACES.**


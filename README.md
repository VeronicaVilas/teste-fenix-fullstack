# Fênix — Provas Online

Aplicação de provas online. Professores criam e publicam provas de múltipla escolha. Alunos respondem, recebem a nota na hora e revisam onde erraram.

**Stack:** Laravel 13 (PHP 8.4), Vue 3, PostgreSQL, Redis e Docker.

## Pré-requisitos

- [Git](https://git-scm.com/)
- [Docker](https://www.docker.com/) com Docker Compose

## Como baixar

```bash
git clone https://github.com/VeronicaVilas/teste-fenix-fullstack.git
cd teste-fenix-fullstack
```

## Como rodar

1. Crie o `.env` do backend a partir do exemplo:

   ```bash
   cp backend/.env.example backend/.env
   ```

2. Suba os containers:

   ```bash
   docker compose up -d --build
   ```

3. Gere a chave da aplicação e crie as tabelas e os usuários de acesso:

   ```bash
   docker compose exec backend php artisan key:generate
   docker compose exec backend php artisan migrate --seed
   ```

Pronto. Acesse:

| O quê | Endereço |
|---|---|
| Aplicação | http://localhost:5173 |
| Documentação da API (Swagger) | http://localhost:8000/api/documentation |

Não há login. Na tela inicial, escolha entrar como **Professor** ou como um dos **alunos**.

## Testes

Backend (com cobertura mínima de 80%):

```bash
docker compose exec backend php artisan test --coverage --min=80
```

Frontend:

```bash
docker compose exec frontend npm run test:unit -- --run
```

## Parar

```bash
docker compose down
```

> **Windows com a pasta no OneDrive:** se o build falhar com `invalid file request`, rode no PowerShell `$env:DOCKER_BUILDKIT="0"; $env:COMPOSE_BAKE="false"` antes do `docker compose up -d --build`.

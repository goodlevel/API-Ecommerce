# E-Commerce Product API 🛒

A complete API installation guide for product management with shopping cart functionality.

## 🐳 Docker Setup

### Prerequisites
- Docker

### Quick Start
```bash
# 1. Clone repository (if not already cloned)
mkdir Product\ API\ Back\ end\
cd Product\ API\ Back\ end\
git clone https://github.com/goodlevel/API-Ecommerce.git

# 2. Start containers
docker-compose up -d --build

# 3. Enter PHP container
docker-compose exec php bash

# 4. Install dependencies (inside container)
composer install

# 5. Setup database (inside container)
php bin/console doctrine:migrations:migrate

# 6. API Doc URL
http://localhost:8080/api/doc
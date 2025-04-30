<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424121928 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables products, users, user_carts, cart_items, user_wishlists and wishlist_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS products (
            `id` INT AUTO_INCREMENT NOT NULL,
            `code` VARCHAR(100) DEFAULT NULL,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `image` VARCHAR(255) DEFAULT NULL,
            `category` VARCHAR(100) DEFAULT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `quantity` INT NOT NULL DEFAULT 0,
            `internalReference` VARCHAR(100) DEFAULT NULL,
            `shellId` INT NOT NULL DEFAULT 0,
            `inventoryStatus` ENUM('INSTOCK','LOWSTOCK','OUTOFSTOCK') NOT NULL,
            `rating` FLOAT NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS users (
            `id` INT AUTO_INCREMENT NOT NULL,
            `username` VARCHAR(100) NOT NULL,
            `firstname` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS user_carts (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_USER_CART` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS cart_items (
            `id` INT AUTO_INCREMENT NOT NULL,
            `cart_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_CART_ITEMS_CART` FOREIGN KEY (`cart_id`) REFERENCES `user_carts` (`id`) ON DELETE CASCADE,
            CONSTRAINT `FK_CART_ITEMS_PRODUCT` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS user_wishlists (
            `id` INT AUTO_INCREMENT NOT NULL,
            `user_id` INT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_USER_WISHLIST` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");
        
        $this->addSql("CREATE TABLE IF NOT EXISTS wishlist_items (
            `id` INT AUTO_INCREMENT NOT NULL,
            `wishlist_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `FK_WISHLIST_ITEMS_WISHLIST` FOREIGN KEY (`wishlist_id`) REFERENCES `user_wishlists` (`id`) ON DELETE CASCADE,
            CONSTRAINT `FK_WISHLIST_ITEMS_PRODUCT` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;");

    }

    public function down(Schema $schema): void
    {

    }
}

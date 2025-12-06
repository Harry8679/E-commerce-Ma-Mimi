<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205095953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_72113DE6989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, author_id INT NOT NULL, post_id INT NOT NULL, INDEX IDX_7882EFEFF675F31B (author_id), INDEX IDX_7882EFEF4B89032C (post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_like (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, post_id INT NOT NULL, INDEX IDX_4CB3CC23A76ED395 (user_id), INDEX IDX_4CB3CC234B89032C (post_id), UNIQUE INDEX user_post_unique (user_id, post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE blog_post (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt LONGTEXT NOT NULL, content LONGTEXT NOT NULL, featured_image VARCHAR(255) DEFAULT NULL, is_published TINYINT NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, category_id INT NOT NULL, author_id INT NOT NULL, UNIQUE INDEX UNIQ_BA5AE01D989D9B62 (slug), INDEX IDX_BA5AE01D12469DE2 (category_id), INDEX IDX_BA5AE01DF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE blog_comment ADD CONSTRAINT FK_7882EFEFF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE blog_comment ADD CONSTRAINT FK_7882EFEF4B89032C FOREIGN KEY (post_id) REFERENCES blog_post (id)');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT FK_4CB3CC23A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE blog_like ADD CONSTRAINT FK_4CB3CC234B89032C FOREIGN KEY (post_id) REFERENCES blog_post (id)');
        $this->addSql('ALTER TABLE blog_post ADD CONSTRAINT FK_BA5AE01D12469DE2 FOREIGN KEY (category_id) REFERENCES blog_category (id)');
        $this->addSql('ALTER TABLE blog_post ADD CONSTRAINT FK_BA5AE01DF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE blog_comment DROP FOREIGN KEY FK_7882EFEFF675F31B');
        $this->addSql('ALTER TABLE blog_comment DROP FOREIGN KEY FK_7882EFEF4B89032C');
        $this->addSql('ALTER TABLE blog_like DROP FOREIGN KEY FK_4CB3CC23A76ED395');
        $this->addSql('ALTER TABLE blog_like DROP FOREIGN KEY FK_4CB3CC234B89032C');
        $this->addSql('ALTER TABLE blog_post DROP FOREIGN KEY FK_BA5AE01D12469DE2');
        $this->addSql('ALTER TABLE blog_post DROP FOREIGN KEY FK_BA5AE01DF675F31B');
        $this->addSql('DROP TABLE blog_category');
        $this->addSql('DROP TABLE blog_comment');
        $this->addSql('DROP TABLE blog_like');
        $this->addSql('DROP TABLE blog_post');
    }
}

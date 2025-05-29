<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250529172229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE bank_account_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE clip_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE comment_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE discussion_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE favoris_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE follow_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE line_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE live_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE live_products_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE message_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE "option_id_seq" INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE order_status_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE product_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE promotion_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE security_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE shipping_address_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE upload_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE user_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE variant_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE vendor_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE SEQUENCE withdraw_id_seq INCREMENT BY 1 MINVALUE 1 START 1
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE bank_account (id INT NOT NULL, vendor_id INT NOT NULL, bank_id VARCHAR(255) DEFAULT NULL, currency VARCHAR(255) NOT NULL, number VARCHAR(255) NOT NULL, last4 VARCHAR(255) NOT NULL, business_name VARCHAR(255) DEFAULT NULL, country_code VARCHAR(255) NOT NULL, holder_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_53A23E0AF603EE73 ON bank_account (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE category (id INT NOT NULL, name VARCHAR(255) NOT NULL, picture VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clip (id INT NOT NULL, vendor_id INT DEFAULT NULL, live_id INT NOT NULL, product_id INT NOT NULL, start INT NOT NULL, "end" INT NOT NULL, duration INT NOT NULL, file_list TEXT DEFAULT NULL, preview VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, total_likes INT DEFAULT NULL, job_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AD201467F603EE73 ON clip (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AD2014671DEBA901 ON clip (live_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_AD2014674584665A ON clip (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE comment (id INT NOT NULL, live_id INT DEFAULT NULL, user_id INT NOT NULL, clip_id INT DEFAULT NULL, content VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, is_vendor BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526C1DEBA901 ON comment (live_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526C3E19EFA5 ON comment (clip_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE discussion (id INT NOT NULL, user_id INT DEFAULT NULL, vendor_id INT DEFAULT NULL, purchase_id INT DEFAULT NULL, preview TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, unseen BOOLEAN DEFAULT NULL, unseen_vendor BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C0B9F90FA76ED395 ON discussion (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C0B9F90FF603EE73 ON discussion (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C0B9F90F558FBEB9 ON discussion (purchase_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE favoris (id INT NOT NULL, product_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8933C4324584665A ON favoris (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8933C432A76ED395 ON favoris (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE follow (id INT NOT NULL, following_id INT NOT NULL, follower_id INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_683444701816E3A3 ON follow (following_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_68344470AC24F853 ON follow (follower_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE line_item (id INT NOT NULL, product_id INT DEFAULT NULL, variant_id INT DEFAULT NULL, order_id_id INT NOT NULL, title VARCHAR(255) NOT NULL, quantity INT NOT NULL, price NUMERIC(8, 2) NOT NULL, total NUMERIC(8, 2) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9456D6C74584665A ON line_item (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9456D6C73B69A9AF ON line_item (variant_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9456D6C7FCDAEAAA ON line_item (order_id_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE live (id INT NOT NULL, vendor_id INT DEFAULT NULL, notice_id VARCHAR(255) DEFAULT NULL, status INT DEFAULT NULL, reason INT DEFAULT NULL, channel VARCHAR(255) DEFAULT NULL, event VARCHAR(255) DEFAULT NULL, display INT DEFAULT NULL, resource_id TEXT DEFAULT NULL, file_list TEXT DEFAULT NULL, sid TEXT DEFAULT NULL, cname TEXT DEFAULT NULL, preview VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, viewers INT DEFAULT NULL, total_viewers INT DEFAULT NULL, duration INT DEFAULT NULL, total_likes INT DEFAULT NULL, fb_stream_id VARCHAR(255) DEFAULT NULL, fb_stream_url VARCHAR(255) DEFAULT NULL, post_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_530F2CAFF603EE73 ON live (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE live_products (id INT NOT NULL, product_id INT NOT NULL, live_id INT DEFAULT NULL, priority INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_74EC2FAE4584665A ON live_products (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_74EC2FAE1DEBA901 ON live_products (live_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE message (id INT NOT NULL, discussion_id INT NOT NULL, from_user INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, picture VARCHAR(255) DEFAULT NULL, text TEXT DEFAULT NULL, picture_type VARCHAR(255) DEFAULT NULL, loading BOOLEAN DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B6BD307F1ADED311 ON message (discussion_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "option" (id INT NOT NULL, product_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, data TEXT NOT NULL, position INT NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_5A8600B04584665A ON "option" (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            COMMENT ON COLUMN "option".data IS '(DC2Type:array)'
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "order" (id INT NOT NULL, vendor_id INT NOT NULL, buyer_id INT NOT NULL, shipping_address_id INT DEFAULT NULL, promotion_id INT DEFAULT NULL, payment_id VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sub_total NUMERIC(8, 2) NOT NULL, total NUMERIC(8, 2) NOT NULL, fees NUMERIC(8, 2) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, shipping_price NUMERIC(8, 2) NOT NULL, number INT DEFAULT NULL, tracking_number VARCHAR(255) DEFAULT NULL, weight NUMERIC(8, 2) DEFAULT NULL, pdf VARCHAR(255) DEFAULT NULL, expected_delivery TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, shipping_status VARCHAR(255) DEFAULT NULL, identifier VARCHAR(255) NOT NULL, shipping_carrier_id VARCHAR(255) NOT NULL, shipping_carrier_name VARCHAR(255) NOT NULL, shipping_service_id VARCHAR(255) NOT NULL, shipping_service_name VARCHAR(255) NOT NULL, shipping_service_code VARCHAR(255) NOT NULL, dropoff_location_id VARCHAR(255) DEFAULT NULL, dropoff_country_code VARCHAR(255) DEFAULT NULL, dropoff_postcode VARCHAR(255) DEFAULT NULL, dropoff_name VARCHAR(255) DEFAULT NULL, delivered BOOLEAN DEFAULT NULL, incident_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, delivery_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, event_id VARCHAR(255) DEFAULT NULL, promotion_amount NUMERIC(8, 2) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F5299398F603EE73 ON "order" (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F52993986C755722 ON "order" (buyer_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F52993984D4CFF2B ON "order" (shipping_address_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F5299398139DF194 ON "order" (promotion_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE order_status (id INT NOT NULL, shipping_id INT DEFAULT NULL, code VARCHAR(255) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, location VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, postcode VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B88F75C94887F3F8 ON order_status (shipping_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE product (id INT NOT NULL, category_id INT NOT NULL, vendor_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, price NUMERIC(8, 2) NOT NULL, compare_at_price NUMERIC(8, 2) DEFAULT NULL, quantity INT NOT NULL, weight NUMERIC(8, 2) DEFAULT NULL, weight_unit VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04AD12469DE2 ON product (category_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D34A04ADF603EE73 ON product (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE promotion (id INT NOT NULL, vendor_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, value INT NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_C11D7DD1F603EE73 ON promotion (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE security_user (id INT NOT NULL, user_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, connected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, wifi_ipaddress VARCHAR(255) DEFAULT NULL, carrier_ipaddress VARCHAR(255) DEFAULT NULL, connection VARCHAR(255) DEFAULT NULL, model VARCHAR(255) DEFAULT NULL, platform VARCHAR(255) DEFAULT NULL, uuid VARCHAR(255) DEFAULT NULL, version VARCHAR(255) DEFAULT NULL, manufacturer VARCHAR(255) DEFAULT NULL, is_virtual BOOLEAN DEFAULT NULL, timezone VARCHAR(255) DEFAULT NULL, locale VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_52825A88A76ED395 ON security_user (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE shipping_address (id INT NOT NULL, user_id INT NOT NULL, address VARCHAR(255) NOT NULL, house_number VARCHAR(255) DEFAULT NULL, city VARCHAR(255) NOT NULL, zip VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, country_code VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, latitude VARCHAR(255) NOT NULL, longitude VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_EB066945A76ED395 ON shipping_address (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE upload (id INT NOT NULL, product_id INT DEFAULT NULL, filename VARCHAR(255) NOT NULL, position INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_17BDE61F4584665A ON upload (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE "user" (id INT NOT NULL, vendor_id INT DEFAULT NULL, hash VARCHAR(255) NOT NULL, push_token VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, email VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, picture VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, day VARCHAR(255) DEFAULT NULL, month VARCHAR(255) DEFAULT NULL, year VARCHAR(255) DEFAULT NULL, facebook_id VARCHAR(255) DEFAULT NULL, stripe_customer VARCHAR(255) DEFAULT NULL, apple_id VARCHAR(255) DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_8D93D649F603EE73 ON "user" (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE variant (id INT NOT NULL, product_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, price NUMERIC(8, 2) NOT NULL, compare_at_price NUMERIC(8, 2) DEFAULT NULL, quantity INT NOT NULL, position INT NOT NULL, option1 VARCHAR(255) DEFAULT NULL, option2 VARCHAR(255) DEFAULT NULL, weight NUMERIC(8, 2) DEFAULT NULL, weight_unit VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F143BFAD4584665A ON variant (product_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE vendor (id INT NOT NULL, company VARCHAR(255) DEFAULT NULL, summary VARCHAR(255) DEFAULT NULL, business_type VARCHAR(255) DEFAULT NULL, pseudo VARCHAR(255) DEFAULT NULL, siren VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, zip VARCHAR(255) DEFAULT NULL, pending NUMERIC(8, 2) DEFAULT NULL, available NUMERIC(8, 2) DEFAULT NULL, verified BOOLEAN DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, country_code VARCHAR(255) DEFAULT NULL, stripe_acc VARCHAR(255) DEFAULT NULL, person_id VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE withdraw (id INT NOT NULL, vendor_id INT DEFAULT NULL, payout_id VARCHAR(255) DEFAULT NULL, amount NUMERIC(8, 2) NOT NULL, status VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last4 VARCHAR(255) NOT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B5DE5F9EF603EE73 ON withdraw (vendor_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bank_account ADD CONSTRAINT FK_53A23E0AF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip ADD CONSTRAINT FK_AD201467F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip ADD CONSTRAINT FK_AD2014671DEBA901 FOREIGN KEY (live_id) REFERENCES live (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip ADD CONSTRAINT FK_AD2014674584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526C1DEBA901 FOREIGN KEY (live_id) REFERENCES live (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526C3E19EFA5 FOREIGN KEY (clip_id) REFERENCES clip (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF603EE73 FOREIGN KEY (vendor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F558FBEB9 FOREIGN KEY (purchase_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris ADD CONSTRAINT FK_8933C4324584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris ADD CONSTRAINT FK_8933C432A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE follow ADD CONSTRAINT FK_683444701816E3A3 FOREIGN KEY (following_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE follow ADD CONSTRAINT FK_68344470AC24F853 FOREIGN KEY (follower_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item ADD CONSTRAINT FK_9456D6C74584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item ADD CONSTRAINT FK_9456D6C73B69A9AF FOREIGN KEY (variant_id) REFERENCES variant (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item ADD CONSTRAINT FK_9456D6C7FCDAEAAA FOREIGN KEY (order_id_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live ADD CONSTRAINT FK_530F2CAFF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live_products ADD CONSTRAINT FK_74EC2FAE4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live_products ADD CONSTRAINT FK_74EC2FAE1DEBA901 FOREIGN KEY (live_id) REFERENCES live (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1ADED311 FOREIGN KEY (discussion_id) REFERENCES discussion (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "option" ADD CONSTRAINT FK_5A8600B04584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" ADD CONSTRAINT FK_F5299398F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" ADD CONSTRAINT FK_F52993986C755722 FOREIGN KEY (buyer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" ADD CONSTRAINT FK_F52993984D4CFF2B FOREIGN KEY (shipping_address_id) REFERENCES shipping_address (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" ADD CONSTRAINT FK_F5299398139DF194 FOREIGN KEY (promotion_id) REFERENCES promotion (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_status ADD CONSTRAINT FK_B88F75C94887F3F8 FOREIGN KEY (shipping_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product ADD CONSTRAINT FK_D34A04ADF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD1F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE security_user ADD CONSTRAINT FK_52825A88A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipping_address ADD CONSTRAINT FK_EB066945A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upload ADD CONSTRAINT FK_17BDE61F4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649F603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE variant ADD CONSTRAINT FK_F143BFAD4584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE withdraw ADD CONSTRAINT FK_B5DE5F9EF603EE73 FOREIGN KEY (vendor_id) REFERENCES vendor (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP SEQUENCE bank_account_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE category_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE clip_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE comment_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE discussion_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE favoris_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE follow_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE line_item_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE live_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE live_products_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE message_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE "option_id_seq" CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE "order_id_seq" CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE order_status_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE product_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE promotion_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE security_user_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE shipping_address_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE upload_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE user_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE variant_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE vendor_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP SEQUENCE withdraw_id_seq CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE bank_account DROP CONSTRAINT FK_53A23E0AF603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip DROP CONSTRAINT FK_AD201467F603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip DROP CONSTRAINT FK_AD2014671DEBA901
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clip DROP CONSTRAINT FK_AD2014674584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT FK_9474526C1DEBA901
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT FK_9474526CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT FK_9474526C3E19EFA5
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion DROP CONSTRAINT FK_C0B9F90FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion DROP CONSTRAINT FK_C0B9F90FF603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE discussion DROP CONSTRAINT FK_C0B9F90F558FBEB9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris DROP CONSTRAINT FK_8933C4324584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE favoris DROP CONSTRAINT FK_8933C432A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE follow DROP CONSTRAINT FK_683444701816E3A3
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE follow DROP CONSTRAINT FK_68344470AC24F853
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item DROP CONSTRAINT FK_9456D6C74584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item DROP CONSTRAINT FK_9456D6C73B69A9AF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE line_item DROP CONSTRAINT FK_9456D6C7FCDAEAAA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live DROP CONSTRAINT FK_530F2CAFF603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live_products DROP CONSTRAINT FK_74EC2FAE4584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE live_products DROP CONSTRAINT FK_74EC2FAE1DEBA901
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE message DROP CONSTRAINT FK_B6BD307F1ADED311
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "option" DROP CONSTRAINT FK_5A8600B04584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" DROP CONSTRAINT FK_F5299398F603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" DROP CONSTRAINT FK_F52993986C755722
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" DROP CONSTRAINT FK_F52993984D4CFF2B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "order" DROP CONSTRAINT FK_F5299398139DF194
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE order_status DROP CONSTRAINT FK_B88F75C94887F3F8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04AD12469DE2
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE product DROP CONSTRAINT FK_D34A04ADF603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE promotion DROP CONSTRAINT FK_C11D7DD1F603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE security_user DROP CONSTRAINT FK_52825A88A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE shipping_address DROP CONSTRAINT FK_EB066945A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE upload DROP CONSTRAINT FK_17BDE61F4584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649F603EE73
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE variant DROP CONSTRAINT FK_F143BFAD4584665A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE withdraw DROP CONSTRAINT FK_B5DE5F9EF603EE73
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE bank_account
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE category
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clip
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE comment
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discussion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE favoris
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE follow
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE line_item
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE live
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE live_products
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE message
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "option"
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "order"
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE order_status
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE product
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE promotion
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE security_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE shipping_address
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE upload
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE "user"
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE variant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE vendor
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE withdraw
        SQL);
    }
}

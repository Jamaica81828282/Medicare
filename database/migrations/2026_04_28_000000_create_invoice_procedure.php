<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create invoice_sequence table if it doesn't exist
        if (!Schema::hasTable('invoice_sequence')) {
            DB::unprepared('
                CREATE TABLE IF NOT EXISTS `invoice_sequence` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `sequence_year` year(4) NOT NULL,
                  `sequence_month` tinyint(4) NOT NULL,
                  `last_number` int(11) NOT NULL DEFAULT 0,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_year_month` (`sequence_year`,`sequence_month`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
            
            // Insert initial sequence
            DB::table('invoice_sequence')->insert([
                'sequence_year' => now()->year,
                'sequence_month' => now()->month,
                'last_number' => 0,
            ]);
        }

        // Create company_settings table if it doesn't exist
        if (!Schema::hasTable('company_settings')) {
            DB::unprepared('
                CREATE TABLE IF NOT EXISTS `company_settings` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `setting_key` varchar(100) NOT NULL,
                  `setting_value` text NOT NULL,
                  `setting_group` varchar(50) DEFAULT "general",
                  `data_type` enum("text","number","boolean","json","date") DEFAULT "text",
                  `is_editable` tinyint(1) DEFAULT 1,
                  `description` varchar(255) DEFAULT NULL,
                  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `setting_key` (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
            
            // Insert default settings
            DB::table('company_settings')->insert([
                ['setting_key' => 'invoice_prefix', 'setting_value' => 'INV', 'setting_group' => 'invoice', 'description' => 'Prefix for invoice numbers'],
                ['setting_key' => 'pharmacy_name', 'setting_value' => 'MediCare Pharmacy', 'setting_group' => 'branding'],
                ['setting_key' => 'pharmacy_tagline', 'setting_value' => 'Your Health, Our Priority', 'setting_group' => 'branding'],
            ]);
        }

        // Create or replace the stored procedure
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_invoice_number');
        
        $procedure = "
        CREATE PROCEDURE generate_invoice_number (OUT new_invoice_no VARCHAR(50))
        BEGIN
            DECLARE prefix VARCHAR(10);
            DECLARE last_seq INT;
            DECLARE yr YEAR;
            DECLARE mo TINYINT;

            SET yr = YEAR(NOW());
            SET mo = MONTH(NOW());

            -- Get prefix from settings
            SELECT COALESCE(setting_value, 'INV') INTO prefix FROM company_settings WHERE setting_key = 'invoice_prefix' LIMIT 1;

            -- If no prefix found, set default
            IF prefix IS NULL OR prefix = '' THEN
                SET prefix = 'INV';
            END IF;

            -- Lock row and increment
            INSERT INTO invoice_sequence (sequence_year, sequence_month, last_number)
                VALUES (yr, mo, 1)
                ON DUPLICATE KEY UPDATE last_number = last_number + 1;

            SELECT last_number INTO last_seq
                FROM invoice_sequence WHERE sequence_year = yr AND sequence_month = mo;

            SET new_invoice_no = CONCAT(prefix, '-', yr, '-', LPAD(last_seq, 5, '0'));
        END
        ";
        
        DB::unprepared($procedure);
    }

    public function down(): void
    {
        // Drop the procedure
        DB::unprepared('DROP PROCEDURE IF EXISTS generate_invoice_number');
    }
};

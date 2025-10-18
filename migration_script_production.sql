-- ========================================
-- SCRIPT DE MIGRAÇÃO PARA PRODUÇÃO
-- OvoWpp - WhatsApp CRM Platform
-- Data de Criação: 18 de Outubro de 2025
-- ========================================
-- 
-- INSTRUÇÕES DE USO:
-- 1. Faça backup completo do banco de dados ANTES de executar este script
-- 2. Execute este script no banco de dados de produção
-- 3. Verifique se todas as alterações foram aplicadas com sucesso
-- 
-- ========================================

-- Início da transação para garantir atomicidade
START TRANSACTION;

-- ========================================
-- 1. CRIAÇÃO DA TABELA: short_links
-- Migration: 2025_05_19_191546_create_short_links_table.php
-- ========================================
CREATE TABLE IF NOT EXISTS `short_links` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. CRIAÇÃO DA TABELA: floaters
-- Migration: 2025_05_21_202423_create_floaters_table.php
-- ========================================
CREATE TABLE IF NOT EXISTS `floaters` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. CRIAÇÃO DAS TABELAS DE PERMISSÕES (Spatie Permission Package)
-- Migration: 2025_08_18_191913_create_permission_tables.php
-- ========================================

-- 3.1. Tabela: permissions
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nome da permissão',
    `guard_name` VARCHAR(255) NOT NULL COMMENT 'Nome do guard (ex: web, api)',
    `group_name` VARCHAR(255) NOT NULL COMMENT 'Grupo/categoria da permissão',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.2. Tabela: roles
CREATE TABLE IF NOT EXISTS `roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nome da role/função',
    `guard_name` VARCHAR(255) NOT NULL COMMENT 'Nome do guard (ex: web, api)',
    `status` TINYINT NOT NULL DEFAULT 1 COMMENT 'Status da role: 1=Ativa, 0=Inativa',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `roles_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.3. Tabela: model_has_permissions (Relacionamento Polimórfico)
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID da permissão',
    `model_type` VARCHAR(255) NOT NULL COMMENT 'Tipo do modelo (ex: App\\Models\\User)',
    `model_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID do modelo',
    PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
    INDEX `model_has_permissions_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_permissions_permission_id_foreign` 
        FOREIGN KEY (`permission_id`) 
        REFERENCES `permissions` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.4. Tabela: model_has_roles (Relacionamento Polimórfico)
CREATE TABLE IF NOT EXISTS `model_has_roles` (
    `role_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID da role',
    `model_type` VARCHAR(255) NOT NULL COMMENT 'Tipo do modelo (ex: App\\Models\\User)',
    `model_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID do modelo',
    PRIMARY KEY (`role_id`, `model_id`, `model_type`),
    INDEX `model_has_roles_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `model_has_roles_role_id_foreign` 
        FOREIGN KEY (`role_id`) 
        REFERENCES `roles` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3.5. Tabela: role_has_permissions (Tabela Pivô)
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
    `permission_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID da permissão',
    `role_id` BIGINT UNSIGNED NOT NULL COMMENT 'ID da role',
    PRIMARY KEY (`permission_id`, `role_id`),
    CONSTRAINT `role_has_permissions_permission_id_foreign` 
        FOREIGN KEY (`permission_id`) 
        REFERENCES `permissions` (`id`) 
        ON DELETE CASCADE,
    CONSTRAINT `role_has_permissions_role_id_foreign` 
        FOREIGN KEY (`role_id`) 
        REFERENCES `roles` (`id`) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. ALTERAÇÃO DA TABELA: messages
-- Migration: 2025_10_17_162730_add_job_id_to_messages_table.php
-- Adiciona coluna job_id para rastreamento de jobs assíncronos do Baileys
-- ========================================
ALTER TABLE `messages` 
ADD COLUMN IF NOT EXISTS `job_id` VARCHAR(255) NULL DEFAULT NULL 
    COMMENT 'ID do job assíncrono para mensagens Baileys (Status SCHEDULED até confirmação)' 
    AFTER `whatsapp_message_id`,
ADD INDEX IF NOT EXISTS `messages_job_id_index` (`job_id`);

-- ========================================
-- 5. ALTERAÇÃO DA TABELA: ai_user_settings
-- Migration: 2025_10_18_114204_add_auto_reactivation_to_ai_user_settings_table.php
-- Adiciona colunas para configuração de reativação automática da IA após fallback
-- ========================================
ALTER TABLE `ai_user_settings` 
ADD COLUMN IF NOT EXISTS `auto_reactivate_ai` BOOLEAN NOT NULL DEFAULT FALSE 
    COMMENT 'Habilita/desabilita reativação automática da IA após fallback manual' 
    AFTER `fallback_response`,
ADD COLUMN IF NOT EXISTS `reactivation_delay_minutes` INT NULL DEFAULT NULL 
    COMMENT 'Tempo em minutos para reativar a IA (NULL = reativação imediata após resposta manual)' 
    AFTER `auto_reactivate_ai`;

-- ========================================
-- 6. ALTERAÇÃO DA TABELA: whatsapp_accounts
-- Migration: 2025_10_18_152000_add_connection_type_to_whatsapp_accounts_table.php
-- Adiciona coluna connection_type para seleção do tipo de conexão WhatsApp
-- ========================================
ALTER TABLE `whatsapp_accounts` 
ADD COLUMN IF NOT EXISTS `connection_type` TINYINT NOT NULL DEFAULT 1 
    COMMENT 'Tipo de conexão: 1=Meta WhatsApp Business API, 2=Baileys Direct Connection' 
    AFTER `baileys_phone_number`;

-- ========================================
-- VERIFICAÇÕES E VALIDAÇÕES
-- ========================================

-- Verificar se todas as tabelas foram criadas
SELECT 
    'short_links' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'short_links'
UNION ALL
SELECT 
    'floaters' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'floaters'
UNION ALL
SELECT 
    'permissions' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'permissions'
UNION ALL
SELECT 
    'roles' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'roles'
UNION ALL
SELECT 
    'model_has_permissions' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'model_has_permissions'
UNION ALL
SELECT 
    'model_has_roles' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'model_has_roles'
UNION ALL
SELECT 
    'role_has_permissions' AS tabela,
    CASE WHEN COUNT(*) > 0 THEN '✓ Criada' ELSE '✗ Não encontrada' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'role_has_permissions';

-- Verificar se as novas colunas foram adicionadas
SELECT 
    'messages.job_id' AS campo,
    CASE WHEN COUNT(*) > 0 THEN '✓ Adicionado' ELSE '✗ Não encontrado' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'messages' 
    AND column_name = 'job_id'
UNION ALL
SELECT 
    'ai_user_settings.auto_reactivate_ai' AS campo,
    CASE WHEN COUNT(*) > 0 THEN '✓ Adicionado' ELSE '✗ Não encontrado' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'ai_user_settings' 
    AND column_name = 'auto_reactivate_ai'
UNION ALL
SELECT 
    'ai_user_settings.reactivation_delay_minutes' AS campo,
    CASE WHEN COUNT(*) > 0 THEN '✓ Adicionado' ELSE '✗ Não encontrado' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'ai_user_settings' 
    AND column_name = 'reactivation_delay_minutes'
UNION ALL
SELECT 
    'whatsapp_accounts.connection_type' AS campo,
    CASE WHEN COUNT(*) > 0 THEN '✓ Adicionado' ELSE '✗ Não encontrado' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'whatsapp_accounts' 
    AND column_name = 'connection_type';

-- Confirmar transação se tudo estiver correto
COMMIT;

-- ========================================
-- RESUMO DAS ALTERAÇÕES
-- ========================================
-- 
-- TABELAS CRIADAS:
-- 1. short_links - Tabela para gerenciamento de links curtos
-- 2. floaters - Tabela para elementos flutuantes
-- 3. permissions - Permissões do sistema (Spatie Permission)
-- 4. roles - Funções/Roles do sistema (Spatie Permission)
-- 5. model_has_permissions - Relação polimórfica modelo-permissões
-- 6. model_has_roles - Relação polimórfica modelo-roles
-- 7. role_has_permissions - Relação roles-permissões
-- 
-- COLUNAS ADICIONADAS:
-- 1. messages.job_id - Rastreamento de jobs Baileys (VARCHAR(255), NULL, com INDEX)
--    Usado para mensagens com status SCHEDULED até confirmação webhook
-- 
-- 2. ai_user_settings.auto_reactivate_ai - Habilita reativação automática da IA (BOOLEAN, DEFAULT FALSE)
--    Permite que a IA seja reativada após fallback manual
-- 
-- 3. ai_user_settings.reactivation_delay_minutes - Tempo para reativação (INT, NULL)
--    Define atraso em minutos para reativação (NULL = imediato após resposta manual)
-- 
-- 4. whatsapp_accounts.connection_type - Tipo de conexão WhatsApp (TINYINT, DEFAULT 1)
--    1 = Meta WhatsApp Business API
--    2 = Baileys Direct Connection
--    Permite seleção explícita do método de conexão via UI
-- 
-- ÍNDICES CRIADOS:
-- 1. messages_job_id_index - Índice na coluna job_id da tabela messages
-- 
-- VALORES PADRÃO CONFIGURADOS:
-- 1. roles.status = 1 (Ativa por padrão)
-- 2. ai_user_settings.auto_reactivate_ai = FALSE (Desabilitado por padrão)
-- 3. ai_user_settings.reactivation_delay_minutes = NULL (Reativação imediata)
-- 4. whatsapp_accounts.connection_type = 1 (Meta API por padrão)
-- 
-- ========================================

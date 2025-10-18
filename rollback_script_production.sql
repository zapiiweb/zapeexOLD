-- ========================================
-- SCRIPT DE ROLLBACK (REVERSÃO) PARA PRODUÇÃO
-- OvoWpp - WhatsApp CRM Platform
-- Data de Criação: 18 de Outubro de 2025
-- ========================================
-- 
-- ⚠️ ATENÇÃO: Este script REVERTE todas as alterações feitas pela migração
-- Use apenas se precisar desfazer as mudanças aplicadas
-- 
-- INSTRUÇÕES DE USO:
-- 1. Faça backup completo do banco de dados ANTES de executar este script
-- 2. Execute este script apenas se precisar reverter a migração
-- 3. Este script removerá tabelas e colunas criadas pela migração
-- 
-- ========================================

-- Início da transação
START TRANSACTION;

-- ========================================
-- REVERSÃO DAS ALTERAÇÕES - ORDEM INVERSA
-- ========================================

-- 6. REMOVER coluna connection_type da tabela whatsapp_accounts
ALTER TABLE `whatsapp_accounts` 
DROP COLUMN IF EXISTS `connection_type`;

-- 5. REMOVER colunas de reativação da IA da tabela ai_user_settings
ALTER TABLE `ai_user_settings` 
DROP COLUMN IF EXISTS `reactivation_delay_minutes`,
DROP COLUMN IF EXISTS `auto_reactivate_ai`;

-- 4. REMOVER coluna job_id da tabela messages
ALTER TABLE `messages` 
DROP COLUMN IF EXISTS `job_id`;

-- 3. REMOVER tabelas de permissões (em ordem correta devido às foreign keys)
DROP TABLE IF EXISTS `role_has_permissions`;
DROP TABLE IF EXISTS `model_has_roles`;
DROP TABLE IF EXISTS `model_has_permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `permissions`;

-- 2. REMOVER tabela floaters
DROP TABLE IF EXISTS `floaters`;

-- 1. REMOVER tabela short_links
DROP TABLE IF EXISTS `short_links`;

-- ========================================
-- VERIFICAÇÕES
-- ========================================

-- Verificar se as tabelas foram removidas
SELECT 
    'short_links' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'short_links'
UNION ALL
SELECT 
    'floaters' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'floaters'
UNION ALL
SELECT 
    'permissions' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'permissions'
UNION ALL
SELECT 
    'roles' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'roles'
UNION ALL
SELECT 
    'model_has_permissions' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'model_has_permissions'
UNION ALL
SELECT 
    'model_has_roles' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'model_has_roles'
UNION ALL
SELECT 
    'role_has_permissions' AS tabela,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removida' ELSE '✗ Ainda existe' END AS status
FROM information_schema.tables 
WHERE table_schema = DATABASE() AND table_name = 'role_has_permissions';

-- Verificar se as colunas foram removidas
SELECT 
    'messages.job_id' AS campo,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removido' ELSE '✗ Ainda existe' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'messages' 
    AND column_name = 'job_id'
UNION ALL
SELECT 
    'ai_user_settings.auto_reactivate_ai' AS campo,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removido' ELSE '✗ Ainda existe' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'ai_user_settings' 
    AND column_name = 'auto_reactivate_ai'
UNION ALL
SELECT 
    'ai_user_settings.reactivation_delay_minutes' AS campo,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removido' ELSE '✗ Ainda existe' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'ai_user_settings' 
    AND column_name = 'reactivation_delay_minutes'
UNION ALL
SELECT 
    'whatsapp_accounts.connection_type' AS campo,
    CASE WHEN COUNT(*) = 0 THEN '✓ Removido' ELSE '✗ Ainda existe' END AS status
FROM information_schema.columns 
WHERE table_schema = DATABASE() 
    AND table_name = 'whatsapp_accounts' 
    AND column_name = 'connection_type';

-- Confirmar transação
COMMIT;

-- ========================================
-- AVISO IMPORTANTE
-- ========================================
-- 
-- ⚠️ DADOS PERDIDOS APÓS ROLLBACK:
-- 
-- Este rollback irá remover PERMANENTEMENTE:
-- 
-- 1. Todas as permissões criadas no sistema
-- 2. Todas as roles/funções criadas
-- 3. Todas as atribuições de permissões a usuários
-- 4. Todos os links curtos criados
-- 5. Todos os floaters criados
-- 6. Configurações de tipo de conexão WhatsApp (voltará a Meta API)
-- 7. Configurações de auto-reativação da IA
-- 8. IDs de jobs de mensagens Baileys
-- 
-- CERTIFIQUE-SE de que você realmente precisa reverter antes de executar!
-- 
-- ========================================

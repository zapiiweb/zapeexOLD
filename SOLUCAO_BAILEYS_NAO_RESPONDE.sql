-- ========================================
-- SOLUÇÃO: BAILEYS NÃO RESPONDE AUTOMATICAMENTE
-- ========================================
-- PROBLEMA: needs_human_reply = 1 nas conversas do Baileys
-- 
-- Execute este script no seu banco de dados externo
-- ========================================

-- 1. IDENTIFICAR CONVERSAS DO BAILEYS vs META API
SELECT 
    'CONVERSAS POR TIPO DE CONEXÃO' as diagnostico,
    c.id as conversation_id,
    c.user_id,
    c.whatsapp_account_id,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as tipo,
    wa.number,
    c.needs_human_reply,
    CASE 
        WHEN c.needs_human_reply = 0 THEN '✅ IA pode responder'
        WHEN c.needs_human_reply = 1 THEN '❌ BLOQUEADO - Aguardando humano'
        ELSE '⚠️ Valor inválido'
    END as status_ia,
    c.last_message_at
FROM conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
ORDER BY wa.connection_type, c.id DESC;

-- ========================================
-- 2. RESETAR needs_human_reply APENAS PARA BAILEYS (connection_type = 2)
-- ========================================
-- Descomente a linha abaixo para resetar TODAS as conversas do Baileys
-- UPDATE conversations c
-- JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
-- SET c.needs_human_reply = 0,
--     c.updated_at = NOW()
-- WHERE wa.connection_type = 2;

-- ========================================
-- 3. RESETAR CONVERSA ESPECÍFICA DO BAILEYS
-- ========================================
-- Se você sabe qual conversation_id está testando, use este:
-- Exemplo: conversation_id = 20 (visto nos logs)
UPDATE conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
SET c.needs_human_reply = 0,
    c.updated_at = NOW()
WHERE c.id = 20 
  AND wa.connection_type = 2;

-- ========================================
-- 4. VERIFICAR SE FUNCIONOU
-- ========================================
SELECT 
    'APÓS CORREÇÃO - BAILEYS' as diagnostico,
    c.id as conversation_id,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 2 THEN '✅ Baileys'
        ELSE 'Meta API'
    END as tipo,
    c.needs_human_reply,
    CASE 
        WHEN c.needs_human_reply = 0 THEN '✅✅✅ IA VAI RESPONDER AGORA!'
        ELSE '❌ Ainda bloqueado'
    END as resultado,
    c.updated_at
FROM conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
WHERE wa.connection_type = 2
ORDER BY c.id DESC
LIMIT 10;

-- ========================================
-- 5. DIAGNÓSTICO COMPLETO POR CONVERSA
-- ========================================
SELECT 
    'DIAGNÓSTICO BAILEYS vs META API' as diagnostico,
    c.id,
    CASE 
        WHEN wa.connection_type = 1 THEN '🔵 Meta API'
        WHEN wa.connection_type = 2 THEN '🟢 Baileys'
        ELSE '⚪ Outro'
    END as tipo,
    c.needs_human_reply,
    u.ai_assistance,
    uas.status as ai_settings_status,
    CASE 
        WHEN c.needs_human_reply = 0 
            AND u.ai_assistance = 1 
            AND uas.status = 1
        THEN '✅ IA ATIVA'
        WHEN c.needs_human_reply = 1 
        THEN '❌ AGUARDANDO HUMANO'
        WHEN u.ai_assistance = 0 
        THEN '❌ AI_ASSISTANCE DESABILITADA'
        WHEN uas.status = 0 
        THEN '❌ AI_SETTINGS DESABILITADA'
        ELSE '⚠️ OUTRO PROBLEMA'
    END as status_final
FROM conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
JOIN users u ON u.id = c.user_id
LEFT JOIN user_ai_settings uas ON uas.user_id = c.user_id
ORDER BY c.id DESC
LIMIT 20;

-- ========================================
-- RESUMO
-- ========================================
SELECT '========================================' as '';
SELECT 'RESUMO' as '';
SELECT '========================================' as '';

SELECT 
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Outro'
    END as tipo_conexao,
    COUNT(*) as total_conversas,
    SUM(CASE WHEN c.needs_human_reply = 0 THEN 1 ELSE 0 END) as ia_ativa,
    SUM(CASE WHEN c.needs_human_reply = 1 THEN 1 ELSE 0 END) as aguardando_humano
FROM conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
GROUP BY wa.connection_type;

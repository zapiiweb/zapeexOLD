-- ========================================
-- DIAGNÓSTICO COMPLETO - POR QUE A IA NÃO RESPONDE?
-- ========================================
-- Execute este script no seu banco de dados externo

-- ========================================
-- 1. VERIFICAR CONVERSAS - needs_human_reply
-- ========================================
SELECT 
    '1. ESTADO DAS CONVERSAS' as diagnostico,
    c.id,
    c.user_id,
    c.whatsapp_account_id,
    c.needs_human_reply,
    CASE 
        WHEN c.needs_human_reply = 0 THEN '✅ OK - IA pode responder'
        WHEN c.needs_human_reply = 1 THEN '❌ BLOQUEADO - Aguardando humano'
        ELSE '⚠️ VALOR INVÁLIDO'
    END as status_ia,
    c.last_message_at,
    c.created_at
FROM conversations c
ORDER BY c.id DESC
LIMIT 20;

-- ========================================
-- 2. VERIFICAR CONFIGURAÇÕES DA IA (user_ai_settings)
-- ========================================
SELECT 
    '2. CONFIGURAÇÕES DE IA' as diagnostico,
    uas.id,
    uas.user_id,
    uas.status as ai_status,
    CASE 
        WHEN uas.status = 1 THEN '✅ IA ATIVA'
        WHEN uas.status = 0 THEN '❌ IA DESABILITADA'
        ELSE '⚠️ STATUS INVÁLIDO'
    END as status_texto,
    uas.auto_reactivate_ai,
    uas.reactivation_delay_minutes,
    CASE 
        WHEN uas.fallback_response IS NOT NULL THEN '✅ Configurado'
        ELSE '❌ NÃO configurado'
    END as tem_fallback,
    LEFT(uas.fallback_response, 50) as fallback_preview,
    CASE 
        WHEN uas.system_prompt IS NOT NULL THEN '✅ Configurado'
        ELSE '❌ NÃO configurado'
    END as tem_system_prompt
FROM user_ai_settings uas
ORDER BY uas.user_id;

-- ========================================
-- 3. VERIFICAR USUÁRIOS - ai_assistance
-- ========================================
SELECT 
    '3. USUÁRIOS - AI_ASSISTANCE' as diagnostico,
    u.id as user_id,
    u.username,
    u.email,
    u.ai_assistance,
    CASE 
        WHEN u.ai_assistance = 1 THEN '✅ AI HABILITADA para este usuário'
        WHEN u.ai_assistance = 0 THEN '❌ AI DESABILITADA para este usuário'
        ELSE '⚠️ VALOR INVÁLIDO'
    END as ai_status
FROM users u
WHERE u.user_type = 1 -- usuários normais (não admin)
ORDER BY u.id;

-- ========================================
-- 4. VERIFICAR PROVIDER DE IA ATIVO (OpenAI/Gemini)
-- ========================================
SELECT 
    '4. PROVIDER DE IA' as diagnostico,
    aa.id,
    aa.provider,
    aa.status,
    CASE 
        WHEN aa.status = 1 THEN '✅ PROVIDER ATIVO'
        WHEN aa.status = 0 THEN '❌ PROVIDER INATIVO'
        ELSE '⚠️ STATUS INVÁLIDO'
    END as status_texto,
    CASE 
        WHEN aa.api_key IS NOT NULL AND aa.api_key != '' THEN '✅ API Key configurada'
        ELSE '❌ API Key FALTANDO'
    END as tem_api_key,
    aa.created_at
FROM ai_assistants aa
ORDER BY aa.status DESC, aa.id DESC;

-- ========================================
-- 5. VERIFICAR CONTATOS (contact deve existir)
-- ========================================
SELECT 
    '5. CONTATOS DAS CONVERSAS' as diagnostico,
    c.id as conversation_id,
    c.user_id,
    c.contact_id,
    CASE 
        WHEN c.contact_id IS NOT NULL THEN '✅ Contato existe'
        ELSE '❌ SEM CONTATO - IA não pode responder!'
    END as tem_contato,
    co.mobile_number,
    co.name
FROM conversations c
LEFT JOIN contacts co ON co.id = c.contact_id
ORDER BY c.id DESC
LIMIT 20;

-- ========================================
-- 6. VERIFICAR CONTAS WHATSAPP
-- ========================================
SELECT 
    '6. CONTAS WHATSAPP' as diagnostico,
    wa.id,
    wa.user_id,
    wa.number,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as tipo_conexao,
    wa.status,
    CASE 
        WHEN wa.status = 1 THEN '✅ Ativa'
        WHEN wa.status = 0 THEN '❌ Inativa'
        ELSE '⚠️ Status inválido'
    END as status_texto
FROM whatsapp_accounts wa
ORDER BY wa.user_id, wa.id;

-- ========================================
-- 7. DIAGNÓSTICO COMPLETO - TODAS AS CONDIÇÕES
-- ========================================
SELECT 
    '7. DIAGNÓSTICO COMPLETO POR CONVERSA' as diagnostico,
    c.id as conversation_id,
    c.user_id,
    c.whatsapp_account_id,
    
    -- Condição 1: needs_human_reply deve ser 0
    CASE 
        WHEN c.needs_human_reply = 0 THEN '✅'
        ELSE '❌ needs_human_reply = 1'
    END as check_needs_human,
    
    -- Condição 2: user_ai_settings deve existir
    CASE 
        WHEN uas.id IS NOT NULL THEN '✅'
        ELSE '❌ SEM user_ai_settings'
    END as check_ai_settings,
    
    -- Condição 3: IA deve estar habilitada (status = 1)
    CASE 
        WHEN uas.status = 1 THEN '✅'
        WHEN uas.status = 0 THEN '❌ IA desabilitada'
        ELSE '❌ SEM status'
    END as check_ai_status,
    
    -- Condição 4: contact deve existir
    CASE 
        WHEN c.contact_id IS NOT NULL THEN '✅'
        ELSE '❌ SEM contato'
    END as check_contact,
    
    -- Condição 5: ai_assistance do usuário deve ser 1
    CASE 
        WHEN u.ai_assistance = 1 THEN '✅'
        WHEN u.ai_assistance = 0 THEN '❌ ai_assistance = 0'
        ELSE '❌ SEM ai_assistance'
    END as check_user_ai,
    
    -- Condição 6: provider ativo deve existir
    CASE 
        WHEN EXISTS(SELECT 1 FROM ai_assistants WHERE status = 1) THEN '✅'
        ELSE '❌ SEM provider ativo'
    END as check_provider,
    
    -- Condição 7: conta WhatsApp deve existir
    CASE 
        WHEN wa.id IS NOT NULL THEN '✅'
        ELSE '❌ SEM conta WhatsApp'
    END as check_whatsapp_account,
    
    -- RESULTADO FINAL
    CASE 
        WHEN c.needs_human_reply = 0 
            AND uas.id IS NOT NULL 
            AND uas.status = 1 
            AND c.contact_id IS NOT NULL
            AND u.ai_assistance = 1
            AND EXISTS(SELECT 1 FROM ai_assistants WHERE status = 1)
            AND wa.id IS NOT NULL
        THEN '✅✅✅ IA DEVERIA RESPONDER!'
        ELSE '❌❌❌ IA NÃO VAI RESPONDER - veja os checks acima'
    END as resultado_final

FROM conversations c
LEFT JOIN users u ON u.id = c.user_id
LEFT JOIN user_ai_settings uas ON uas.user_id = c.user_id
LEFT JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
ORDER BY c.id DESC
LIMIT 20;

-- ========================================
-- 8. MENSAGENS RECENTES PARA DEBUG
-- ========================================
SELECT 
    '8. MENSAGENS RECENTES' as diagnostico,
    m.id,
    m.conversation_id,
    m.type,
    CASE 
        WHEN m.type = 0 THEN 'Recebida'
        WHEN m.type = 1 THEN 'Enviada'
        ELSE 'Outro'
    END as tipo_msg,
    m.ai_reply,
    CASE 
        WHEN m.ai_reply = 1 THEN 'Resposta IA'
        ELSE 'Manual/Cliente'
    END as origem,
    LEFT(m.message, 100) as message_preview,
    m.created_at
FROM messages m
ORDER BY m.id DESC
LIMIT 30;

-- ========================================
-- RESUMO FINAL
-- ========================================
SELECT '========================================' as '';
SELECT 'RESUMO DO DIAGNÓSTICO' as '';
SELECT '========================================' as '';

SELECT 
    'Total de conversas' as metrica,
    COUNT(*) as valor
FROM conversations
UNION ALL
SELECT 
    'Conversas aguardando humano (needs_human_reply=1)',
    COUNT(*)
FROM conversations
WHERE needs_human_reply = 1
UNION ALL
SELECT 
    'Conversas com IA ativa (needs_human_reply=0)',
    COUNT(*)
FROM conversations
WHERE needs_human_reply = 0
UNION ALL
SELECT 
    'Usuários com IA habilitada (ai_assistance=1)',
    COUNT(*)
FROM users
WHERE ai_assistance = 1 AND user_type = 1
UNION ALL
SELECT 
    'Usuários com IA desabilitada (ai_assistance=0)',
    COUNT(*)
FROM users
WHERE ai_assistance = 0 AND user_type = 1
UNION ALL
SELECT 
    'Configurações de IA ativas (status=1)',
    COUNT(*)
FROM user_ai_settings
WHERE status = 1
UNION ALL
SELECT 
    'Providers de IA ativos',
    COUNT(*)
FROM ai_assistants
WHERE status = 1;

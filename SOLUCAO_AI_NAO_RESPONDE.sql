-- ========================================
-- SOLUÇÃO: IA NÃO RESPONDE
-- ========================================
-- PROBLEMA IDENTIFICADO: user.ai_assistance = 0
-- 
-- A IA verifica o campo 'ai_assistance' na tabela 'users'
-- Se esse campo é 0, a IA não responde MESMO com needs_human_reply = 0
--
-- Execute este script no seu banco de dados externo
-- ========================================

-- 1. VERIFICAR O ESTADO ATUAL
SELECT 
    'ESTADO ATUAL DOS USUÁRIOS' as diagnostico,
    id,
    username,
    email,
    ai_assistance,
    CASE 
        WHEN ai_assistance = 1 THEN '✅ IA HABILITADA'
        WHEN ai_assistance = 0 THEN '❌ IA DESABILITADA - Por isso não responde!'
        ELSE '⚠️ VALOR INVÁLIDO'
    END as status
FROM users
WHERE user_type = 1 -- apenas usuários normais
ORDER BY id;

-- ========================================
-- 2. SOLUÇÃO: HABILITAR AI PARA TODOS OS USUÁRIOS
-- ========================================
-- Descomente a linha abaixo para habilitar IA para TODOS os usuários
-- UPDATE users SET ai_assistance = 1 WHERE user_type = 1;

-- ========================================
-- 3. SOLUÇÃO: HABILITAR AI PARA USUÁRIO ESPECÍFICO
-- ========================================
-- Substitua USER_ID_AQUI pelo ID do usuário (ex: 1)
-- UPDATE users SET ai_assistance = 1 WHERE id = USER_ID_AQUI;

-- Exemplo para usuário ID 1:
UPDATE users SET ai_assistance = 1 WHERE id = 1;

-- ========================================
-- 4. VERIFICAR SE A CORREÇÃO FUNCIONOU
-- ========================================
SELECT 
    'APÓS CORREÇÃO' as diagnostico,
    id,
    username,
    email,
    ai_assistance,
    CASE 
        WHEN ai_assistance = 1 THEN '✅ IA HABILITADA - Agora deve responder!'
        WHEN ai_assistance = 0 THEN '❌ AINDA DESABILITADA'
        ELSE '⚠️ VALOR INVÁLIDO'
    END as status
FROM users
WHERE user_type = 1
ORDER BY id;

-- ========================================
-- 5. DIAGNÓSTICO COMPLETO PÓS-CORREÇÃO
-- ========================================
SELECT 
    '5. DIAGNÓSTICO COMPLETO' as diagnostico,
    c.id as conversation_id,
    c.user_id,
    c.needs_human_reply,
    u.ai_assistance,
    uas.status as ai_settings_status,
    CASE 
        WHEN c.needs_human_reply = 0 
            AND u.ai_assistance = 1 
            AND uas.status = 1
        THEN '✅✅✅ IA VAI RESPONDER AGORA!'
        ELSE '❌ Ainda há problemas - veja os valores acima'
    END as resultado
FROM conversations c
JOIN users u ON u.id = c.user_id
LEFT JOIN user_ai_settings uas ON uas.user_id = c.user_id
ORDER BY c.id DESC
LIMIT 10;

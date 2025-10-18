-- Script completo para verificar configuração de reativação automática da IA

-- 1. Verificar estado das conversas
SELECT 
    'Estado das Conversas' as secao,
    c.id as conversation_id,
    c.whatsapp_account_id,
    c.needs_human_reply,
    c.created_at,
    c.updated_at
FROM conversations c
WHERE (c.id = 7 AND c.whatsapp_account_id = 3) 
   OR (c.id = 19 AND c.whatsapp_account_id = 1);

-- 2. Verificar configurações da IA por usuário
SELECT 
    'Configurações AI' as secao,
    uas.user_id,
    uas.auto_reactivate_ai,
    uas.reactivation_delay_minutes,
    uas.fallback_response,
    uas.status as ai_status,
    u.ai_assistance
FROM user_ai_settings uas
JOIN users u ON u.id = uas.user_id
WHERE uas.user_id IN (
    SELECT user_id FROM conversations 
    WHERE (id = 7 AND whatsapp_account_id = 3) 
       OR (id = 19 AND whatsapp_account_id = 1)
);

-- 3. Verificar última mensagem de fallback por conversa
SELECT 
    'Últimas Mensagens Fallback' as secao,
    m.id,
    m.conversation_id,
    m.ai_reply,
    m.type,
    m.message,
    m.created_at,
    TIMESTAMPDIFF(MINUTE, m.created_at, NOW()) as minutes_ago
FROM messages m
WHERE m.conversation_id IN (7, 19)
  AND m.ai_reply = 1 
  AND m.type = 1
ORDER BY m.conversation_id, m.created_at DESC;

-- 4. Verificar se há provider de IA ativo
SELECT 
    'Provider IA Ativo' as secao,
    id,
    provider,
    status,
    api_key
FROM ai_assistants
WHERE status = 1
LIMIT 1;

-- Script para resetar conversas para teste de reativação automática da IA
-- Execute este script no seu banco de dados externo

-- Verificar o estado atual das conversas
SELECT 
    id, 
    whatsapp_account_id, 
    needs_human_reply,
    created_at,
    updated_at
FROM conversations 
WHERE (id = 7 AND whatsapp_account_id = 3) 
   OR (id = 19 AND whatsapp_account_id = 1);

-- Resetar needs_human_reply para NO (0) para poder testar novamente
UPDATE conversations 
SET needs_human_reply = 0,
    updated_at = NOW()
WHERE (id = 7 AND whatsapp_account_id = 3) 
   OR (id = 19 AND whatsapp_account_id = 1);

-- Verificar se a atualização funcionou
SELECT 
    id, 
    whatsapp_account_id, 
    needs_human_reply,
    updated_at as 'updated_just_now'
FROM conversations 
WHERE (id = 7 AND whatsapp_account_id = 3) 
   OR (id = 19 AND whatsapp_account_id = 1);

-- INFORMAÇÕES IMPORTANTES:
-- needs_human_reply = 0 (NO): IA ativa, responde automaticamente
-- needs_human_reply = 1 (YES): Aguardando interação humana, IA pausada

-- Para testar a reativação automática:
-- 1. Configure auto_reactivate_ai = 1 na tabela user_ai_settings
-- 2. Configure reactivation_delay_minutes (0 = reativa imediatamente, >0 = aguarda X minutos)
-- 3. Envie uma nova mensagem do cliente para a conversa
-- 4. A IA verificará se deve reativar baseado no delay configurado

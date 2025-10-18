-- Script de debug para verificar configuração do Baileys vs Meta API

-- 1. Verificar contas WhatsApp e seus tipos de conexão
SELECT 
    '1. CONTAS WHATSAPP' as diagnostico,
    wa.id,
    wa.user_id,
    wa.number,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as tipo,
    wa.status,
    wa.baileys_session_id,
    wa.whatsapp_business_account_id,
    CASE 
        WHEN wa.status = 1 THEN '✅ Ativa'
        ELSE '❌ Inativa'
    END as status_texto
FROM whatsapp_accounts wa
ORDER BY wa.user_id, wa.id;

-- 2. Verificar conversas por tipo de conta
SELECT 
    '2. CONVERSAS POR TIPO DE CONEXÃO' as diagnostico,
    c.id as conversation_id,
    c.user_id,
    c.whatsapp_account_id,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as tipo_conexao,
    c.needs_human_reply,
    CASE 
        WHEN c.needs_human_reply = 0 THEN '✅ IA pode responder'
        ELSE '❌ Aguardando humano'
    END as status_ia,
    c.last_message_at
FROM conversations c
JOIN whatsapp_accounts wa ON wa.id = c.whatsapp_account_id
ORDER BY c.id DESC
LIMIT 20;

-- 3. Últimas mensagens recebidas por tipo de conexão
SELECT 
    '3. ÚLTIMAS MENSAGENS RECEBIDAS' as diagnostico,
    m.id,
    m.conversation_id,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as via,
    m.type,
    CASE 
        WHEN m.type = 0 THEN 'Recebida'
        WHEN m.type = 1 THEN 'Enviada'
        ELSE 'Outro'
    END as tipo_msg,
    m.ai_reply,
    CASE 
        WHEN m.ai_reply = 1 THEN 'IA respondeu'
        ELSE 'Manual/Cliente'
    END as origem,
    LEFT(m.message, 80) as mensagem,
    m.created_at
FROM messages m
JOIN whatsapp_accounts wa ON wa.id = m.whatsapp_account_id
ORDER BY m.id DESC
LIMIT 30;

-- 4. Comparação: Meta API vs Baileys - Contagem de mensagens IA
SELECT 
    '4. ESTATÍSTICAS IA POR TIPO' as diagnostico,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Outro'
    END as tipo_conexao,
    COUNT(DISTINCT m.conversation_id) as total_conversas,
    COUNT(*) as total_mensagens,
    SUM(CASE WHEN m.ai_reply = 1 THEN 1 ELSE 0 END) as mensagens_ia,
    SUM(CASE WHEN m.ai_reply = 0 THEN 1 ELSE 0 END) as mensagens_manuais
FROM messages m
JOIN whatsapp_accounts wa ON wa.id = m.whatsapp_account_id
WHERE m.type = 1 -- apenas enviadas
GROUP BY wa.connection_type;

-- 5. Verificar se há chatbots ativos que podem estar interceptando
SELECT 
    '5. CHATBOTS ATIVOS' as diagnostico,
    cb.id,
    cb.user_id,
    cb.whatsapp_account_id,
    cb.name,
    cb.keywords,
    cb.status,
    CASE 
        WHEN cb.status = 1 THEN '✅ Ativo - pode interceptar mensagens'
        ELSE '❌ Inativo'
    END as status_texto
FROM chatbots cb
WHERE cb.status = 1
ORDER BY cb.whatsapp_account_id;

-- 6. Verificar mensagem de boas-vindas que pode estar interceptando primeira mensagem
SELECT 
    '6. MENSAGENS DE BOAS-VINDAS' as diagnostico,
    wa.id as account_id,
    wa.user_id,
    wa.connection_type,
    CASE 
        WHEN wa.connection_type = 1 THEN 'Meta API'
        WHEN wa.connection_type = 2 THEN 'Baileys'
        ELSE 'Desconhecido'
    END as tipo,
    CASE 
        WHEN wa.welcome_message IS NOT NULL THEN '✅ Configurada - intercepta 1ª mensagem'
        ELSE '❌ Não configurada'
    END as tem_welcome,
    LEFT(wa.welcome_message, 50) as welcome_preview
FROM whatsapp_accounts wa
ORDER BY wa.id;

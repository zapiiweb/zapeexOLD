# 🔄 Instruções para Testar Reativação Automática da IA

## 📋 Como Funciona a Reativação Automática

### Cenário
Quando a IA não sabe responder uma pergunta, ela:
1. Envia a mensagem de fallback configurada
2. Marca a conversa como `needs_human_reply = 1` (YES)
3. Para de responder automaticamente (aguarda interação humana)

### Reativação Automática
Se a funcionalidade de **auto-reativação** está habilitada, a IA pode voltar a responder automaticamente:

#### Opção 1: Reativação Imediata (delay = 0)
- Quando uma nova mensagem do cliente chega
- A IA reativa **imediatamente** antes de processar
- Volta a responder automaticamente

#### Opção 2: Reativação com Delay (delay > 0)
- Quando uma nova mensagem do cliente chega
- A IA verifica quanto tempo passou desde a última mensagem de fallback
- Se passou o tempo configurado (ex: 5 minutos), reativa
- Se não passou, continua aguardando interação humana

#### Opção 3: Reativação Após Resposta Manual
- Quando um atendente humano responde manualmente
- A IA verifica se deve reativar baseado no delay
- Se delay = 0, reativa imediatamente
- Se delay > 0, verifica se passou o tempo

---

## 🛠️ Passo a Passo para Testar

### 1️⃣ Preparar o Banco de Dados

Execute o script SQL no seu banco de dados externo:

```bash
mysql -u seu_usuario -p seu_banco < reset_conversations_for_testing.sql
```

Ou execute manualmente:

```sql
-- Resetar as conversas para teste
UPDATE conversations 
SET needs_human_reply = 0,
    updated_at = NOW()
WHERE (id = 7 AND whatsapp_account_id = 3) 
   OR (id = 19 AND whatsapp_account_id = 1);
```

### 2️⃣ Verificar Configurações da IA

Verifique se as configurações estão corretas na tabela `user_ai_settings`:

```sql
SELECT 
    user_id,
    auto_reactivate_ai,
    reactivation_delay_minutes,
    fallback_response,
    status
FROM user_ai_settings
WHERE user_id IN (
    SELECT user_id FROM conversations 
    WHERE (id = 7 AND whatsapp_account_id = 3) 
       OR (id = 19 AND whatsapp_account_id = 1)
);
```

**Valores esperados:**
- `auto_reactivate_ai = 1` (habilitado)
- `reactivation_delay_minutes = 0` (reativação imediata) **OU** `> 0` (com delay)
- `fallback_response` configurado (ex: "Aguarde, vou transferir para um atendente")
- `status = 1` (IA ativa)

### 3️⃣ Cenário de Teste A: Reativação Imediata (delay = 0)

**Configuração:**
```sql
UPDATE user_ai_settings 
SET auto_reactivate_ai = 1,
    reactivation_delay_minutes = 0
WHERE user_id = [ID_DO_USUARIO];
```

**Teste:**
1. Faça uma pergunta que a IA **não sabe responder** (para acionar o fallback)
2. A IA deve responder com a mensagem de fallback
3. A conversa fica marcada como `needs_human_reply = 1`
4. **ENVIE UMA NOVA MENSAGEM** (qualquer mensagem)
5. ✅ **A IA deve reativar imediatamente e responder**

### 4️⃣ Cenário de Teste B: Reativação com Delay (ex: 2 minutos)

**Configuração:**
```sql
UPDATE user_ai_settings 
SET auto_reactivate_ai = 1,
    reactivation_delay_minutes = 2
WHERE user_id = [ID_DO_USUARIO];
```

**Teste:**
1. Faça uma pergunta que a IA **não sabe responder**
2. A IA envia fallback e marca `needs_human_reply = 1`
3. **AGUARDE MENOS DE 2 MINUTOS** e envie nova mensagem
   - ❌ A IA **NÃO deve** responder (ainda aguardando)
4. **AGUARDE MAIS DE 2 MINUTOS** desde o fallback e envie nova mensagem
   - ✅ A IA **deve reativar** e responder

### 5️⃣ Cenário de Teste C: Reativação Após Resposta Manual

**Teste:**
1. Faça uma pergunta que a IA não sabe
2. IA envia fallback → `needs_human_reply = 1`
3. **ATENDENTE HUMANO RESPONDE** manualmente pela interface
4. Baseado no delay configurado:
   - Se delay = 0: ✅ IA reativa imediatamente
   - Se delay > 0: ✅ IA reativa se já passou o tempo

---

## 🔍 Logs de Debug

O sistema gera logs detalhados. Verifique os logs em:

```bash
tail -f storage/logs/laravel.log | grep "sendAutoReply"
```

**Logs esperados:**

```
[INFO] sendAutoReply - Check 2: Status/Contact/NeedsHuman
[INFO] sendAutoReply - IA reativada automaticamente (sem delay)
// OU
[INFO] sendAutoReply - IA reativada automaticamente após delay
```

---

## 📊 Query para Verificar Estado Atual

```sql
-- Ver conversas e última mensagem de fallback
SELECT 
    c.id as conversation_id,
    c.whatsapp_account_id,
    c.needs_human_reply,
    c.updated_at as conversa_updated,
    (SELECT created_at FROM messages 
     WHERE conversation_id = c.id 
       AND ai_reply = 1 
       AND type = 1 
     ORDER BY id DESC LIMIT 1) as last_fallback_at,
    TIMESTAMPDIFF(MINUTE, 
        (SELECT created_at FROM messages 
         WHERE conversation_id = c.id 
           AND ai_reply = 1 
           AND type = 1 
         ORDER BY id DESC LIMIT 1),
        NOW()
    ) as minutes_since_fallback
FROM conversations c
WHERE (c.id = 7 AND c.whatsapp_account_id = 3) 
   OR (c.id = 19 AND c.whatsapp_account_id = 1);
```

---

## ⚠️ Problemas Comuns

### Problema: IA não reativa mesmo com delay = 0
**Causas possíveis:**
1. `auto_reactivate_ai = 0` (desabilitado)
2. IA está desabilitada (`user_ai_settings.status = 0`)
3. Usuário tem `ai_assistance = 0`

**Solução:**
```sql
-- Verificar configurações
SELECT * FROM user_ai_settings WHERE user_id = [ID];
SELECT ai_assistance FROM users WHERE id = [ID];
```

### Problema: IA não detecta que passou o delay
**Causa:** Não há mensagem de fallback registrada com `ai_reply = 1`

**Solução:**
```sql
-- Verificar mensagens de fallback
SELECT id, conversation_id, ai_reply, type, created_at 
FROM messages 
WHERE conversation_id IN (7, 19)
  AND ai_reply = 1 
  AND type = 1
ORDER BY created_at DESC;
```

---

## ✅ Checklist de Teste

- [ ] Script SQL executado com sucesso
- [ ] `needs_human_reply = 0` nas conversas teste
- [ ] `auto_reactivate_ai = 1` configurado
- [ ] Delay configurado (0 ou > 0)
- [ ] Fallback message configurada
- [ ] IA ativa (`status = 1`)
- [ ] Teste com delay = 0 funcionando
- [ ] Teste com delay > 0 funcionando
- [ ] Teste após resposta manual funcionando
- [ ] Logs sendo gerados corretamente

---

## 📞 Próximos Passos

Se após seguir todos os passos a reativação ainda não funcionar:
1. Compartilhe os logs do Laravel
2. Execute a query de verificação de estado
3. Verifique se há erros no console do navegador

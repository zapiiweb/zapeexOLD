# 🤖 Campo `ai_assistance` - Controle Principal da IA

## 📌 O Que É?

O campo `ai_assistance` na tabela `users` é o **interruptor mestre** que controla se a IA pode responder para aquele usuário.

### Localização
- **Tabela**: `users`
- **Campo**: `ai_assistance`
- **Valores**: 
  - `0` = IA desabilitada para este usuário ❌
  - `1` = IA habilitada para este usuário ✅

---

## 🔍 Por Que a IA Não Respondia?

Mesmo com **todas** as outras configurações corretas:
- ✅ `needs_human_reply = 0` (conversa liberada)
- ✅ `user_ai_settings.status = 1` (configurações de IA ativas)
- ✅ Provider ativo (OpenAI/Gemini configurado)
- ✅ Contato existe
- ✅ Conta WhatsApp conectada

Se `users.ai_assistance = 0`, a IA **NÃO responde**.

### Fluxo de Verificação no Código

```php
// WhatsAppLib.php - linha 661
if($user->ai_assistance == 0) return; // <-- Para aqui!
```

A IA verifica `ai_assistance` **ANTES** de chamar o modelo de IA (OpenAI/Gemini).

---

## 🛠️ Como Habilitar?

### Opção 1: Via SQL (Banco de Dados Externo)

```sql
-- Para um usuário específico (ex: ID 1)
UPDATE users SET ai_assistance = 1 WHERE id = 1;

-- Para todos os usuários
UPDATE users SET ai_assistance = 1 WHERE user_type = 1;
```

### Opção 2: Via Interface Admin (se disponível)

1. Faça login como **admin**
2. Vá em **Gerenciar Usuários**
3. Edite o usuário
4. Habilite a opção **"AI Assistance"** ou **"Assistência IA"**
5. Salve

---

## 🎯 Diferença Entre os Campos

| Campo | Tabela | Propósito | Quando Usar |
|-------|--------|-----------|-------------|
| `ai_assistance` | `users` | **Interruptor mestre** - Habilita/desabilita IA para o usuário | Controle global do usuário |
| `status` | `user_ai_settings` | Habilita/desabilita configurações específicas de IA | Configurações de prompt, fallback, etc |
| `needs_human_reply` | `conversations` | Pausa a IA para esta conversa específica (aguarda humano) | Por conversa individual |

### Hierarquia de Controle

```
1. users.ai_assistance = 0?  ❌ PARA - IA desabilitada globalmente
   └─> SIM → IA responde ✅
       └─> 2. user_ai_settings.status = 0?  ❌ PARA - Sem configurações
           └─> SIM → Continua ✅
               └─> 3. conversation.needs_human_reply = 1?  ❌ PARA - Aguarda humano
                   └─> NÃO → IA RESPONDE! 🎉
```

---

## 📊 Diagnóstico Rápido

Execute no seu banco de dados:

```sql
SELECT 
    u.id,
    u.username,
    u.ai_assistance as 'AI Master Switch',
    uas.status as 'AI Settings Status',
    COUNT(c.id) as 'Total Conversations',
    SUM(CASE WHEN c.needs_human_reply = 0 THEN 1 ELSE 0 END) as 'Conversations Ready for AI'
FROM users u
LEFT JOIN user_ai_settings uas ON uas.user_id = u.id
LEFT JOIN conversations c ON c.user_id = u.id
WHERE u.user_type = 1
GROUP BY u.id, u.username, u.ai_assistance, uas.status;
```

---

## ✅ Checklist Pós-Correção

Depois de executar `UPDATE users SET ai_assistance = 1`:

- [ ] Verificar que `ai_assistance = 1` no banco
- [ ] Verificar que `user_ai_settings.status = 1`
- [ ] Verificar que existe provider ativo (OpenAI/Gemini)
- [ ] Verificar que `needs_human_reply = 0` nas conversas
- [ ] **Enviar mensagem teste** do WhatsApp
- [ ] **Verificar logs** em `storage/logs/laravel.log`

### Logs Esperados

```
[INFO] === sendAutoReply INICIADO === {"user_id":1,"conversation_id":X}
[INFO] sendAutoReply - Check 1: userAiSetting {"exists":true}
[INFO] sendAutoReply - Check 2: Status/Contact/NeedsHuman {"aiSetting_status":1,"has_contact":true,"needs_human_reply":0}
[INFO] sendAutoReply - Check 3: activeProvider {"exists":true,"provider":"openai"}
[INFO] sendAutoReply - Check 4: user.ai_assistance {"ai_assistance":1} ✅
[INFO] sendAutoReply - Vai chamar IA {"class":"App\\Lib\\OpenAi"}
[INFO] sendAutoReply - Resposta da IA {"aiResponse":"..."}
```

---

## 🔧 Solução Aplicada

Para seu caso específico (usuário ID 1):

```sql
UPDATE users SET ai_assistance = 1 WHERE id = 1;
```

Após executar este comando:
1. ✅ `ai_assistance` passa de 0 → 1
2. ✅ A IA vai passar do Check 4
3. ✅ A IA vai chamar o modelo (OpenAI/Gemini)
4. ✅ A IA vai responder automaticamente!

---

## 🚨 Se Ainda Não Funcionar

Se após habilitar `ai_assistance = 1` a IA ainda não responder, verifique:

1. **Provider de IA ativo?**
   ```sql
   SELECT * FROM ai_assistants WHERE status = 1;
   ```
   - Deve ter pelo menos 1 registro com `status = 1`
   - Deve ter `api_key` configurada

2. **Configurações de IA do usuário?**
   ```sql
   SELECT * FROM user_ai_settings WHERE user_id = 1;
   ```
   - Deve ter `status = 1`
   - Deve ter `system_prompt` configurado

3. **Logs do Laravel**
   ```bash
   tail -f storage/logs/laravel.log | grep "sendAutoReply"
   ```
   - Veja em qual check a IA está parando

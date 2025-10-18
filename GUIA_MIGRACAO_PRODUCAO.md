# 📋 Guia de Migração para Produção - OvoWpp

## ⚠️ IMPORTANTE: Leia antes de executar!

Este guia explica como aplicar todas as alterações do banco de dados no servidor de produção de forma segura.

---

## 🎯 O que este script faz?

Este script aplica **6 migrations** que incluem:

### ✅ **Novas Tabelas Criadas:**

1. **`short_links`** - Gerenciamento de links curtos
2. **`floaters`** - Elementos flutuantes do sistema
3. **`permissions`** - Permissões do sistema (Spatie Permission Package)
4. **`roles`** - Funções/Papéis de usuários
5. **`model_has_permissions`** - Relação entre modelos e permissões
6. **`model_has_roles`** - Relação entre modelos e roles
7. **`role_has_permissions`** - Relação entre roles e permissões

### ✅ **Novas Colunas Adicionadas:**

#### 📨 **Tabela: `messages`**
- **`job_id`** (VARCHAR 255, NULL)
  - **Propósito:** Rastrear jobs assíncronos de mensagens enviadas via Baileys
  - **Uso:** Mensagens ficam com status SCHEDULED até que o webhook confirme o envio
  - **Índice:** Sim (para busca rápida)

#### 🤖 **Tabela: `ai_user_settings`**
- **`auto_reactivate_ai`** (BOOLEAN, padrão: FALSE)
  - **Propósito:** Habilita/desabilita reativação automática da IA após fallback
  - **Uso:** Quando habilitado, a IA volta a responder automaticamente após uma resposta manual
  - **Padrão:** Desabilitado (FALSE)

- **`reactivation_delay_minutes`** (INT, NULL)
  - **Propósito:** Define tempo de espera para reativar a IA
  - **Uso:** 
    - NULL = reativação imediata após resposta manual
    - Número = minutos de espera antes de reativar
  - **Padrão:** NULL (imediato)

#### 📱 **Tabela: `whatsapp_accounts`**
- **`connection_type`** (TINYINT, padrão: 1)
  - **Propósito:** Permite escolher o tipo de conexão WhatsApp
  - **Valores:**
    - `1` = Meta WhatsApp Business API (padrão)
    - `2` = Baileys Direct Connection
  - **Uso:** Usuário escolhe via interface qual método usar
  - **Padrão:** 1 (Meta API)

---

## 🔒 Pré-requisitos

Antes de executar o script:

1. ✅ Acesso ao banco de dados MySQL/MariaDB de produção
2. ✅ Permissões de CREATE, ALTER TABLE e CREATE INDEX
3. ✅ **BACKUP COMPLETO** do banco de dados

---

## 📦 Passo a Passo para Aplicar a Migração

### 1️⃣ **FAZER BACKUP DO BANCO DE DADOS**

```bash
# Exemplo de backup via mysqldump
mysqldump -u SEU_USUARIO -p SEU_BANCO_DE_DADOS > backup_antes_migracao_$(date +%Y%m%d_%H%M%S).sql
```

### 2️⃣ **Transferir o arquivo para o servidor**

Envie o arquivo `migration_script_production.sql` para o servidor de produção.

### 3️⃣ **Executar o script SQL**

**Opção A - Via MySQL CLI:**
```bash
mysql -u SEU_USUARIO -p SEU_BANCO_DE_DADOS < migration_script_production.sql
```

**Opção B - Via phpMyAdmin:**
1. Acesse phpMyAdmin
2. Selecione o banco de dados
3. Vá em "SQL"
4. Cole todo o conteúdo do arquivo `migration_script_production.sql`
5. Clique em "Executar"

**Opção C - Via cliente MySQL:**
```sql
mysql -u SEU_USUARIO -p
USE SEU_BANCO_DE_DADOS;
SOURCE /caminho/para/migration_script_production.sql;
```

### 4️⃣ **Verificar a execução**

O script inclui verificações automáticas que mostrarão:
- ✓ Tabelas criadas com sucesso
- ✓ Colunas adicionadas com sucesso

Exemplo de saída esperada:
```
+-----------------------------+-----------+
| tabela                      | status    |
+-----------------------------+-----------+
| short_links                 | ✓ Criada  |
| floaters                    | ✓ Criada  |
| permissions                 | ✓ Criada  |
| ...                         | ...       |
+-----------------------------+-----------+

+--------------------------------------------+---------------+
| campo                                      | status        |
+--------------------------------------------+---------------+
| messages.job_id                            | ✓ Adicionado  |
| ai_user_settings.auto_reactivate_ai        | ✓ Adicionado  |
| ai_user_settings.reactivation_delay_minutes| ✓ Adicionado  |
| whatsapp_accounts.connection_type          | ✓ Adicionado  |
+--------------------------------------------+---------------+
```

---

## 🧪 Testes Pós-Migração

Após aplicar o script, teste as funcionalidades:

### 1. **Teste de Conexão WhatsApp**
- ✅ Verifique se as contas WhatsApp existentes mantiveram `connection_type = 1`
- ✅ Teste alternar entre Meta API e Baileys via interface
- ✅ Envie mensagens usando ambos os tipos de conexão

### 2. **Teste de IA Auto-Reativação**
- ✅ Configure uma conta com `auto_reactivate_ai = TRUE`
- ✅ Teste o comportamento de reativação após fallback
- ✅ Teste com diferentes valores de `reactivation_delay_minutes`

### 3. **Teste de Rastreamento de Jobs**
- ✅ Envie mensagem via Baileys
- ✅ Verifique se `job_id` foi preenchido
- ✅ Confirme atualização de status via webhook

### 4. **Teste de Permissões**
- ✅ Crie roles e permissões
- ✅ Atribua permissões a usuários
- ✅ Verifique controle de acesso

---

## 🔧 Resolução de Problemas

### ❌ Erro: "Table already exists"
**Solução:** O script usa `IF NOT EXISTS`, então isso não deve acontecer. Se ocorrer, verifique se o script foi executado anteriormente.

### ❌ Erro: "Column already exists"
**Solução:** O script usa `IF NOT EXISTS`, mas alguns MySQL/MariaDB não suportam isso no ALTER TABLE. Neste caso:
1. Verifique se a coluna já existe
2. Se sim, comente ou remova a linha correspondente no script
3. Execute novamente

### ❌ Erro: Foreign Key constraint
**Solução:** 
1. Verifique se as tabelas referenciadas existem
2. Desabilite temporariamente as foreign key checks:
```sql
SET FOREIGN_KEY_CHECKS=0;
-- Execute o script
SET FOREIGN_KEY_CHECKS=1;
```

---

## 📊 Estrutura das Novas Tabelas

### **Tabela: permissions**
```sql
- id (BIGINT, AUTO_INCREMENT)
- name (VARCHAR 255) - Nome da permissão
- guard_name (VARCHAR 255) - Guard (web, api)
- group_name (VARCHAR 255) - Grupo da permissão
- created_at, updated_at
```

### **Tabela: roles**
```sql
- id (BIGINT, AUTO_INCREMENT)
- name (VARCHAR 255) - Nome da role
- guard_name (VARCHAR 255) - Guard (web, api)
- status (TINYINT, DEFAULT 1) - 1=Ativa, 0=Inativa
- created_at, updated_at
- UNIQUE: (name, guard_name)
```

### **Tabela: model_has_permissions**
```sql
- permission_id (BIGINT)
- model_type (VARCHAR 255)
- model_id (BIGINT)
- PRIMARY KEY: (permission_id, model_id, model_type)
- FOREIGN KEY: permission_id -> permissions.id
```

### **Tabela: model_has_roles**
```sql
- role_id (BIGINT)
- model_type (VARCHAR 255)
- model_id (BIGINT)
- PRIMARY KEY: (role_id, model_id, model_type)
- FOREIGN KEY: role_id -> roles.id
```

### **Tabela: role_has_permissions**
```sql
- permission_id (BIGINT)
- role_id (BIGINT)
- PRIMARY KEY: (permission_id, role_id)
- FOREIGN KEYs: permission_id, role_id
```

---

## 📝 Notas Importantes

### ⚡ **Valores Padrão Configurados:**

1. **`roles.status`** = `1` (Ativa por padrão)
2. **`ai_user_settings.auto_reactivate_ai`** = `FALSE` (Desabilitado)
3. **`ai_user_settings.reactivation_delay_minutes`** = `NULL` (Imediato)
4. **`whatsapp_accounts.connection_type`** = `1` (Meta API)

### 🔄 **Contas WhatsApp Existentes:**

Todas as contas existentes receberão automaticamente:
- `connection_type = 1` (Meta API)
- Isso mantém o comportamento atual sem interrupção

### 🤖 **Configurações de IA Existentes:**

Todas as configurações de IA existentes receberão:
- `auto_reactivate_ai = FALSE` (desabilitado)
- `reactivation_delay_minutes = NULL`
- Os usuários podem habilitar via interface quando quiserem

---

## ✅ Checklist Final

Antes de considerar a migração completa:

- [ ] Backup do banco de dados realizado
- [ ] Script executado sem erros
- [ ] Verificações automáticas mostraram sucesso
- [ ] Testes de conexão WhatsApp OK
- [ ] Testes de IA auto-reativação OK
- [ ] Testes de rastreamento de jobs OK
- [ ] Testes de permissões OK
- [ ] Sistema Laravel funcionando normalmente
- [ ] Nenhum erro nos logs do Laravel
- [ ] Nenhum erro nos logs do Baileys

---

## 📞 Suporte

Se encontrar problemas durante a migração:

1. **Restaure o backup imediatamente** se houver falha crítica
2. Verifique os logs do MySQL/MariaDB para detalhes do erro
3. Revise a seção "Resolução de Problemas" acima
4. Teste em ambiente de staging antes de aplicar em produção

---

## 📅 Histórico de Alterações

| Data       | Migration                                      | Descrição                           |
|------------|------------------------------------------------|-------------------------------------|
| 2025-05-19 | create_short_links_table                       | Criação tabela short_links          |
| 2025-05-21 | create_floaters_table                          | Criação tabela floaters             |
| 2025-08-18 | create_permission_tables                       | Sistema de permissões Spatie        |
| 2025-10-17 | add_job_id_to_messages_table                   | Rastreamento jobs Baileys           |
| 2025-10-18 | add_auto_reactivation_to_ai_user_settings      | Auto-reativação IA após fallback    |
| 2025-10-18 | add_connection_type_to_whatsapp_accounts_table | Seleção tipo conexão WhatsApp       |

---

**✨ Migração criada em:** 18 de Outubro de 2025  
**🔧 Versão do Script:** 1.0  
**📦 Total de Migrations:** 6  
**🗄️ Novas Tabelas:** 7  
**📊 Novas Colunas:** 4  

---

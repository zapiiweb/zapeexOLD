# 📦 Scripts de Migração do Banco de Dados - OvoWpp

## 📁 Arquivos Criados

Este pacote contém 4 arquivos para gerenciar a migração do banco de dados:

### 1. 🚀 `migration_script_production.sql`
**O QUE FAZ:** Script principal que aplica TODAS as alterações no banco de dados de produção.

**QUANDO USAR:** Quando você quiser aplicar as migrations no servidor de produção.

**CONTÉM:**
- ✅ Criação de 7 novas tabelas
- ✅ Adição de 4 novas colunas em tabelas existentes
- ✅ Criação de índices
- ✅ Configuração de foreign keys
- ✅ Verificações automáticas
- ✅ Valores padrão configurados

---

### 2. 📖 `GUIA_MIGRACAO_PRODUCAO.md`
**O QUE FAZ:** Guia completo em português com instruções detalhadas.

**QUANDO USAR:** Leia ANTES de executar o script de migração.

**CONTÉM:**
- 📋 Passo a passo completo
- ⚠️ Avisos importantes
- 🔧 Resolução de problemas
- 🧪 Testes pós-migração
- 📊 Estrutura das tabelas
- ✅ Checklist final

---

### 3. ⏪ `rollback_script_production.sql`
**O QUE FAZ:** Reverte TODAS as alterações feitas pela migração.

**QUANDO USAR:** APENAS se algo der errado e você precisar desfazer as mudanças.

**⚠️ ATENÇÃO:** Ao executar este script, você perderá:
- Todas as permissões criadas
- Todas as roles criadas
- Configurações de conexão WhatsApp
- Configurações de auto-reativação da IA
- E todos os dados relacionados

---

### 4. 📄 `LEIAME_MIGRACAO.md` (este arquivo)
**O QUE FAZ:** Índice rápido de todos os arquivos e instruções básicas.

**QUANDO USAR:** Para ter uma visão geral rápida do pacote.

---

## 🎯 Guia Rápido de Uso

### ✅ Para Aplicar a Migração:

```bash
# 1. Faça backup do banco
mysqldump -u usuario -p banco_de_dados > backup.sql

# 2. Execute a migração
mysql -u usuario -p banco_de_dados < migration_script_production.sql

# 3. Verifique os resultados
# O próprio script mostrará se tudo foi aplicado com sucesso
```

### ⏪ Para Reverter a Migração (Rollback):

```bash
# 1. Faça backup ANTES de reverter
mysqldump -u usuario -p banco_de_dados > backup_antes_rollback.sql

# 2. Execute o rollback
mysql -u usuario -p banco_de_dados < rollback_script_production.sql

# 3. Verifique os resultados
# O script mostrará quais tabelas/colunas foram removidas
```

---

## 📊 Resumo das Alterações

### 🆕 Novas Tabelas (7):
1. `short_links` - Links curtos
2. `floaters` - Elementos flutuantes
3. `permissions` - Permissões do sistema
4. `roles` - Funções/papéis
5. `model_has_permissions` - Relação modelo-permissões
6. `model_has_roles` - Relação modelo-roles
7. `role_has_permissions` - Relação role-permissões

### ➕ Novas Colunas (4):
1. `messages.job_id` - Rastreamento de jobs Baileys
2. `ai_user_settings.auto_reactivate_ai` - Habilita auto-reativação IA
3. `ai_user_settings.reactivation_delay_minutes` - Tempo para reativação
4. `whatsapp_accounts.connection_type` - Tipo de conexão (Meta API ou Baileys)

---

## ⚠️ Checklist ANTES de Executar

- [ ] Fiz backup completo do banco de dados
- [ ] Li o arquivo `GUIA_MIGRACAO_PRODUCAO.md`
- [ ] Tenho acesso ao servidor de produção
- [ ] Tenho permissões necessárias no banco
- [ ] Testei em ambiente de staging (recomendado)

---

## 🆘 Em Caso de Problemas

1. **NÃO ENTRE EM PÂNICO** 🧘
2. Restaure o backup imediatamente
3. Consulte a seção "Resolução de Problemas" no guia
4. Revise os logs do MySQL/MariaDB
5. Teste em ambiente de staging antes de tentar novamente

---

## 📞 Informações Técnicas

**Migrations Incluídas:**
- `2025_05_19_191546` - create_short_links_table
- `2025_05_21_202423` - create_floaters_table
- `2025_08_18_191913` - create_permission_tables
- `2025_10_17_162730` - add_job_id_to_messages_table
- `2025_10_18_114204` - add_auto_reactivation_to_ai_user_settings
- `2025_10_18_152000` - add_connection_type_to_whatsapp_accounts_table

**Compatibilidade:**
- MySQL 5.7+
- MariaDB 10.2+
- Laravel 11.9

**Charset:** UTF8MB4
**Collation:** utf8mb4_unicode_ci
**Engine:** InnoDB

---

## ✨ Próximos Passos Após a Migração

1. ✅ Verificar que não há erros no Laravel
2. ✅ Testar funcionalidades do WhatsApp
3. ✅ Testar sistema de permissões
4. ✅ Configurar auto-reativação da IA (se necessário)
5. ✅ Monitorar logs por 24-48h

---

**Data de Criação:** 18 de Outubro de 2025  
**Versão:** 1.0  
**Projeto:** OvoWpp - WhatsApp CRM Platform

---

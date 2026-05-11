# Integração: Algolia Search + GPT Custom Action (Magento 2)

Este projeto disponibiliza um endpoint PHP para um **Custom GPT** consultar produtos no índice do **Algolia** e retornar resultados formatados.

## Visão geral do projeto

- **Plataforma**: Magento 2
- **Busca**: Algolia Search (plugin oficial)
- **IA**: Custom GPT (OpenAI) com Actions
- **Servidor**: PHP 7.4+ (recomendado 8.x)
- **Pasta da integração**: `/agent`

Arquivos principais:

- `agent/config.php`: credenciais e constantes.
- `agent/algolia.php`: integração REST via cURL com Algolia.
- `agent/search-products.php`: endpoint da Action.
- `agent/schema.yaml`: OpenAPI 3.1 para cadastrar no GPT.

---

## 1) Configurar credenciais no `config.php`

Edite os placeholders em `agent/config.php`:

- `ALGOLIA_APP_ID`: App ID do Algolia.
- `ALGOLIA_SEARCH_KEY`: **Search-Only API Key** (não usar Admin Key).
- `ALGOLIA_INDEX_NAME`: nome do índice (ex.: `magento2_default_products`).
- `AGENT_SECRET_TOKEN`: token secreto forte para proteger o endpoint.

> Recomendação: use um token com no mínimo 32 caracteres.

---

## 2) Publicar no servidor Magento

1. Faça upload da pasta `/agent` para a raiz pública do site.
2. Garanta que o PHP tenha as extensões `curl` e `json` habilitadas.
3. Use HTTPS obrigatório.

---

## 3) Testar o endpoint

### Teste sem token (deve retornar 401)

Abra no navegador:

```text
https://seusite.com.br/agent/search-products.php?query=tenis
```

Resposta esperada:

```json
{"error":"Nao autorizado"}
```

### Teste com token (curl)

```bash
curl -X POST https://seusite.com.br/agent/search-products.php \
  -H "Content-Type: application/json" \
  -H "X-Agent-Token: SEU_TOKEN_AQUI" \
  -d '{"query":"tenis"}'
```

---

## 4) Criar a Action no Custom GPT

No painel da OpenAI:

1. Acesse **Meus GPTs**.
2. Abra seu GPT e clique em **Editar**.
3. Vá em **Actions** > **Adicionar Action**.
4. Cole o conteúdo de `agent/schema.yaml`.
5. Salve.

---

## 5) Configurar autenticação da Action (API Key)

Ao configurar a Action:

- **Tipo**: API Key
- **Header name**: `X-Agent-Token`
- **Valor**: o mesmo de `AGENT_SECRET_TOKEN` no `config.php`

Isso fará o GPT enviar automaticamente o header de autenticação a cada chamada.

---

## 6) Informações importantes do projeto

- O endpoint aceita `query` por **JSON no POST** e também por `GET` (facilita testes).
- A resposta retorna:
  - `total`
  - `query`
  - `products[]` com `name`, `sku`, `price`, `url`, `description`
- O endpoint limita a busca a até **5 produtos** por chamada.

Exemplo de resposta:

```json
{
  "total": 2,
  "query": "tenis nike",
  "products": [
    {
      "name": "Tênis Nike Air Max",
      "sku": "TEN-NIKE-001",
      "price": "R$ 599,90",
      "url": "https://loja.com.br/tenis-nike-air-max",
      "description": "Tênis esportivo com amortecimento..."
    }
  ]
}
```

---

## 7) Segurança (obrigatório)

- **Nunca** use a Admin API Key do Algolia no endpoint.
- Use apenas Search-Only Key.
- Não logue queries/dados sensíveis em arquivo.
- Mantenha `AGENT_SECRET_TOKEN` privado.
- Use HTTPS em produção.

---

## 8) Dicas Magento + Algolia

- Índice padrão comum: `magento2_default_products`.
- Em lojas com múltiplas store views, podem existir índices separados por loja/idioma.
- O campo de preço pode variar de estrutura no Algolia; a integração já trata os formatos mais comuns.

# Prompt para Geração de API REST em Laravel (Arquitetura + PSRs + Clean Code)

## Objetivo
Gerar uma API REST em Laravel seguindo:

- Boas práticas do ecossistema Laravel
- Padrões PSR (PSR-1, PSR-4, PSR-12)
- Princípios de Clean Code e SOLID
- Arquitetura escalável e sustentável
- Código legível, testável e desacoplado

---

## Princípios Fundamentais

### Clean Code
- Métodos pequenos (máx ~20 linhas idealmente)
- Nomes descritivos e sem abreviações obscuras
- Uma responsabilidade por classe (SRP)
- Evitar comentários desnecessários (código deve ser autoexplicativo)
- Evitar duplicação (DRY)

### SOLID
- S: Single Responsibility → Services fazem regra de negócio
- O: Open/Closed → Evitar alterar código existente desnecessariamente
- L: Liskov → Substituições seguras
- I: Interface Segregation → Interfaces específicas
- D: Dependency Inversion → Injeção de dependência

---

## Padrões PSR Obrigatórios

### PSR-1
- Classes em PascalCase
- Métodos em camelCase
- Constantes em UPPER_CASE

### PSR-4
- Namespaces alinhados com estrutura de pastas

### PSR-12
- Indentação com 4 espaços
- Uma classe por arquivo
- Chaves sempre em nova linha

---

## Estrutura de Diretórios

```
app/
 ├── Http/
 │    ├── Controllers/
 │    ├── Requests/
 │    └── Resources/
 ├── Services/
 ├── DTOs/
 ├── Exceptions/
 ├── Actions/ (opcional)
 └── Models/
```

---

## Regras Arquiteturais

### Controllers
- NÃO conter lógica de negócio
- Apenas orquestrar fluxo

### Form Requests
- Toda validação deve ser centralizada

### DTOs
- Nunca usar arrays soltos entre camadas

### Services
- Toda regra de negócio deve estar aqui

### API Resources
- Nunca retornar Models diretamente

---

## Code Smells que DEVEM ser evitados

- God Controller
- Arrays genéricos
- Lógica duplicada
- Queries espalhadas
- Métodos com múltiplas responsabilidades

---

## Tratamento de Exceções

- Usar exceptions customizadas
- Centralizar no Handler

---

## Banco de Dados

- Usar migrations
- Usar transactions em operações críticas

---

## Performance

- Evitar N+1 queries
- Utilizar eager loading

---

## Padrão de Resposta

### Sucesso
```
{
  "data": {},
  "meta": {}
}
```

### Erro
```
{
  "error": true,
  "message": "Mensagem"
}
```

---

## Versionamento

- Sempre utilizar versionamento (v1, v2, etc)

---

## Autenticação

- Usar Laravel Sanctum
- Usar Policies

---

## Testes

- Feature Tests
- Unit Tests

---

## Diretrizes para IA

1. Nunca colocar regra de negócio em controllers
2. Sempre usar DTOs
3. Criar Request, Service e Resource
4. Seguir PSR-12
5. Evitar code smells
6. Garantir código limpo

---

## Resultado Esperado

Uma API:
- Escalável
- Padronizada
- Limpa
- Pronta para produção

# 🚀 Prompt Base — Projeto React com TypeScript e Tailwind

## 🎯 Objetivo

Desenvolver uma aplicação React moderna, escalável e de fácil manutenção utilizando:

- React (com hooks e componentes funcionais)
- TypeScript (tipagem forte e segura)
- Tailwind CSS (estilização utilitária)
- Princípios SOLID
- Clean Code e boas práticas de arquitetura

---

## 🧱 Estrutura do Projeto

Organizar o projeto de forma modular e escalável:

```
src/
  ├── app/                # Configurações globais (providers, rotas)
  ├── components/         # Componentes reutilizáveis (UI)
  ├── features/           # Módulos de negócio (domínio)
  ├── hooks/              # Hooks customizados
  ├── services/           # Comunicação com APIs
  ├── stores/             # Gerenciamento de estado (se necessário)
  ├── types/              # Tipagens globais
  ├── utils/              # Funções utilitárias
  ├── styles/             # Estilos globais
  └── main.tsx            # Entry point
```

---

## ⚛️ Boas Práticas com React

- Utilizar componentes funcionais e hooks
- Evitar lógica complexa diretamente no JSX
- Separar responsabilidades (UI vs lógica)
- Manter componentes pequenos e coesos
- Evitar prop drilling excessivo

---

## 🧠 Boas Práticas com TypeScript

- Evitar `any`
- Tipar props, funções e APIs
- Criar tipos reutilizáveis
- Utilizar enums quando fizer sentido
- Preferir `unknown` ao invés de `any`

---

## 🎨 Boas Práticas com Tailwind

- Priorizar classes utilitárias
- Evitar duplicação de estilos
- Usar clsx/classnames
- Manter consistência visual

---

## 🧩 Princípios SOLID

- SRP: responsabilidade única
- OCP: aberto para extensão
- LSP: substituível
- ISP: interfaces específicas
- DIP: depender de abstrações

---

## 🔌 Comunicação com API

- Centralizar em services/
- Tipar requests/responses
- Tratar erros globalmente
- Evitar chamadas diretas em componentes

---

## 🧪 Testes

- Jest
- React Testing Library

---

## ♻️ Reutilização

- Componentes reutilizáveis
- Hooks customizados
- Evitar duplicação

---

## ⚙️ Convenções

- PascalCase para componentes
- camelCase para funções
- Um componente por arquivo

---

## 🚫 Anti-patterns

- Uso de any
- Componentes grandes
- Lógica no JSX
- Código duplicado

---

## 📦 Bibliotecas sugeridas

- React Query
- Zustand ou Redux Toolkit
- clsx
- Zod ou Yup

---

## 🧭 Diretrizes finais

- Código limpo e legível
- Evitar over-engineering
- Pensar em escalabilidade

---

## 🧠 Prompt para IA

> Gere um projeto React com TypeScript e Tailwind seguindo rigorosamente essas boas práticas.

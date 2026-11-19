# Guia do Blog MXD20 (maxd20.com.br/blog/)

Este branch (`blog-posts`) guarda os posts do blog. Cada arquivo em `posts/` vira um post no site quando chega o horário de `publicar_em`. O servidor puxa este branch a cada 10 minutos.

## Formato do arquivo
Nome: `posts/AAAA-MM-DD-HHMM-slug.json`. Conteúdo (JSON UTF-8):

```json
{
  "slug": "palavras-com-hifen-sem-acento",
  "titulo": "Título do post (até ~65 caracteres)",
  "titulo_seo": "opcional, se quiser um título diferente na aba do Google",
  "descricao": "Resumo de 140 a 160 caracteres, com a palavra-chave",
  "categoria": "Barba | Cabelo | Sobrancelha | Rotina e cuidados | Mitos e verdades | Estilo",
  "palavra_chave": "termo principal que a pessoa digita no Google",
  "publicar_em": "AAAA-MM-DDTHH:MM:00-03:00",
  "conteudo_html": "<p>...</p><h2>...</h2>...",
  "faq": [["Pergunta?", "Resposta curta."], ["Pergunta?", "Resposta curta."]]
}
```

- `slug` único: nunca repita um slug que já existe em `posts/`.
- Horários: 3 posts por dia, às 08:00, 13:00 e 19:00 (horário de Brasília, `-03:00`).
- `conteudo_html`: só `<p> <h2> <h3> <ul> <ol> <li> <strong> <em> <a> <blockquote> <table>`. Nada de `<h1>` (o título já é o h1), imagens, scripts ou estilos. O site coloca sozinho o bloco do produto no meio do texto e no fim.

## Como escrever
- Português do Brasil, tom de conversa, direto, para homens que querem barba mais cheia, cabelo mais forte ou sobrancelha preenchida.
- 600 a 1.000 palavras. Começa respondendo a dúvida do título já no 1º parágrafo. 3 a 6 subtítulos `<h2>`, listas quando ajudar, 2 a 4 perguntas no `faq`.
- Cada post resolve UMA dúvida real de busca (ex.: "por que a barba cresce falhada", "quanto tempo a barba demora para crescer", "óleo de rícino na barba funciona", "como disfarçar falha na sobrancelha", "queda de cabelo no banho é normal", "como pentear barba", "barba ruiva/grisalha", "minoxidil na barba: o que saber").
- Varie: guias passo a passo, listas, mitos e verdades, comparações, erros comuns, cuidados por tipo de fio, estilo e corte de barba, rotina, alimentação e sono, perguntas de iniciante.
- Inclua 1 ou 2 links internos naturais: para o produto (`/produto/tonico-mxd20-barbudom-30ml/`) e, quando fizer sentido, para outro post do blog que já exista (`/blog/<slug>/`).
- Mencione o Tônico MXD20 com naturalidade, no máximo 2 vezes no texto, como parte da rotina. O post precisa ser útil mesmo para quem não comprar.

## Regras obrigatórias (não quebrar)
- Nada de promessa de cura ou de resultado garantido. Não diga que o tônico "trata calvície/alopecia", "faz nascer cabelo em X dias" ou "cura". Use "ajuda", "contribui", "com uso contínuo", "resultados variam".
- Não invente estudos, números, porcentagens, depoimentos ou nomes de clientes. Se citar ciência, fale de forma geral ("dermatologistas costumam recomendar…").
- Composição do Tônico MXD20 (só cite estes): óleo de uva, óleo de rícino, extrato de semente de abóbora, amor crescido, essências naturais, álcool parfum e água purificada. Modo de uso: algumas gotas na área limpa e seca, massagear 1 minuto, 2x ao dia, sem enxágue.
- Preços, se citar: 1 frasco R$ 137, 2 frascos R$ 197, 3 frascos R$ 237. Frete grátis. Pix ou até 12x no cartão.
- Quando o assunto for queda intensa, falhas em placas, coceira, feridas ou mudança brusca, recomende procurar um dermatologista.
- Não fale mal de concorrentes por nome.

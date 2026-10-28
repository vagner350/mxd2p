/* Conteúdo de vendas de cada produto (estilo landing page), indexado pelo slug da loja */
const MXD_PASSOS = [
  ["Pele limpa e seca", "Aplique algumas gotas na área da barba, do cabelo ou da sobrancelha."],
  ["Massageie 1 minuto", "Movimentos suaves para o tônico chegar à raiz. Não precisa enxaguar."],
  ["2x ao dia", "Use de manhã e à noite, todos os dias. A constância é o que traz resultado."]
];

const CONTEUDO = {
  "tonico-mxd20-barbudom-30ml": {
    hero: {"h": ["Barba, cabelo e", "sobrancelhas mais fortes", "e preenchidos"], "resumo": "O Strong Hair Tônico MXD20 Barbudom leva a força da Amazônia direto na raiz: nutre os folículos, ajuda a reduzir a queda e a preencher as falhas. Leve, de absorção rápida e não oleoso.", "checks": ["Desperta os folículos e estimula novos fios", "Fortalece e engrossa a raiz, reduzindo a queda", "Um só tônico para barba, cabelo e sobrancelha"]},
    titulo: ["Chega de", "falhas"],
    ofertas: { "1 frasco": { sub: "30ml · para começar" }, "2 frascos": { selo: "Mais vendido" }, "3 frascos": { sub: "tratamento completo", selo: "Melhor preço", padrao: true } },
    sub: "Barba, cabelo e sobrancelhas mais densos, fortes e preenchidos com ativos da Amazônia.",
    para: ["Barba falhada ou com áreas vazias", "Cabelo ralo e com queda", "Sobrancelhas fracas ou com falhas", "Quem quer fios mais grossos e resistentes"],
    beneficios: [
      ["sparkle", "Novos fios", "Desperta os folículos adormecidos e estimula o surgimento de novos fios."],
      ["shield", "Raiz forte", "Fortalece e engrossa a raiz, ajudando a reduzir a queda."],
      ["leaf", "Ativos naturais", "Óleo de uva, óleo de rícino e semente de abóbora nutrem os folículos."],
      ["drop", "Leve e seco", "Absorção rápida, não oleoso e sem enxágue."]
    ],
    formula: [
      ["Óleo de uva", "Leve e rico em antioxidantes, nutre o couro cabeludo e a pele da barba."],
      ["Óleo de rícino", "Tradicional no cuidado dos fios: ajuda a fortalecer e dar mais corpo."],
      ["Semente de abóbora", "Extrato que nutre os folículos e ajuda a manter os fios na raiz."]
    ],
    passos: MXD_PASSOS,
    faq: [
      ["Posso usar na barba, no cabelo e na sobrancelha?", "Sim. Aplique algumas gotas na área desejada, com a pele limpa e seca, e massageie por 1 minuto."],
      ["Quanto dura um frasco?", "O frasco tem 30ml. Usando algumas gotas duas vezes ao dia, rende cerca de um mês, dependendo da área."],
      ["Qual kit escolher?", "1 frasco para começar, 2 frascos (R$ 197) para seguir a rotina por mais tempo, ou 3 frascos (R$ 237) para o tratamento completo com o menor preço por frasco."],
      ["Arde ou irrita?", "A fórmula é suave, mas cada pele reage de um jeito: faça um teste em uma pequena área antes e não aplique sobre pele ferida ou irritada."]
    ]
  }
};

const LP_ICONS = {
  sparkle: '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>',
  drop: '<path d="M12 2.7C8 7.5 6 10.8 6 13.5a6 6 0 0012 0c0-2.7-2-6-6-10.8z"/>',
  wave: '<path d="M3 9c3-2 6 2 9 0s6-2 9 0M3 15c3-2 6 2 9 0s6-2 9 0"/>',
  leaf: '<path d="M20 4C10 4 4 9 4 16c0 1.4.3 2.7.8 4M20 4c0 10-5 16-12 16M20 4L8 16"/>',
  heart: '<path d="M12 21s-7-4.5-9.5-9A5.5 5.5 0 0112 6a5.5 5.5 0 019.5 6C19 16.5 12 21 12 21z"/>',
  clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  box: '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
  shield: '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/>'
};
function lpIcon(k){ return `<svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${LP_ICONS[k]||LP_ICONS.sparkle}</svg>`; }

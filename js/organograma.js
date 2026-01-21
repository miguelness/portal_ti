/**
 * Organograma Interativo com D3.js
 * Visual premium + filtro por Departamento ocultando nós/links não relevantes.
 */
class OrganogramChart {
  constructor(containerId, data) {
    this.container = d3.select(containerId);
    this.data = Array.isArray(data) ? data : [];
    this.width = 0;
    this.height = 0;
    this.svg = null;
    this.g = null;
    this.tree = null;
    this.root = null;
    this.zoom = null;

    // Estado de filtros
    this.activeDept = '';   // departamento atual filtrado ('' = sem filtro)
    this.activeSearch = ''; // termo de busca (opcional, mantém só destaque)

    // Tooltip (cria se precisar)
    this.tooltip = d3.select('#tooltip');
    if (this.tooltip.empty()) {
      this.tooltip = d3.select('body').append('div')
        .attr('id', 'tooltip')
        .attr('class', 'tooltip')
        .style('display', 'none');
    }

    // Auxiliares do tooltip
    this.pointerMoveRAF = null;
    this.tooltipTimeout = null;
    this.hideTimeout = null;

    // Layout responsivo
    this.updateResponsiveSettings();
    this.init();
    this.setupEventListeners();
  }

  /* ---------- Helpers de visual ---------- */

  getAvatarUrl(d){
    const u = d?.data?.foto_url || d?.data?.foto || null;
    return (u && typeof u === 'string' && u.trim() !== '') ? u : null;
  }

  getInitials(name=''){
    const parts = String(name).trim().split(/\s+/).slice(0,2);
    return parts.map(p => p[0]?.toUpperCase() || '').join('');
  }

  // Abrevia texto em no máx. N caracteres, adicionando reticências
  ellipsis(text, max=26){
    const s = String(text || '');
    if (s.length <= max) return s;
    return s.slice(0, Math.max(0, max-1)) + '…';
  }

  // Config do cartão (avatar + textos)
  get card(){
    return {
      w: this.nodeWidth, h: this.nodeHeight,
      padX: 14, padY: 12,
      avatarR: 18,
      gap: 10
    };
  }

  /* ---------- Init / Render ---------- */

  init() {
    this.container.selectAll('*').remove();

    requestAnimationFrame(() => {
      const rect = this.container.node().getBoundingClientRect();
      this.width  = Math.max(rect.width, 900);
      this.height = Math.max(rect.height, 420);

      this.svg = this.container.append('svg')
        .attr('width', this.width)
        .attr('height', this.height)
        .style('display', 'block');

      this.zoom = d3.zoom()
        .scaleExtent([0.1, 5])
        .filter((event) => {
          if (event.type === 'wheel') return event.ctrlKey;
          return event.button === 0 || event.button === 1 || event.type === 'mousedown';
        })
        .on('zoom', (event) => this.g.attr('transform', event.transform));

      this.svg.call(this.zoom);

      this.g = this.svg.append('g');

      this.tree = d3.tree().nodeSize([this.nodeWidth + 60, this.levelHeight]);

      this.processData();
      this.render();
      this.centerView();
    });
  }

  processData() {
    this.root = d3.hierarchy({
      id: 'root',
      nome: 'Organização',
      cargo: 'Estrutura Organizacional',
      children: this.data
    }, d => d.children);

    // Expande níveis 0-2, colapsa 3+
    this.root.descendants().forEach(d => {
      d._children = d.children;
      if (d.depth > 2) d.children = null;
    });

    this.tree(this.root);
  }

  render() {
    const nodes = this.root.descendants();
    const links = this.root.links();
    const C = this.card;

    this.g.selectAll('.link').remove();
    this.g.selectAll('.node').remove();

    // Links
    this.g.selectAll('.link')
      .data(links)
      .enter().append('path')
      .attr('class', 'link')
      .attr('d', d => `M${d.source.x},${d.source.y}
                       C${d.source.x},${(d.source.y + d.target.y)/2}
                        ${d.target.x},${(d.source.y + d.target.y)/2}
                        ${d.target.x},${d.target.y}`);

    // Nós
    const node = this.g.selectAll('.node')
      .data(nodes)
      .enter().append('g')
      .attr('class', d => `node level-${Math.min(d.depth,3)}`)
      .attr('transform', d => `translate(${d.x},${d.y})`)
      .on('mouseenter', (event, d) => this.showTooltip(event, d))
      .on('mousemove',  (event, d) => this.moveTooltip(event))
      .on('mouseleave', () => this.hideTooltip())
      .on('click',      (event, d) => this.toggleNode(d));

    // HITBOX invisível ampla
    node.append('rect')
      .attr('class', 'hitbox')
      .attr('x', -C.w/2 - 8)
      .attr('y', -C.h/2 - 8)
      .attr('width',  C.w + 16)
      .attr('height', C.h + 16)
      .style('fill', 'transparent')
      .style('pointer-events', 'all');

    // Cartão
    node.append('rect')
      .attr('x', -C.w/2)
      .attr('y', -C.h/2)
      .attr('width',  C.w)
      .attr('height', C.h);

    // Área interna (texto alinhado à esquerda)
    const inner = node.append('g')
      .attr('transform', `translate(${-C.w/2 + C.padX}, ${-C.h/2 + C.padY})`);

    // Avatar (clip de círculo)
    const avatarGroup = inner.append('g').attr('class','avatar');

    avatarGroup.append('clipPath')
      .attr('id', (d,i)=>`clip-${d.data?.id ?? i}`)
      .append('circle')
      .attr('cx', C.avatarR)
      .attr('cy', C.avatarR)
      .attr('r',  C.avatarR);

    // Imagem ou iniciais
    avatarGroup.each((d, i, nodesSel) => {
      const g = d3.select(nodesSel[i]);
      const url = this.getAvatarUrl(d);

      if (url) {
        g.append('image')
         .attr('href', url)
         .attr('x', 0)
         .attr('y', 0)
         .attr('width',  C.avatarR*2)
         .attr('height', C.avatarR*2)
         .attr('clip-path', `url(#clip-${d.data?.id ?? i})`);
      } else {
        g.append('circle')
         .attr('cx', C.avatarR)
         .attr('cy', C.avatarR)
         .attr('r',  C.avatarR)
         .attr('fill', '#cfe3ff');

        g.append('text')
         .attr('x', C.avatarR)
         .attr('y', C.avatarR + 4)
         .attr('text-anchor','middle')
         .attr('font-weight',700)
         .attr('font-size',12)
         .attr('fill','#1b4b99')
         .text(this.getInitials(d.data?.nome || ''));
      }

      // anel branco
      g.append('circle')
       .attr('cx', C.avatarR)
       .attr('cy', C.avatarR)
       .attr('r',  C.avatarR)
       .attr('fill','none')
       .attr('class','avatar-ring');
    });

    // Grupo de textos (ao lado direito do avatar)
    const textX = C.avatarR*2 + C.gap;
    const text = inner.append('g').attr('transform', `translate(${textX}, 2)`);

    text.append('text')
      .attr('class','name')
      .attr('x', 0).attr('y', 0)
      .attr('dominant-baseline','hanging')
      .attr('text-anchor','start')
      .text(d => this.ellipsis(d.data?.nome, 28));

    text.append('text')
      .attr('class','title')
      .attr('x', 0).attr('y', 18)
      .attr('dominant-baseline','hanging')
      .attr('text-anchor','start')
      .text(d => this.ellipsis(d.data?.cargo, 36));

    text.append('text')
      .attr('class','dept')
      .attr('x', 0).attr('y', 34)
      .attr('dominant-baseline','hanging')
      .attr('text-anchor','start')
      .text(d => this.ellipsis(d.data?.departamento || '', 38));

    // Botão +/− (indicador de filhos)
    node.each((d,i,nodesSel) => {
      if (!(d._children && d._children.length>0)) return;
      const g = d3.select(nodesSel[i]);
      g.append('circle')
        .attr('cx',  C.w/2 - 16)
        .attr('cy',  C.h/2 - 16)
        .attr('r',   9)
        .attr('fill', d.children ? '#dc3545' : '#28a745')
        .attr('stroke','#fff')
        .attr('stroke-width',2);

      g.append('text')
        .attr('x',  C.w/2 - 16)
        .attr('y',  C.h/2 - 12.5)
        .attr('text-anchor','middle')
        .attr('fill','#fff')
        .attr('font-size',11)
        .attr('font-weight',700)
        .text(d.children ? '−' : '+');
    });

    // Aplica visibilidade conforme filtros ativos
    this.applyActiveFilters();
  }

  /* ---------- Tooltip ---------- */

  showTooltip(event, d) {
    if (!d || d.depth === 0) return;

    if (this.tooltipTimeout) clearTimeout(this.tooltipTimeout);
    if (this.hideTimeout) { clearTimeout(this.hideTimeout); this.hideTimeout = null; }

    const data = d.data || {};
    let content = `
      <div class="fw-bold mb-2">${this.ellipsis(data.nome, 48)}</div>
      <div class="mb-1"><strong>Cargo:</strong> ${this.ellipsis(data.cargo, 70)}</div>
      <div class="mb-1"><strong>Departamento:</strong> ${this.ellipsis(data.departamento || '', 70)}</div>
      <div class="mb-1"><strong>Tipo:</strong> ${this.ellipsis(data.tipo_contrato || '', 40)}</div>
    `;
    if (data.email)    content += `<div class="mb-1"><strong>E-mail:</strong> ${this.ellipsis(data.email, 70)}</div>`;
    if (data.telefone) content += `<div class="mb-1"><strong>Telefone:</strong> ${this.ellipsis(data.telefone, 40)}</div>`;
    if (data.descricao) content += `<div class="mt-2"><strong>Descrição:</strong><br>${this.ellipsis(data.descricao, 160)}</div>`;

    this.tooltipTimeout = setTimeout(() => {
      this.tooltip.html(content).style('display', 'block').classed('show', true);
      this.moveTooltip(event);
    }, 80);
  }

  moveTooltip(event) {
    if (this.pointerMoveRAF) cancelAnimationFrame(this.pointerMoveRAF);
    this.pointerMoveRAF = requestAnimationFrame(() => {
      const tooltipWidth = 320, tooltipHeight = 200;
      let left = event.clientX + 16;
      let top  = event.clientY + 12;

      if (left + tooltipWidth > window.innerWidth)  left = event.clientX - tooltipWidth - 16;
      if (top + tooltipHeight > window.innerHeight) top = event.clientY - tooltipHeight - 12;

      left = Math.max(8, Math.min(left, window.innerWidth - tooltipWidth - 8));
      top  = Math.max(8, Math.min(top,  window.innerHeight - tooltipHeight - 8));

      this.tooltip.style('left', left + 'px').style('top', top + 'px');
    });
  }

  hideTooltip() {
    if (this.tooltipTimeout) { clearTimeout(this.tooltipTimeout); this.tooltipTimeout = null; }
    if (this.pointerMoveRAF) { cancelAnimationFrame(this.pointerMoveRAF); this.pointerMoveRAF = null; }

    this.hideTimeout = setTimeout(() => {
      this.tooltip.classed('show', false);
      setTimeout(() => { if (!this.tooltip.classed('show')) this.tooltip.style('display','none'); }, 200);
    }, 120);
  }

  /* ---------- Interação ---------- */

  toggleNode(d) {
    if (!d) return;
    if (d._children) {
      d.children = d.children ? null : d._children;
      this.update();
    }
  }

  update() {
    this.tree(this.root);
    this.render();
  }

  centerView() {
    const b = this.g.node().getBBox();
    if (!b || !isFinite(b.width) || !isFinite(b.height) || b.width === 0 || b.height === 0) return;

    const fullW = this.width, fullH = this.height;
    const midX = b.x + b.width/2, midY = b.y + b.height/2;
    const scale = Math.min(fullW / b.width, fullH / b.height) * 0.82;
    const translate = [fullW/2 - scale*midX, fullH/2 - scale*midY];

    this.svg.transition().duration(700)
      .call(this.zoom.transform, d3.zoomIdentity.translate(translate[0], translate[1]).scale(scale));
  }

  expandAll() {
    this.root.descendants().forEach(d => { if (d._children) d.children = d._children; });
    this.update();
  }

  collapseAll() {
    this.root.descendants().forEach(d => { if (d.depth > 1 && d._children) d.children = null; });
    this.update();
  }

  resetView() {
    this.collapseAll();
    setTimeout(() => this.centerView(), 100);
  }

  zoomIn(){  this.svg.transition().call(this.zoom.scaleBy, 1.5); }
  zoomOut(){ this.svg.transition().call(this.zoom.scaleBy, 1/1.5); }

  /* ---------- Filtros/Busca ---------- */

  search(query) {
    this.activeSearch = String(query || '');
    if (!this.activeSearch) {
      this.clearHighlight();
      return;
    }
    const q = this.activeSearch.toLowerCase();
    const matches = this.root.descendants().filter(node => {
      if (!node.data || node.depth === 0) return false;
      const n = (node.data.nome || '').toLowerCase();
      const c = (node.data.cargo || '').toLowerCase();
      const d = (node.data.departamento || '').toLowerCase();
      return n.includes(q) || c.includes(q) || d.includes(q);
    });
    this.highlightNodes(matches);
  }

  filterByDepartment(department) {
    this.activeDept = department || '';
    this.applyActiveFilters();
  }

  filterByLevel(level) {
    // Mantive igual ao anterior (só destaque). Se quiser que o nível também oculte, posso aplicar a mesma lógica do depto.
    if (!level) {
      this.clearHighlight();
      return;
    }
    const lvl = String(level);
    const matches = this.root.descendants().filter(n => n.data && String(n.data.nivel_hierarquico) === lvl);
    this.highlightNodes(matches);
  }

  /* ---------- Engine de visibilidade para o filtro de Departamento ---------- */

  applyActiveFilters() {
    // Se não há filtro por Departamento, mostra tudo e sai
    if (!this.activeDept) {
      this.showAllNodesAndLinks();
      return;
    }

    // Monta o conjunto de nós a manter (matches + ancestrais)
    const keepIds = this.computeDeptKeepSet(this.activeDept);

    // Aplica display:none em nós fora do conjunto; preserva raiz
    this.g.selectAll('.node')
      .style('display', d => {
        if (d.depth === 0) return null; // mantém raiz visível
        const id = d.data?.id;
        return keepIds.has(id) ? null : 'none';
      });

    // Esconde links que apontam para nós ocultos
    this.g.selectAll('.link')
      .style('display', d => {
        const srcOk = (d.source.depth === 0) || keepIds.has(d.source.data?.id);
        const tgtOk = (d.target.depth === 0) || keepIds.has(d.target.data?.id);
        return (srcOk && tgtOk) ? null : 'none';
      });

    // Mantém qualquer destaque de busca (se houver)
    if (this.activeSearch) {
      const q = this.activeSearch.toLowerCase();
      const matches = this.root.descendants().filter(node => {
        if (!node.data || node.depth === 0) return false;
        const n = (node.data.nome || '').toLowerCase();
        const c = (node.data.cargo || '').toLowerCase();
        const d = (node.data.departamento || '').toLowerCase();
        return n.includes(q) || c.includes(q) || d.includes(q);
      });
      this.highlightNodes(matches, keepIds); // destaca apenas entre os visíveis
    } else {
      this.clearHighlight();
    }
  }

  computeDeptKeepSet(dept) {
    const keep = new Set();
    const all = this.root.descendants();

    // nós cujo departamento corresponde
    const matches = all.filter(n => n.data && n.data.departamento === dept);

    // adiciona matches + todos os ancestrais até a raiz
    for (const m of matches) {
      let cur = m;
      while (cur) {
        if (cur.depth === 0) { cur = null; continue; } // não tem id útil
        const id = cur.data?.id;
        if (id != null && !keep.has(id)) keep.add(id);
        cur = cur.parent;
      }
    }
    return keep;
  }

  showAllNodesAndLinks() {
    this.g.selectAll('.node').style('display', null);
    this.g.selectAll('.link').style('display', null);
    // E remove qualquer opacidade alterada por highlight
    this.g.selectAll('.node').style('opacity', 1);
  }

  /* ---------- Destaque (opcional) ---------- */

  highlightNodes(matches, restrictToSet = null) {
    // Se houver um conjunto de nós visíveis (dept), só destaca dentro dele
    const isAllowed = (d) => {
      if (!restrictToSet) return true;
      if (d.depth === 0) return true;
      const id = d.data?.id;
      return restrictToSet.has(id);
    };

    this.g.selectAll('.node')
      .style('opacity', d => {
        if (!isAllowed(d)) return 1; // nós ocultos já estão display:none; não precisa alterar
        if (d.depth === 0) return 1;
        return matches.some(m => m.data && m.data.id === d.data.id) ? 1 : 0.32;
      });
  }

  clearHighlight() {
    this.g.selectAll('.node').style('opacity', 1);
  }

  /* ---------- Responsivo ---------- */

  updateResponsiveSettings() {
    const w = window.innerWidth;
    if (w <= 576) {
      this.nodeWidth = 210; this.nodeHeight = 86; this.levelHeight = 110;
    } else if (w <= 768) {
      this.nodeWidth = 230; this.nodeHeight = 92; this.levelHeight = 120;
    } else {
      this.nodeWidth = 260; this.nodeHeight = 96; this.levelHeight = 130;
    }
  }

  setupEventListeners() {
    let resizeTimeout;
    window.addEventListener('resize', () => {
      clearTimeout(resizeTimeout);
      resizeTimeout = setTimeout(() => {
        this.updateResponsiveSettings();
        const rect = this.container.node().getBoundingClientRect();
        const newW = Math.max(rect.width, 900);
        const newH = Math.max(rect.height, 420);

        if (Math.abs(newW - this.width) > 10 || Math.abs(newH - this.height) > 10) {
          this.width = newW; this.height = newH;
          if (this.svg) this.svg.attr('width', this.width).attr('height', this.height);
          if (this.tree) { this.tree.nodeSize([this.nodeWidth + 60, this.levelHeight]); this.update(); }
        }
      }, 220);
    });
  }
}

/* Controles (sem mudanças no HTML) */
function setupOrganogramControls(chart) {
  const on = (id, ev, fn) => {
    const el = document.getElementById(id);
    if (!el) { console.warn(`Element with ID '${id}' not found`); return; }
    el.addEventListener(ev, fn);
  };

  on('expandAll','click', () => chart.expandAll());
  on('collapseAll','click', () => chart.collapseAll());
  on('resetView','click', () => chart.resetView());
  on('zoomIn','click', () => chart.zoomIn());
  on('zoomOut','click', () => chart.zoomOut());
  on('searchInput','input', e => chart.search(e.target.value));
  on('departmentFilter','change', e => chart.filterByDepartment(e.target.value));
  on('levelFilter','change', e => chart.filterByLevel(e.target.value));
}

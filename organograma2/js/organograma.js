(function () {
  // Tamanhos e espaçamentos
  const NODE = { w: 220, h: 90, padX: 12, padY: 10 };
  const GAP  = { x: 40,  y: 120 };
  const ZOOM_LIMIT = [0.3, 3];

  let svg, g, linkG, nodeG, zoomBehavior, root, tree;
  let allNodes = [], allLinks = [];
  let searchTerm = '', activeDept = '';

  document.addEventListener('DOMContentLoaded', init);

  async function init() {
    const container = d3.select('#orgChart');
    ensureMinHeight(container);

    const { width, height } = getContainerSize(container);
    svg = container.append('svg')
      .attr('width', width)
      .attr('height', height);

    g = svg.append('g');
    linkG = g.append('g').attr('class', 'links');
    nodeG = g.append('g').attr('class', 'nodes');

    zoomBehavior = d3.zoom()
      .scaleExtent(ZOOM_LIMIT)
      .filter((event) => {
        // pan com arraste normal, zoom com ctrl+scroll
        if (event.type === 'wheel') return event.ctrlKey;
        return true;
      })
      .on('zoom', (event) => g.attr('transform', event.transform));
    svg.call(zoomBehavior);

    try {
      const payload = await fetchData();
      if (!payload?.success || !Array.isArray(payload.data)) {
        throw new Error('Resposta inválida da API.');
      }
      fillDepartments(payload.departamentos || []);

      // Nó raiz sintético para múltiplas raízes
      root = d3.hierarchy({ nome: 'Organização', children: payload.data }, d => d.children);

      // Guarda children reais em _children e colapsa níveis profundos
      root.descendants().forEach(d => {
        d._children = d.children;
        if (d.depth > 2) d.children = null;
      });

      tree = d3.tree()
        .nodeSize([NODE.w + GAP.x, GAP.y])
        .separation((a, b) => (a.parent === b.parent ? 1 : 1.2));

      updateLayout();
      fitView();
      wireControls();

    } catch (err) {
      console.error('[Organograma] erro:', err);
      showError(container, err?.message || 'Falha ao carregar');
    }
  }

  async function fetchData() {
    const tries = [
      '../api/organograma.php', // /portal/organograma  -> /portal/api
      'api/organograma.php'     // caso api esteja dentro da mesma pasta
    ];
    let lastErr;
    for (const url of tries) {
      try {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.json();
      } catch (e) { lastErr = e; }
    }
    throw lastErr || new Error('API não encontrada');
  }

  function updateLayout() {
    tree(root);

    const links = root.links();
    const nodes = root.descendants();
    allNodes = nodes;
    allLinks = links;

    // LINKS (ortogonais “cotovelo” para ficar limpo)
    const linkGen = (d) => {
      const x1 = d.source.x, y1 = d.source.y + NODE.h/2;
      const x2 = d.target.x, y2 = d.target.y - NODE.h/2;
      const mx = x1 + (x2 - x1) / 2;
      // M x1 y1  L mx y1  L mx y2  L x2 y2
      return `M${x1},${y1}L${mx},${y1}L${mx},${y2}L${x2},${y2}`;
    };

    const linkSel = linkG.selectAll('path.link')
      .data(links, d => `${nodeKey(d.source)}->${nodeKey(d.target)}`);

    linkSel.enter()
      .append('path')
      .attr('class', 'link')
      .attr('stroke', '#97a6ba')
      .attr('stroke-width', 1.6)
      .attr('fill', 'none')
      .merge(linkSel)
      .attr('d', linkGen);

    linkSel.exit().remove();

    // NODES
    const nodeSel = nodeG.selectAll('g.node')
      .data(nodes, nodeKey);

    const nodeEnter = nodeSel.enter()
      .append('g')
      .attr('class', d => 'node ' + (d.children ? 'expanded' : (d._children ? 'collapsed' : '')))
      .attr('transform', d => `translate(${d.x},${d.y})`)
      .on('click', (event, d) => { event.stopPropagation(); toggle(d); })
      .on('mouseenter', (event, d) => showTooltip(event, d))
      .on('mousemove', (event) => moveTooltip(event))
      .on('mouseleave', hideTooltip);

    // Card
    nodeEnter.append('rect')
      .attr('class', 'card')
      .attr('x', -NODE.w/2).attr('y', -NODE.h/2)
      .attr('width', NODE.w).attr('height', NODE.h)
      .attr('rx', 10)
      .attr('fill', '#fff')                 // Fallback visual
      .attr('stroke', '#d6d9df')            // Fallback
      .attr('stroke-width', 1.25)           // Fallback
      .attr('filter', 'drop-shadow(0 1px 2px rgba(0,0,0,0.06))');

    // Barra de nível
    nodeEnter.append('rect')
      .attr('x', -NODE.w/2).attr('y', -NODE.h/2)
      .attr('width', 6).attr('height', NODE.h).attr('rx', 10)
      .attr('fill', '#2f6fdc');

    // Conteúdo interno
    const inner = nodeEnter.append('g')
      .attr('transform', `translate(${ -NODE.w/2 + NODE.padX }, ${ -NODE.h/2 + NODE.padY })`);

    // Avatar (iniciais)
    const A = 16;
    inner.append('circle')
      .attr('cx', A).attr('cy', A).attr('r', A).attr('fill', '#e0e7ff');
    inner.append('text')
      .attr('x', A).attr('y', A + 4)
      .attr('text-anchor', 'middle')
      .attr('font-weight', 700)
      .attr('font-size', 11)
      .attr('fill', '#1b4b99')
      .text(d => initials(d?.data?.nome || ''));

    const text = inner.append('g').attr('transform', `translate(${A*2 + 10},0)`);
    text.append('text')
      .attr('y', 2).attr('text-anchor', 'start')
      .attr('fill', '#1f2937').attr('font-weight', 700).attr('font-size', 13)
      .text(d => ellipsis(d?.data?.nome || (d.depth === 0 ? 'Organização' : ''), 24));
    text.append('text')
      .attr('y', 20).attr('text-anchor', 'start')
      .attr('fill', '#6b7280').attr('font-weight', 500).attr('font-size', 11.5)
      .text(d => ellipsis(d?.data?.cargo || '', 38));
    text.append('text')
      .attr('y', 36).attr('text-anchor', 'start')
      .attr('fill', '#344a7a').attr('font-size', 10.5)
      .text(d => ellipsis(d?.data?.departamento || '', 28));

    // Indicador +/−
    nodeEnter.each(function (d) {
      if (!d._children) return;
      const gx = d3.select(this);
      gx.append('circle')
        .attr('class', 'bubble')
        .attr('cx', NODE.w/2 - 14).attr('cy', NODE.h/2 - 14).attr('r', 9)
        .attr('fill', d.children ? '#f03e3e' : '#12b886')
        .attr('stroke', '#fff').attr('stroke-width', 2);
      gx.append('text')
        .attr('x', NODE.w/2 - 14).attr('y', NODE.h/2 - 10.5)
        .attr('text-anchor', 'middle').attr('font-size', 11)
        .attr('font-weight', 700).attr('fill', '#fff')
        .text(d.children ? '−' : '+');
    });

    const nodeUpdate = nodeEnter.merge(nodeSel);
    nodeUpdate
      .attr('class', d => 'node ' + (d.children ? 'expanded' : (d._children ? 'collapsed' : '')))
      .transition().duration(250)
      .attr('transform', d => `translate(${d.x},${d.y})`);

    nodeSel.exit().remove();

    autosizeSVG();
    applyFiltersAndSearch();
  }

  function toggle(d) {
    if (!d || !d._children) return;
    d.children = d.children ? null : d._children;
    updateLayout();
  }

  function expandAll() {
    root.descendants().forEach(d => { if (d._children) d.children = d._children; });
    updateLayout();
  }

  function collapseDeep() {
    root.descendants().forEach(d => {
      if (d.depth > 2 && d._children) d.children = null;
    });
    updateLayout();
  }

  function fitView() {
    const b = g.node().getBBox();
    if (!b || b.width === 0 || b.height === 0) return;
    const container = document.getElementById('orgChart').getBoundingClientRect();
    const m = 60;
    const scale = Math.min(
      (container.width - m) / (b.width + m),
      (container.height - m) / (b.height + m),
      ZOOM_LIMIT[1]
    );
    const tx = (container.width/2)  - (b.x + b.width/2)  * scale;
    const ty = (container.height/2) - (b.y + b.height/2) * scale;
    svg.transition().duration(600).call(zoomBehavior.transform, d3.zoomIdentity.translate(tx, ty).scale(scale));
  }

  function wireControls() {
    byId('expandAll').addEventListener('click', expandAll);
    byId('collapseAll').addEventListener('click', collapseDeep);
    byId('fitView').addEventListener('click', fitView);
    byId('zoomIn').addEventListener('click', () => svg.transition().call(zoomBehavior.scaleBy, 1.2));
    byId('zoomOut').addEventListener('click', () => svg.transition().call(zoomBehavior.scaleBy, 1/1.2));

    byId('searchInput').addEventListener('input', (e) => {
      searchTerm = (e.target.value || '').toLowerCase();
      applyFiltersAndSearch();
    });

    byId('departmentFilter').addEventListener('change', (e) => {
      activeDept = e.target.value || '';
      applyFiltersAndSearch();
    });

    let ro;
    window.addEventListener('resize', () => {
      if (ro) cancelAnimationFrame(ro);
      ro = requestAnimationFrame(() => {
        ensureMinHeight(d3.select('#orgChart'));
        const { width, height } = getContainerSize(d3.select('#orgChart'));
        svg.attr('width', width).attr('height', height);
        fitView();
      });
    });
  }

  function applyFiltersAndSearch() {
    // 1) Filtra por departamento: mostra nós do dept e seus ancestrais; oculta demais
    if (activeDept) {
      const keep = new Set();
      for (const n of allNodes) {
        const dep = (n.data?.departamento || '');
        if (dep === activeDept) {
          let cur = n;
          while (cur) { keep.add(cur); cur = cur.parent; }
        }
      }
      nodeG.selectAll('g.node').classed('hidden', d => !keep.has(d));
      linkG.selectAll('path.link').classed('hidden', l => !(keep.has(l.source) && keep.has(l.target)));
    } else {
      nodeG.selectAll('g.node').classed('hidden', false);
      linkG.selectAll('path.link').classed('hidden', false);
    }

    // 2) Busca: “apaga” quem não casa (sem esconder)
    if (searchTerm) {
      nodeG.selectAll('g.node').classed('dimmed', d => {
        if (d.depth === 0) return false;
        if (activeDept) {
          // se já está hidden por dept, não precisa dim
          const isHidden = d3.select(d3.selectAll('g.node').nodes()[d.index]).classed('hidden');
          if (isHidden) return false;
        }
        const nome = (d.data?.nome || '').toLowerCase();
        const cargo = (d.data?.cargo || '').toLowerCase();
        const dep   = (d.data?.departamento || '').toLowerCase();
        return !(nome.includes(searchTerm) || cargo.includes(searchTerm) || dep.includes(searchTerm));
      });
    } else {
      nodeG.selectAll('g.node').classed('dimmed', false);
    }
  }

  function fillDepartments(deps) {
    const sel = byId('departmentFilter');
    deps.forEach(d => {
      const opt = document.createElement('option');
      opt.value = d; opt.textContent = d;
      sel.appendChild(opt);
    });
  }

  // Tooltip
  const tooltipEl = document.getElementById('tooltip');
  function showTooltip(event, d) {
    if (d.depth === 0) return;
    const data = d.data || {};
    tooltipEl.innerHTML = `
      <div class="title">${esc(data.nome || '')}</div>
      <div><strong>Cargo:</strong> ${esc(data.cargo || '-')}</div>
      <div><strong>Departamento:</strong> ${esc(data.departamento || '-')}</div>
      ${data.email ? `<div><strong>E-mail:</strong> ${esc(data.email)}</div>` : ''}
      ${data.telefone ? `<div><strong>Telefone:</strong> ${esc(data.telefone)}</div>` : ''}
      ${data.descricao ? `<div style="margin-top:6px">${esc(data.descricao)}</div>` : ''}
    `;
    tooltipEl.classList.add('show');
    moveTooltip(event);
  }
  function moveTooltip(event) {
    const W = 320, H = 220;
    let x = event.clientX + 14, y = event.clientY + 12;
    if (x + W > window.innerWidth)  x = event.clientX - W - 14;
    if (y + H > window.innerHeight) y = event.clientY - H - 12;
    tooltipEl.style.left = x + 'px';
    tooltipEl.style.top  = y + 'px';
  }
  function hideTooltip(){ tooltipEl.classList.remove('show'); }

  // Helpers
  function nodeKey(d){ return d.data?.id ?? `depth${d.depth}-i${d.index}`; }
  function byId(id){ return document.getElementById(id); }
  function getContainerSize(sel){
    const r = sel.node().getBoundingClientRect();
    return { width: Math.max(600, r.width), height: Math.max(400, r.height) };
  }
  function ensureMinHeight(sel){
    if (parseInt(getComputedStyle(sel.node()).height,10) < 200) {
      sel.style('min-height', 'calc(100vh - 56px)');
    }
  }
  function autosizeSVG() {
    const b = g.node().getBBox();
    if (!b || !isFinite(b.width) || !isFinite(b.height)) return;
    const pad = 120;
    svg.attr('width',  Math.max(document.body.clientWidth,  b.width  + pad));
    svg.attr('height', Math.max(window.innerHeight - 56,     b.height + pad));
  }
  function initials(name=''){
    const p = String(name).trim().split(/\s+/).slice(0,2);
    return p.map(x => (x[0]||'').toUpperCase()).join('');
  }
  function ellipsis(s, max=24){ s = String(s||''); return s.length<=max ? s : s.slice(0,max-1)+'…'; }
  function esc(str){
    return String(str).replace(/[&<>"'`=\/]/g, s => ({
      '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;','=':'&#x3D;'
    }[s]));
  }
  function showError(sel, msg){
    sel.html(`
      <div style="padding:24px">
        <div style="background:#fff;border:1px solid #d6d9df;border-radius:12px;padding:16px;max-width:560px">
          <strong>Erro ao carregar o organograma:</strong><br>${esc(msg)}
        </div>
      </div>
    `);
  }
})();

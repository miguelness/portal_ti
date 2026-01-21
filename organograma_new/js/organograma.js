/**
 * Organograma Moderno - JavaScript Principal
 * Funcionalidades: Temas, animações, busca em tempo real, exportação, etc.
 */

class OrganogramaModerno {
  constructor(params = {}) {
    this.params = params;
    this.currentView = params.view_mode || 'org';
    this.currentTheme = params.theme || 'auto';
    this.isLoading = false;
    this.searchTimeout = null;
    this.debounceDelay = 300;
    
    this.init();
  }

  init() {
    this.setupTheme();
    this.setupEventListeners();
    this.setupSearch();
    this.setupKeyboardShortcuts();
    this.setupAccessibility();
  }

  // ===== Theme Management =====
  setupTheme() {
    // Auto theme detection
    if (this.currentTheme === 'auto') {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.currentTheme = prefersDark ? 'dark' : 'light';
    }
    
    this.applyTheme();
    
    // Listen for system theme changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
      if (this.params.theme === 'auto') {
        this.currentTheme = e.matches ? 'dark' : 'light';
        this.applyTheme();
      }
    });
  }

  applyTheme() {
    document.documentElement.setAttribute('data-theme', this.currentTheme);
    localStorage.setItem('organograma-theme', this.currentTheme);
    
    // Update theme button icon
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
      const icon = themeBtn.querySelector('.theme-icon--' + this.currentTheme);
      if (icon) {
        themeBtn.innerHTML = icon.outerHTML;
      }
    }
  }

  toggleTheme() {
    const themes = ['light', 'dark', 'auto'];
    const currentIndex = themes.indexOf(this.params.theme);
    const nextIndex = (currentIndex + 1) % themes.length;
    
    this.params.theme = themes[nextIndex];
    this.currentTheme = this.params.theme;
    
    if (this.currentTheme === 'auto') {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      this.currentTheme = prefersDark ? 'dark' : 'light';
    }
    
    this.applyTheme();
    this.updateURL();
  }

  // ===== Event Listeners =====
  setupEventListeners() {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
      themeToggle.addEventListener('click', () => this.toggleTheme());
    }

    // Settings panel
    const settingsBtn = document.getElementById('settingsBtn');
    const settingsClose = document.getElementById('settingsClose');
    const settingsPanel = document.getElementById('settingsPanel');
    
    if (settingsBtn && settingsPanel) {
      settingsBtn.addEventListener('click', () => this.toggleSettingsPanel());
    }
    
    if (settingsClose && settingsPanel) {
      settingsClose.addEventListener('click', () => this.toggleSettingsPanel());
    }

    // View mode buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const view = e.currentTarget.getAttribute('data-view');
        this.setViewMode(view);
      });
    });

    // Zoom controls
    const zoomRange = document.getElementById('zoomRange');
    if (zoomRange) {
      zoomRange.addEventListener('input', (e) => {
        const zoom = parseFloat(e.target.value);
        this.setZoom(zoom);
      });
    }

    // Expansion mode
    const expansionRadios = document.querySelectorAll('input[name="expansionMode"]');
    expansionRadios.forEach(radio => {
      radio.addEventListener('change', (e) => {
        this.params.modo = e.target.value;
        this.updateURL();
      });
    });

    // Company filters
    const selectAllCheckbox = document.getElementById('selectAllEmpresas');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', (e) => {
        this.toggleAllCompanies(e.target.checked);
      });
    }

    const companyCheckboxes = document.querySelectorAll('input[name="empresas[]"]');
    companyCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        this.updateCompanyFilters();
      });
    });

    // Export button
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
      exportBtn.addEventListener('click', () => this.exportData());
    }

    // Modal close
    const modalClose = document.getElementById('modalClose');
    if (modalClose) {
      modalClose.addEventListener('click', () => this.closeModal());
    }

    // Click outside modal to close
    const modal = document.getElementById('personModal');
    if (modal) {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          this.closeModal();
        }
      });
    }

    // Settings panel controls
    const themeSelect = document.getElementById('themeSelect');
    if (themeSelect) {
      themeSelect.addEventListener('change', (e) => {
        this.params.theme = e.target.value;
        this.currentTheme = this.params.theme;
        if (this.currentTheme === 'auto') {
          const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
          this.currentTheme = prefersDark ? 'dark' : 'light';
        }
        this.applyTheme();
        this.updateURL();
      });
    }

    const defaultZoom = document.getElementById('defaultZoom');
    if (defaultZoom) {
      defaultZoom.addEventListener('input', (e) => {
        const zoom = parseFloat(e.target.value);
        document.getElementById('defaultZoomValue').textContent = zoom + '×';
        this.params.zoom = zoom;
        this.updateURL();
      });
    }

    const animationsToggle = document.getElementById('animationsToggle');
    if (animationsToggle) {
      animationsToggle.addEventListener('change', (e) => {
        document.documentElement.style.setProperty('--animation-duration', e.target.checked ? '250ms' : '0ms');
        localStorage.setItem('organograma-animations', e.target.checked);
      });
    }

    // Items per page
    const itemsPerPage = document.getElementById('itemsPerPage');
    if (itemsPerPage) {
      itemsPerPage.addEventListener('change', (e) => {
        this.params.per_page = parseInt(e.target.value);
        this.params.page = 1;
        this.updateURL();
        this.reloadData();
      });
    }

    // Pagination
    const pageButtons = document.querySelectorAll('.page-btn[data-page]');
    pageButtons.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const page = parseInt(e.currentTarget.getAttribute('data-page'));
        this.goToPage(page);
      });
    });
  }

  // ===== Search Functionality =====
  setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
      clearTimeout(this.searchTimeout);
      const query = e.target.value.trim();
      
      this.searchTimeout = setTimeout(() => {
        this.performSearch(query);
      }, this.debounceDelay);
    });

    // Clear search on Escape
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        e.target.value = '';
        this.performSearch('');
      }
    });
  }

  performSearch(query) {
    this.params.search = query;
    this.params.page = 1;
    
    if (this.currentView === 'org') {
      this.filterOrganograma(query);
    } else {
      this.updateURL();
      this.reloadData();
    }
  }

  filterOrganograma(query) {
    const nodes = document.querySelectorAll('.person-node');
    const lowerQuery = query.toLowerCase();
    let visibleCount = 0;

    nodes.forEach(node => {
      const cardData = JSON.parse(node.getAttribute('data-card') || '{}');
      const searchableText = [
        cardData.nome,
        cardData.cargo,
        cardData.departamento,
        cardData.empresa,
        cardData.email
      ].join(' ').toLowerCase();

      const isVisible = !query || searchableText.includes(lowerQuery);
      const li = node.closest('li');
      
      if (isVisible) {
        li.style.display = '';
        li.style.opacity = '1';
        visibleCount++;
      } else {
        li.style.opacity = '0.3';
      }
    });

    // Show/hide empty state
    const emptyState = document.querySelector('.empty-state');
    const orgTree = document.getElementById('orgTree');
    
    if (visibleCount === 0 && query) {
      if (!emptyState) {
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'empty-state';
        emptyDiv.innerHTML = `
          <div class="empty-icon">🔍</div>
          <h3>Nenhum resultado encontrado</h3>
          <p>Tente ajustar sua busca</p>
        `;
        orgTree.appendChild(emptyDiv);
      }
    } else if (emptyState) {
      emptyState.remove();
    }

    // Redraw connections
    if (this.currentView === 'org') {
      setTimeout(() => this.redrawConnections(), 100);
    }
  }

  // ===== View Mode Management =====
  setViewMode(mode) {
    if (this.currentView === mode) return;
    
    this.currentView = mode;
    this.params.view_mode = mode;
    this.params.page = 1;
    
    // Update active button
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-view') === mode);
    });
    
    this.updateURL();
    this.reloadData();
  }

  // ===== Organograma Tree Functions =====
  initTree() {
    this.setupOrganogramaEvents();
    this.setupDragToScroll();
    this.setZoom(this.params.zoom || 1);
    this.redrawConnections();
    
    // Auto-fit on load
    setTimeout(() => this.autoFit(), 500);
  }

  setupOrganogramaEvents() {
    // Person node clicks
    const personNodes = document.querySelectorAll('.person-node');
    personNodes.forEach(node => {
      node.addEventListener('click', (e) => {
        e.preventDefault();
        this.showPersonModal(node);
      });

      // Hover effects
      node.addEventListener('mouseenter', () => {
        node.style.transform = 'translateY(-4px) scale(1.02)';
      });

      node.addEventListener('mouseleave', () => {
        node.style.transform = '';
      });
    });

    // Expand/collapse functionality
    const details = document.querySelectorAll('details');
    details.forEach(detail => {
      detail.addEventListener('toggle', () => {
        this.redrawConnections();
        
        // Focus mode - close siblings
        if (this.params.modo === 'foco' && detail.open) {
          const parent = detail.closest('li').parentElement;
          if (parent) {
            const siblings = parent.querySelectorAll(':scope > li > details[open]');
            siblings.forEach(sibling => {
              if (sibling !== detail) {
                sibling.open = false;
              }
            });
          }
        }
      });
    });

    // Window resize
    window.addEventListener('resize', () => {
      clearTimeout(this.resizeTimeout);
      this.resizeTimeout = setTimeout(() => {
        this.redrawConnections();
        this.autoFit();
      }, 250);
    });
  }

  setupDragToScroll() {
    const orgStage = document.getElementById('orgStage');
    if (!orgStage) return;

    let isDragging = false;
    let startX, startY, scrollLeft, scrollTop;

    orgStage.addEventListener('mousedown', (e) => {
      if (e.target.closest('.person-node')) return;
      
      isDragging = true;
      orgStage.style.cursor = 'grabbing';
      startX = e.pageX - orgStage.offsetLeft;
      startY = e.pageY - orgStage.offsetTop;
      scrollLeft = orgStage.scrollLeft;
      scrollTop = orgStage.scrollTop;
      
      e.preventDefault();
    });

    orgStage.addEventListener('mouseleave', () => {
      isDragging = false;
      orgStage.style.cursor = 'grab';
    });

    orgStage.addEventListener('mouseup', () => {
      isDragging = false;
      orgStage.style.cursor = 'grab';
    });

    orgStage.addEventListener('mousemove', (e) => {
      if (!isDragging) return;
      
      e.preventDefault();
      const x = e.pageX - orgStage.offsetLeft;
      const y = e.pageY - orgStage.offsetTop;
      const walkX = (x - startX) * 2;
      const walkY = (y - startY) * 2;
      
      orgStage.scrollLeft = scrollLeft - walkX;
      orgStage.scrollTop = scrollTop - walkY;
    });

    // Touch support
    orgStage.addEventListener('touchstart', (e) => {
      if (e.target.closest('.person-node')) return;
      
      isDragging = true;
      const touch = e.touches[0];
      startX = touch.pageX - orgStage.offsetLeft;
      startY = touch.pageY - orgStage.offsetTop;
      scrollLeft = orgStage.scrollLeft;
      scrollTop = orgStage.scrollTop;
    });

    orgStage.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      
      e.preventDefault();
      const touch = e.touches[0];
      const x = touch.pageX - orgStage.offsetLeft;
      const y = touch.pageY - orgStage.offsetTop;
      const walkX = (x - startX) * 2;
      const walkY = (y - startY) * 2;
      
      orgStage.scrollLeft = scrollLeft - walkX;
      orgStage.scrollTop = scrollTop - walkY;
    });

    orgStage.addEventListener('touchend', () => {
      isDragging = false;
    });
  }

  setZoom(zoom) {
    this.params.zoom = Math.max(0.5, Math.min(2, zoom));
    
    const orgTree = document.getElementById('orgTree');
    if (orgTree) {
      orgTree.style.transform = `scale(${this.params.zoom})`;
    }
    
    const zoomValue = document.getElementById('zoomValue');
    if (zoomValue) {
      zoomValue.textContent = this.params.zoom + '×';
    }
    
    const zoomRange = document.getElementById('zoomRange');
    if (zoomRange) {
      zoomRange.value = this.params.zoom;
    }
    
    this.redrawConnections();
    this.updateURL();
  }

  autoFit() {
    const orgStage = document.getElementById('orgStage');
    const orgTree = document.getElementById('orgTree');
    
    if (!orgStage || !orgTree) return;
    
    const stageWidth = orgStage.clientWidth;
    const stageHeight = orgStage.clientHeight;
    const treeWidth = orgTree.scrollWidth * this.params.zoom;
    const treeHeight = orgTree.scrollHeight * this.params.zoom;
    
    if (treeWidth > stageWidth || treeHeight > stageHeight) {
      const scaleX = (stageWidth - 40) / treeWidth;
      const scaleY = (stageHeight - 40) / treeHeight;
      const optimalZoom = Math.min(scaleX, scaleY, 1);
      
      if (optimalZoom < this.params.zoom) {
        this.setZoom(optimalZoom);
      }
    }
  }

  redrawConnections() {
    const svg = document.getElementById('orgWires');
    const orgTree = document.getElementById('orgTree');
    
    if (!svg || !orgTree) return;
    
    // Clear existing lines
    svg.innerHTML = '';
    
    // Get all visible connections
    const connections = this.getVisibleConnections();
    
    // Draw lines
    connections.forEach(connection => {
      this.drawConnection(svg, connection);
    });
    
    // Resize SVG
    const bbox = orgTree.getBoundingClientRect();
    svg.setAttribute('width', bbox.width);
    svg.setAttribute('height', bbox.height);
  }

  getVisibleConnections() {
    const connections = [];
    const details = document.querySelectorAll('details[open]');
    
    details.forEach(detail => {
      const summary = detail.querySelector('summary');
      const children = detail.querySelectorAll(':scope > ul > li > details');
      
      if (children.length > 0 && this.isElementVisible(summary)) {
        children.forEach(child => {
          if (this.isElementVisible(child)) {
            connections.push({
              parent: summary,
              child: child.querySelector('summary')
            });
          }
        });
      }
    });
    
    return connections;
  }

  isElementVisible(element) {
    const style = window.getComputedStyle(element);
    return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
  }

  drawConnection(svg, connection) {
    const parentRect = connection.parent.getBoundingClientRect();
    const childRect = connection.child.getBoundingClientRect();
    const svgRect = svg.getBoundingClientRect();
    
    const x1 = parentRect.left + parentRect.width / 2 - svgRect.left;
    const y1 = parentRect.bottom - svgRect.top;
    const x2 = childRect.left + childRect.width / 2 - svgRect.left;
    const y2 = childRect.top - svgRect.top;
    
    // Create curved line
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    const midY = y1 + (y2 - y1) / 2;
    
    path.setAttribute('d', `M ${x1} ${y1} Q ${x1} ${midY} ${x2} ${y2}`);
    path.setAttribute('stroke', getComputedStyle(document.documentElement).getPropertyValue('--org-line-color'));
    path.setAttribute('stroke-width', '2');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-linecap', 'round');
    
    svg.appendChild(path);
  }

  // ===== Modal Functions =====
  showPersonModal(node) {
    const cardData = JSON.parse(node.getAttribute('data-card') || '{}');
    const modal = document.getElementById('personModal');
    
    if (!modal || !cardData) return;
    
    // Populate modal
    this.populateModal(cardData);
    
    // Show modal
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Focus management
    const firstFocusable = modal.querySelector('button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
      firstFocusable.focus();
    }
  }

  populateModal(data) {
    // Avatar
    const modalAvatar = document.getElementById('modalAvatar');
    if (modalAvatar) {
      if (data.foto) {
        modalAvatar.innerHTML = `<img src="${data.foto}" alt="${data.nome}">`;
      } else {
        const initials = this.generateInitials(data.nome);
        const color = this.generateAvatarColor(data.nome);
        modalAvatar.innerHTML = `<div class="avatar-initials" style="background: ${color}">${initials}</div>`;
      }
    }
    
    // Name and title
    const modalName = document.getElementById('modalName');
    const modalTitle = document.getElementById('modalTitle');
    
    if (modalName) modalName.textContent = data.nome || '—';
    if (modalTitle) modalTitle.textContent = [data.cargo, data.departamento].filter(Boolean).join(' • ');
    
    // Body
    const modalBody = document.getElementById('modalBody');
    if (modalBody) {
      modalBody.innerHTML = `
        <div class="modal-fields">
          ${data.empresa ? `
            <div class="modal-field">
              <label>Empresa</label>
              <span class="company-badge company-${data.empresa.toLowerCase()}">${data.empresa}</span>
            </div>
          ` : ''}
          ${data.ramal ? `
            <div class="modal-field">
              <label>Ramal</label>
              <span>${data.ramal}</span>
            </div>
          ` : ''}
          ${data.telefone ? `
            <div class="modal-field">
              <label>Telefone</label>
              <span>${this.formatPhone(data.telefone)}</span>
            </div>
          ` : ''}
          ${data.email ? `
            <div class="modal-field">
              <label>E-mail</label>
              <span>${data.email}</span>
            </div>
          ` : ''}
          ${data.tipo ? `
            <div class="modal-field">
              <label>Tipo de Contrato</label>
              <span>${data.tipo}</span>
            </div>
          ` : ''}
          ${data.admissao ? `
            <div class="modal-field">
              <label>Data de Admissão</label>
              <span>${this.formatDate(data.admissao)}</span>
            </div>
          ` : ''}
          ${data.obs ? `
            <div class="modal-field">
              <label>Observações</label>
              <span>${data.obs}</span>
            </div>
          ` : ''}
        </div>
      `;
    }
    
    // Actions
    const modalActions = document.getElementById('modalActions');
    if (modalActions) {
      const actions = [];
      
      if (data.email) {
        actions.push(`
          <a href="mailto:${data.email}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" width="16" height="16">
              <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
            </svg>
            Enviar E-mail
          </a>
        `);
      }
      
      if (data.telefone) {
        const waLink = this.generateWhatsAppLink(data.telefone);
        actions.push(`
          <a href="${waLink}" target="_blank" rel="noopener" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" width="16" height="16">
              <path fill="currentColor" d="M16.75 13.96c.51.82.83 1.76.83 2.76 0 2.84-2.34 5.16-5.22 5.16A5.22 5.22 0 0 1 7.14 16.1c.31.04.62.06.94.06 1.11 0 2.15-.38 2.98-1.02-.53-.01-.99-.36-1.14-.83.18.03.37.05.56.05.22 0 .43-.03.63-.08-.56-.11-1-.6-1.09-1.18.19.04.39.07.6.07.21 0 .41-.03.6-.07-.58-.19-1-.77-1-1.46v-.02c.34.19.74.31 1.17.32-.34-.23-.58-.61-.58-1.05 0-.47.25-.88.63-1.1-.58-.72-1.46-1.2-2.45-1.23.72-.46 1.55-.73 2.44-.73 2.84 0 5.16 2.34 5.16 5.22 0 .41-.05.81-.14 1.2zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm3.83 11.86c-.18.51-.53.93-.98 1.19-.18.1-.37.18-.56.24-.11.04-.22.07-.34.09-.2.04-.4.06-.6.06s-.4-.02-.6-.06c-.12-.02-.23-.05-.34-.09-.19-.06-.38-.14-.56-.24-.45-.26-.8-.68-.98-1.19-.1-.3-.15-.62-.15-.94 0-.32.05-.64.15-.94.18-.51.53-.93.98-1.19.18-.1.37-.18.56-.24.11-.04.22-.07.34-.09.2-.04.4-.06.6-.06s.4.02.6.06c.12.02.23.05.34.09.19.06.38.14.56.24.45.26.8.68.98 1.19.1.3.15.62.15.94 0 .32-.05.64-.15.94z"/>
            </svg>
            WhatsApp
          </a>
        `);
      }
      
      if (data.teams) {
        const teamsLink = this.generateTeamsLink(data.teams);
        actions.push(`
          <a href="${teamsLink}" target="_blank" rel="noopener" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" width="16" height="16">
              <path fill="currentColor" d="M13.6 4.8c1.3.3 2.6.7 3.8 1.2.5.2.8.7.8 1.2v5.5c0 .8-.6 1.4-1.4 1.4-.3 0-.5-.1-.7-.2-1.1-.6-2.3-1-3.6-1.3-.8-.2-1.3-.9-1.3-1.8V6c0-.8.6-1.4 1.4-1.4.5 0 .9.2 1.2.6zM9.2 6c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8c-.8 0-1.4-.6-1.4-1.4V6zM2 9.5c0-.8.6-1.4 1.4-1.4h.8c.8 0 1.4.6 1.4 1.4v5.5c0 .8-.6 1.4-1.4 1.4h-.8C2.6 16 2 15.4 2 14.5V9.5z"/>
            </svg>
            Teams
          </a>
        `);
      }
      
      modalActions.innerHTML = actions.join('');
    }
  }

  closeModal() {
    const modal = document.getElementById('personModal');
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }

  // ===== List View Functions =====
  initListView() {
    this.setupTableSorting();
    this.setupListPagination();
  }

  setupTableSorting() {
    const headers = document.querySelectorAll('.modern-table th');
    headers.forEach((header, index) => {
      header.style.cursor = 'pointer';
      header.addEventListener('click', () => {
        this.sortTable(index);
      });
    });
  }

  sortTable(columnIndex) {
    const table = document.querySelector('.modern-table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    // Toggle sort direction
    const currentDir = table.getAttribute('data-sort-dir') || 'asc';
    const newDir = currentDir === 'asc' ? 'desc' : 'asc';
    
    table.setAttribute('data-sort-dir', newDir);
    
    // Sort rows
    rows.sort((a, b) => {
      const aCell = a.cells[columnIndex];
      const bCell = b.cells[columnIndex];
      
      const aText = aCell.textContent.trim();
      const bText = bCell.textContent.trim();
      
      // Numeric comparison
      if (!isNaN(aText) && !isNaN(bText)) {
        return newDir === 'asc' ? aText - bText : bText - aText;
      }
      
      // Text comparison
      return newDir === 'asc' 
        ? aText.localeCompare(bText, 'pt-BR')
        : bText.localeCompare(aText, 'pt-BR');
    });
    
    // Reorder DOM
    rows.forEach(row => tbody.appendChild(row));
    
    // Update header indicators
    headers.forEach((header, index) => {
      header.classList.toggle('sort-asc', index === columnIndex && newDir === 'asc');
      header.classList.toggle('sort-desc', index === columnIndex && newDir === 'desc');
    });
  }

  setupListPagination() {
    // Already handled by event listeners
  }

  // ===== Cards View Functions =====
  initCardsView() {
    this.setupCardInteractions();
  }

  setupCardInteractions() {
    const cards = document.querySelectorAll('.person-card');
    cards.forEach(card => {
      card.addEventListener('click', () => {
        const cardData = JSON.parse(card.getAttribute('data-card') || '{}');
        if (cardData && cardData.id) {
          this.showPersonCardModal(cardData);
        }
      });
    });
  }

  showPersonCardModal(cardData) {
    // Similar to showPersonModal but for cards
    this.populateModal(cardData);
    const modal = document.getElementById('personModal');
    if (modal) {
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  // ===== Settings Panel =====
  toggleSettingsPanel() {
    const panel = document.getElementById('settingsPanel');
    if (panel) {
      panel.classList.toggle('active');
      
      if (panel.classList.contains('active')) {
        // Focus first input
        const firstInput = panel.querySelector('input, select');
        if (firstInput) {
          firstInput.focus();
        }
      }
    }
  }

  // ===== Company Filters =====
  toggleAllCompanies(checked) {
    const companyCheckboxes = document.querySelectorAll('input[name="empresas[]"]');
    companyCheckboxes.forEach(checkbox => {
      checkbox.checked = checked;
    });
    
    this.updateCompanyFilters();
  }

  updateCompanyFilters() {
    const companyCheckboxes = document.querySelectorAll('input[name="empresas[]"]:checked');
    const selectedCompanies = Array.from(companyCheckboxes).map(cb => cb.value);
    
    this.params.empresas = selectedCompanies.length > 0 ? selectedCompanies : ['todos'];
    
    // Update "select all" checkbox
    const selectAllCheckbox = document.getElementById('selectAllEmpresas');
    if (selectAllCheckbox) {
      selectAllCheckbox.checked = companyCheckboxes.length === document.querySelectorAll('input[name="empresas[]"]').length;
    }
    
    this.updateURL();
    this.reloadData();
  }

  // ===== Export Functionality =====
  exportData() {
    const format = prompt('Escolha o formato de exportação:\n1 - Excel (.xlsx)\n2 - CSV\n3 - JSON\n4 - Imprimir', '1');
    
    switch(format) {
      case '1':
        this.exportToExcel();
        break;
      case '2':
        this.exportToCSV();
        break;
      case '3':
        this.exportToJSON();
        break;
      case '4':
        this.printData();
        break;
      default:
        alert('Formato inválido');
    }
  }

  exportToExcel() {
    // Redirect to existing export endpoint
    const url = new URL('../organograma/export_xlsx.php', window.location.href);
    url.search = new URLSearchParams(this.params).toString();
    window.open(url.toString(), '_blank');
  }

  exportToCSV() {
    this.showLoading();
    
    fetch('../organograma/api/export.php?format=csv&' + new URLSearchParams(this.params))
      .then(response => response.blob())
      .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'organograma-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      })
      .catch(error => {
        console.error('Erro ao exportar:', error);
        alert('Erro ao exportar dados');
      })
      .finally(() => {
        this.hideLoading();
      });
  }

  exportToJSON() {
    this.showLoading();
    
    fetch('../organograma/api/export.php?format=json&' + new URLSearchParams(this.params))
      .then(response => response.json())
      .then(data => {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'organograma-' + new Date().toISOString().slice(0, 10) + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
      })
      .catch(error => {
        console.error('Erro ao exportar:', error);
        alert('Erro ao exportar dados');
      })
      .finally(() => {
        this.hideLoading();
      });
  }

  printData() {
    window.print();
  }

  // ===== Pagination =====
  goToPage(page) {
    this.params.page = page;
    this.updateURL();
    this.reloadData();
  }

  // ===== URL Management =====
  updateURL() {
    const url = new URL(window.location.href);
    
    // Update search params
    Object.keys(this.params).forEach(key => {
      if (this.params[key] === null || this.params[key] === undefined) {
        url.searchParams.delete(key);
      } else if (Array.isArray(this.params[key])) {
        url.searchParams.delete(key);
        this.params[key].forEach(value => {
          url.searchParams.append(key + '[]', value);
        });
      } else {
        url.searchParams.set(key, this.params[key]);
      }
    });
    
    // Use history API to avoid page reload
    window.history.replaceState({}, '', url.toString());
  }

  // ===== Data Reloading =====
  reloadData() {
    this.showLoading();
    
    // Store scroll position
    const scrollPositions = {};
    document.querySelectorAll('[data-scroll-key]').forEach(el => {
      scrollPositions[el.getAttribute('data-scroll-key')] = {
        top: el.scrollTop,
        left: el.scrollLeft
      };
    });
    
    // Reload page with new parameters
    window.location.reload();
  }

  // ===== Loading States =====
  showLoading() {
    this.isLoading = true;
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
      loadingOverlay.classList.add('active');
    }
  }

  hideLoading() {
    this.isLoading = false;
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (loadingOverlay) {
      loadingOverlay.classList.remove('active');
    }
  }

  // ===== Keyboard Shortcuts =====
  setupKeyboardShortcuts() {
    document.addEventListener('keydown', (e) => {
      // Ignore if typing in input
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        return;
      }
      
      switch(e.key) {
        case '/':
          e.preventDefault();
          const searchInput = document.getElementById('searchInput');
          if (searchInput) {
            searchInput.focus();
          }
          break;
        case 'Escape':
          this.closeModal();
          break;
        case '1':
          e.preventDefault();
          this.setViewMode('org');
          break;
        case '2':
          e.preventDefault();
          this.setViewMode('lista');
          break;
        case '3':
          e.preventDefault();
          this.setViewMode('cards');
          break;
        case 't':
          e.preventDefault();
          this.toggleTheme();
          break;
        case 's':
          e.preventDefault();
          this.toggleSettingsPanel();
          break;
        case 'p':
          e.preventDefault();
          this.printData();
          break;
      }
    });
  }

  // ===== Accessibility =====
  setupAccessibility() {
    // Add ARIA labels
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.setAttribute('aria-label', 'Buscar colaboradores');
      searchInput.setAttribute('role', 'searchbox');
    }
    
    // Add keyboard navigation to cards and nodes
    const interactiveElements = document.querySelectorAll('.person-node, .person-card');
    interactiveElements.forEach(element => {
      element.setAttribute('tabindex', '0');
      element.setAttribute('role', 'button');
      
      element.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          element.click();
        }
      });
    });
    
    // Announce changes to screen readers
    this.setupLiveRegion();
  }

  setupLiveRegion() {
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.style.position = 'absolute';
    liveRegion.style.left = '-10000px';
    liveRegion.style.width = '1px';
    liveRegion.style.height = '1px';
    liveRegion.style.overflow = 'hidden';
    document.body.appendChild(liveRegion);
    
    this.liveRegion = liveRegion;
  }

  announceToScreenReader(message) {
    if (this.liveRegion) {
      this.liveRegion.textContent = message;
      setTimeout(() => {
        this.liveRegion.textContent = '';
      }, 1000);
    }
  }

  // ===== Utility Functions =====
  generateInitials(name) {
    if (!name) return '??';
    const parts = name.trim().split(/\s+/);
    const first = parts[0].charAt(0).toUpperCase();
    const last = parts.length > 1 ? parts[parts.length - 1].charAt(0).toUpperCase() : '';
    return first + (last !== first ? last : '');
  }

  generateAvatarColor(name) {
    if (!name) return '#6b7280';
    
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    
    const hue = Math.abs(hash % 360);
    return `hsl(${hue}, 55%, 68%)`;
  }

  formatPhone(phone) {
    if (!phone) return '';
    const clean = phone.toString().replace(/\D/g, '');
    if (clean.length === 11) {
      return `(${clean.slice(0, 2)}) ${clean.slice(2, 7)}-${clean.slice(7)}`;
    }
    return phone;
  }

  generateWhatsAppLink(phone) {
    if (!phone) return '';
    const clean = phone.toString().replace(/\D/g, '');
    if (!clean) return '';
    const withCountry = clean.startsWith('55') ? clean : '55' + clean;
    return 'https://wa.me/' + withCountry;
  }

  generateTeamsLink(teams) {
    if (!teams) return '';
    const clean = teams.toString().trim();
    if (!clean) return '';
    if (/^https?:/i.test(clean)) return clean;
    return 'https://teams.live.com/l/invite/' + encodeURIComponent(clean);
  }

  formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
  }

  // ===== Performance Optimization =====
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  throttle(func, limit) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    }
  }
}

// ===== Initialize on DOM Content Loaded =====
document.addEventListener('DOMContentLoaded', () => {
  // Load saved preferences
  const savedTheme = localStorage.getItem('organograma-theme');
  const savedAnimations = localStorage.getItem('organograma-animations');
  
  if (savedTheme) {
    document.documentElement.setAttribute('data-theme', savedTheme);
  }
  
  if (savedAnimations === 'false') {
    document.documentElement.style.setProperty('--animation-duration', '0ms');
    const animationsToggle = document.getElementById('animationsToggle');
    if (animationsToggle) {
      animationsToggle.checked = false;
    }
  }
  
  // Initialize tooltips if any
  const tooltipElements = document.querySelectorAll('[title]');
  tooltipElements.forEach(element => {
    element.addEventListener('mouseenter', (e) => {
      const title = e.target.getAttribute('title');
      if (title) {
        // Simple tooltip implementation
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = title;
        tooltip.style.cssText = `
          position: absolute;
          background: rgba(0,0,0,0.8);
          color: white;
          padding: 4px 8px;
          border-radius: 4px;
          font-size: 12px;
          z-index: 1000;
          pointer-events: none;
          white-space: nowrap;
        `;
        
        document.body.appendChild(tooltip);
        
        const rect = e.target.getBoundingClientRect();
        tooltip.style.left = rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + 'px';
        tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
        
        e.target.tooltip = tooltip;
      }
    });
    
    element.addEventListener('mouseleave', (e) => {
      if (e.target.tooltip) {
        document.body.removeChild(e.target.tooltip);
        e.target.tooltip = null;
      }
    });
  });
});

// ===== Service Worker Registration for PWA =====
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('sw.js')
      .then(registration => {
        console.log('SW registered: ', registration);
      })
      .catch(registrationError => {
        console.log('SW registration failed: ', registrationError);
      });
  });
}
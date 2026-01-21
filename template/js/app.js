/*****************************************************************
 *  Sidebar  – recolher / expandir  (persiste em localStorage)
 *****************************************************************/
(() => {
  const BODY          = document.body;
  const KEY           = 'sidebarCollapsed';           // '1' = recolhida
  const desktopTglBtn = document.getElementById('sidebarToggle'); // ícone hambúrguer
  const mobileMenu    = document.getElementById('sidebar-menu');  // div.collapse
  const MOBILE_LINKS  = mobileMenu ? mobileMenu.querySelectorAll('.nav-link') : [];

  /* — restaura estado salvo ao carregar a página — */
  if (localStorage.getItem(KEY) === '1') BODY.classList.add('sidebar-collapsed');

  /* — clique no ícone desktop — */
  desktopTglBtn?.addEventListener('click', e => {
    e.preventDefault();
    BODY.classList.toggle('sidebar-collapsed');
    localStorage.setItem(KEY, BODY.classList.contains('sidebar-collapsed') ? '1' : '0');
  });

  /* — fecha o off-canvas no mobile quando o usuário toca num link — */
  MOBILE_LINKS.forEach(a => {
    a.addEventListener('click', () => {
      if (window.innerWidth < 992 && mobileMenu.classList.contains('show')) {
        bootstrap.Collapse.getOrCreateInstance(mobileMenu).hide();
      }
    });
  });
})();

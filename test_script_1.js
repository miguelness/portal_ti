
        const treinamentosData = <?= json_encode($treinamentos) ?>;
        const anexosData = <?= json_encode($anexosTr) ?>;
        
        function renderTreinamento(id) {
            const tr = treinamentosData.find(t => t.id == id);
            if(!tr) return;
            
            const anexos = anexosData[id] || [];
            let anexosHtml = '';
            if(anexos.length > 0) {
                anexosHtml = `<h4 class="mb-3" style="font-weight: 600; color: var(--text-primary);"><i class="ti ti-paperclip me-1"></i> Documentos em Anexo</h4><div class="d-flex flex-wrap gap-2">`;
                anexos.forEach(a => {
                    anexosHtml += `<a href="uploads_treinamentos/${a.caminho_arquivo}" target="_blank" class="btn btn-sm" style="background:var(--bg-body); border:1px solid var(--border-color); color:var(--text-primary); text-decoration:none; padding:10px 16px; border-radius:8px; display:inline-flex; align-items:center; transition: all 0.2s;" onmouseover="this.style.background='var(--border-color)'" onmouseout="this.style.background='var(--bg-body)'"><i class="ti ti-file-download me-2" style="font-size:1.2rem; color:#d63384;"></i> <span style="font-weight:500;">${a.nome_documento}</span></a>`;
                });
                anexosHtml += `</div>`;
            } else {
                anexosHtml = `<div class="text-muted"><i class="ti ti-info-circle me-1"></i> Este treinamento não possui documentos em anexo.</div>`;
            }
            
            let playlistHtml = '';
            treinamentosData.forEach(t => {
                const isActive = t.id == id ? 'active' : '';
                playlistHtml += `
                    <div class="treinamento-item ${isActive}" onclick="renderTreinamento(${t.id})">
                        <div class="treinamento-item-icon"><i class="ti ti-player-play-filled"></i></div>
                        <div>
                            <div class="treinamento-item-title">${t.titulo}</div>
                            <div class="treinamento-item-desc">${t.descricao ? t.descricao.substring(0, 80) + '...' : ''}</div>
                        </div>
                    </div>
                `;
            });
            
            // Corrige se a url já for embed
            let finalUrl = tr.url_video;
            if (finalUrl.includes('youtube.com/watch?v=')) {
                finalUrl = finalUrl.replace('watch?v=', 'embed/');
            }
            
            const html = `
                <div class="treinamento-player-col">
                    <div class="treinamento-video-container">
                        <iframe src="${finalUrl}" allowfullscreen allow="autoplay; encrypted-media"></iframe>
                    </div>
                    <div class="treinamento-anexos">
                        ${anexosHtml}
                    </div>
                </div>
                <div class="treinamento-playlist-col">
                    <div class="treinamento-playlist-header"><i class="ti ti-list me-1"></i> Conteúdos (${treinamentosData.length})</div>
                    <div class="treinamento-playlist-items">
                        ${playlistHtml}
                    </div>
                </div>
            `;
            document.getElementById('treinamentoRenderer').innerHTML = html;
        }
        
        // Initialize with first video when modal opens
        const treinCard = document.querySelector('.card[onclick="openModal(\\'modalTreinamentos\\')"]');
        if(treinCard) {
            treinCard.addEventListener('click', () => {
                if(treinamentosData && treinamentosData.length > 0) {
                    renderTreinamento(treinamentosData[0].id);
                } else {
                    document.getElementById('treinamentoRenderer').innerHTML = '<div class="p-5 w-100 text-center text-muted"><i class="ti ti-video-off fs-1 mb-3"></i><br><h4>Ainda não há treinamentos cadastrados.</h4></div>';
                }
            });
        }
    <?php endif; ?>


        // Modal Handlers
        function openModal(id) {
            const overlay = document.getElementById(id);
            if(overlay) {
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeModal(id) {
            const overlay = document.getElementById(id);
            if(overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Image Preview Handler
        function openImageModal(src) {
            const img = document.getElementById('previewImage');
            if(img) {
                img.src = src;
                openModal('imagePreviewModal');
            }
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                // If click is directly on the overlay background, close it
                if (e.target === this) {
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    activeModal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            }
        });

        // Theme Toggle Logic
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme') || 'light';
        body.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', function() {
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            body.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'ti ti-moon';
            } else {
                themeIcon.className = 'ti ti-sun';
            }
        }
    
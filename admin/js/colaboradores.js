/**
 * JavaScript para gerenciamento de colaboradores
 * Grupo Barão - Portal TI
 */

$(document).ready(function() {
    let table;
    let isEditing = false;
    let currentId = null;

    // Inicializar DataTable
    initializeDataTable();
    
    // Carregar estatísticas
    loadStatistics();
    
    // Carregar dados para selects
    loadSelectData();

    /**
     * Inicializar DataTable
     */
    function initializeDataTable() {
        table = $('#colaboradores-table').DataTable({
            ajax: {
                url: '../api/colaboradores.php',
                type: 'GET',
                dataSrc: 'data'
            },
            columns: [
                {
                    data: null,
                    render: function(data, type, row) {
                        const avatar = generateAvatar(row.nome);
                        return `
                            <div class="d-flex align-items-center">
                                <div class="colaborador-avatar" style="background-color: ${avatar.color}">
                                    ${avatar.initials}
                                </div>
                                <div>
                                    <div class="fw-bold">${row.nome}</div>
                                    <div class="text-muted small">${row.empresa}</div>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'ramal',
                    render: function(data) {
                        return data || '-';
                    }
                },
                { data: 'empresa' },
                { data: 'setor' },
                { 
                    data: 'email',
                    render: function(data) {
                        return data ? `<a href="mailto:${data}">${data}</a>` : '-';
                    }
                },
                {
                    data: 'status',
                    render: function(data) {
                        const statusClass = data === 'ativo' ? 'bg-green' : 'bg-red';
                        const statusText = data === 'ativo' ? 'Ativo' : 'Inativo';
                        return `<span class="badge ${statusClass} status-badge">${statusText}</span>`;
                    }
                },
                {
                    data: 'updated_at',
                    render: function(data) {
                        return new Date(data).toLocaleDateString('pt-BR');
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewColaborador(${row.id})" title="Visualizar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="editColaborador(${row.id})" title="Editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/><path d="M16 5l3 3"/></svg>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteColaborador(${row.id}, '${row.nome}')" title="Excluir">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="m0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[0, 'asc']]
        });
    }

    /**
     * Carregar estatísticas
     */
    function loadStatistics() {
        $.get('../api/colaboradores.php', function(response) {
            const total = response.total || 0;
            const ativos = response.data.filter(item => item.status === 'ativo').length;
            const inativos = total - ativos;
            
            const statsHtml = `
                <div class="col-sm-6 col-lg-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Total de Colaboradores</div>
                            </div>
                            <div class="h1 mb-3">${total}</div>
                            <div class="d-flex mb-2">
                                <div>Registros no sistema</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Colaboradores Ativos</div>
                            </div>
                            <div class="h1 mb-3 text-green">${ativos}</div>
                            <div class="d-flex mb-2">
                                <div>Em atividade</div>
                                <div class="ms-auto">
                                    <span class="text-green">${total > 0 ? Math.round((ativos/total)*100) : 0}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Colaboradores Inativos</div>
                            </div>
                            <div class="h1 mb-3 text-red">${inativos}</div>
                            <div class="d-flex mb-2">
                                <div>Desligados</div>
                                <div class="ms-auto">
                                    <span class="text-red">${total > 0 ? Math.round((inativos/total)*100) : 0}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Última Atualização</div>
                            </div>
                            <div class="h1 mb-3">${new Date().toLocaleDateString('pt-BR')}</div>
                            <div class="d-flex mb-2">
                                <div>Dados atualizados</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#stats-container').html(statsHtml);
        });
    }

    /**
     * Carregar dados para selects
     */
    function loadSelectData() {
        // Carregar empresas
        $.get('../api/auxiliares.php?tipo=empresas', function(data) {
            const select = $('#empresa');
            select.empty().append('<option value="">Selecione...</option>');
            if (data.data) {
                data.data.forEach(function(item) {
                    select.append(`<option value="${item.empresa}">${item.empresa}</option>`);
                });
            }
        });

        // Carregar setores
        $.get('../api/auxiliares.php?tipo=setores', function(data) {
            const select = $('#setor');
            select.empty().append('<option value="">Selecione...</option>');
            if (data.data) {
                data.data.forEach(function(item) {
                    select.append(`<option value="${item.setor}">${item.setor}</option>`);
                });
            }
        });
    }

    /**
     * Gerar avatar com iniciais
     */
    function generateAvatar(nome) {
        const names = nome.split(' ');
        const initials = names.length > 1 ? 
            names[0].charAt(0) + names[names.length - 1].charAt(0) : 
            names[0].charAt(0) + (names[0].charAt(1) || '');
        
        const colors = ['#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd', '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf'];
        const color = colors[nome.length % colors.length];
        
        return {
            initials: initials.toUpperCase(),
            color: color
        };
    }

    /**
     * Resetar formulário
     */
    function resetForm() {
        $('#form-colaborador')[0].reset();
        $('#colaborador-id').val('');
        $('#modal-title').text('Novo Colaborador');
        isEditing = false;
        currentId = null;
        
        // Limpar contatos adicionais
        $('#contatos-adicionais').empty();
        
        // Remover classes de validação
        $('.form-control').removeClass('is-invalid is-valid');
        $('.invalid-feedback').remove();
    }

    /**
     * Validar formulário
     */
    function validateForm() {
        let isValid = true;
        const requiredFields = ['nome', 'empresa', 'setor', 'email'];
        
        // Limpar validações anteriores
        $('.form-control').removeClass('is-invalid is-valid');
        $('.invalid-feedback').remove();
        
        requiredFields.forEach(function(field) {
            const input = $(`#${field}`);
            const value = input.val().trim();
            
            if (!value) {
                input.addClass('is-invalid');
                input.after('<div class="invalid-feedback">Este campo é obrigatório.</div>');
                isValid = false;
            } else {
                input.addClass('is-valid');
            }
        });
        
        // Validar email
        const email = $('#email').val().trim();
        if (email && !isValidEmail(email)) {
            $('#email').addClass('is-invalid').removeClass('is-valid');
            $('#email').after('<div class="invalid-feedback">Por favor, insira um e-mail válido.</div>');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Validar email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Mostrar feedback
     */
    function showFeedback(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const icon = type === 'success' ? 'check-circle' : 'x-circle';
        
        const alert = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="m0 0h24v24H0z" fill="none"/>
                    ${type === 'success' ? '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M9 12l2 2l4 -4"/>' : '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M12 9v4"/><path d="M12 16h.01"/>'}
                </svg>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        $('.page-body .container-xl').prepend(alert);
        
        // Auto-remover após 5 segundos
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Event Listeners

    /**
     * Abrir modal para novo colaborador
     */
    $('[data-bs-target="#modal-colaborador"]').click(function() {
        resetForm();
    });

    /**
     * Submeter formulário
     */
    $('#form-colaborador').submit(function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        const formData = new FormData(this);
        const data = {};
        
        // Converter FormData para objeto
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        const url = '../api/colaboradores.php';
        const method = isEditing ? 'PUT' : 'POST';
        
        // Mostrar loading
        $('#loading-spinner').removeClass('d-none');
        $('button[type="submit"]').prop('disabled', true);
        
        console.log('Enviando dados:', data);
        console.log('URL:', url);
        console.log('Método:', method);
        
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            success: function(response) {
                console.log('Sucesso:', response);
                $('#modal-colaborador').modal('hide');
                showFeedback('success', isEditing ? 'Colaborador atualizado com sucesso!' : 'Colaborador cadastrado com sucesso!');
                table.ajax.reload();
                loadStatistics();
            },
            error: function(xhr) {
                console.log('Erro completo:', xhr);
                console.log('Status:', xhr.status);
                console.log('Response Text:', xhr.responseText);
                
                const response = xhr.responseJSON;
                const message = response && response.error ? response.error : 'Erro ao salvar colaborador.';
                showFeedback('error', message);
            },
            complete: function() {
                $('#loading-spinner').addClass('d-none');
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });

    /**
     * Filtros
     */
    $('[data-filter]').click(function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        
        if (filter === 'todos') {
            table.column(5).search('').draw();
        } else {
            table.column(5).search(filter).draw();
        }
    });

    /**
     * Limpar filtros
     */
    $('#btn-limpar-filtros').click(function(e) {
        e.preventDefault();
        table.search('').columns().search('').draw();
    });

    /**
     * Atualizar tabela
     */
    $('#btn-refresh').click(function(e) {
        e.preventDefault();
        table.ajax.reload();
        loadStatistics();
        showFeedback('success', 'Dados atualizados!');
    });

    /**
     * Estatísticas
     */
    $('#btn-estatisticas').click(function(e) {
        e.preventDefault();
        loadStatistics();
        showFeedback('success', 'Estatísticas atualizadas!');
    });

    // Funções globais para os botões da tabela
    window.viewColaborador = function(id) {
        $.get(`../api/colaboradores.php?id=${id}`, function(response) {
            const data = response.data;
            
            Swal.fire({
                title: data.nome,
                html: `
                    <div class="text-start">
                        <p><strong>Ramal:</strong> ${data.ramal || '-'}</p>
                        <p><strong>Empresa:</strong> ${data.empresa}</p>
                        <p><strong>Setor:</strong> ${data.setor}</p>
                        <p><strong>E-mail:</strong> ${data.email || '-'}</p>
                        <p><strong>Telefone:</strong> ${data.telefone || '-'}</p>
                        <p><strong>Teams:</strong> ${data.teams || '-'}</p>
                        <p><strong>Status:</strong> <span class="badge ${data.status === 'ativo' ? 'bg-green' : 'bg-red'}">${data.status === 'ativo' ? 'Ativo' : 'Inativo'}</span></p>
                        ${data.observacoes ? `<p><strong>Observações:</strong> ${data.observacoes}</p>` : ''}
                        <hr>
                        <small class="text-muted">
                            Criado em: ${new Date(data.created_at).toLocaleString('pt-BR')}<br>
                            Última atualização: ${new Date(data.updated_at).toLocaleString('pt-BR')}
                        </small>
                    </div>
                `,
                width: 600,
                showCloseButton: true,
                showConfirmButton: false
            });
        }).fail(function() {
            Swal.fire('Erro', 'Erro ao carregar dados do colaborador.', 'error');
        });
    };

    window.editColaborador = function(id) {
        $.get(`../api/colaboradores.php?id=${id}`, function(response) {
            const data = response.data;
            
            // Preencher formulário
            $('#colaborador-id').val(data.id);
            $('#ramal').val(data.ramal);
            $('#nome').val(data.nome);
            $('#empresa').val(data.empresa);
            $('#setor').val(data.setor);
            $('#email').val(data.email);
            $('#telefone').val(data.telefone);
            $('#teams').val(data.teams);
            $('#status').val(data.status);
            $('#observacoes').val(data.observacoes);
            
            // Configurar modal
            $('#modal-title').text('Editar Colaborador');
            isEditing = true;
            currentId = id;
            
            // Abrir modal
            $('#modal-colaborador').modal('show');
            
        }).fail(function() {
            Swal.fire('Erro', 'Erro ao carregar dados do colaborador.', 'error');
        });
    };

    window.deleteColaborador = function(id, nome) {
        Swal.fire({
            title: 'Confirmar Exclusão',
            html: `Tem certeza que deseja excluir o colaborador <strong>${nome}</strong>?<br><br><small class="text-muted">Esta ação não pode ser desfeita.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `../api/colaboradores.php?id=${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        Swal.fire('Excluído!', 'Colaborador excluído com sucesso.', 'success');
                        table.ajax.reload();
                        loadStatistics();
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        const message = response && response.error ? response.error : 'Erro ao excluir colaborador.';
                        Swal.fire('Erro', message, 'error');
                    }
                });
            }
        });
    };
});
function gerarAvatar(nome) {
    if (!nome) return "";
    const initials = nome.split(" ").map(n => n[0]).join("").substring(0, 2).toUpperCase();
    const colors = ["#206bc4", "#ae3ec9", "#d63384", "#fd7e14", "#fab005", "#40c057", "#17a2b8"];
    const color = colors[nome.length % colors.length];
    return "<span class=\"colaborador-avatar\" style=\"background-color: " + color + ";\">" + initials + "</span>";
}

function editarColaborador(id) {
    fetch("colaborador_get.php?id=" + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const colaborador = data.colaborador;
                document.getElementById("edit_id").value = colaborador.id;
                document.getElementById("edit_ramal").value = colaborador.ramal || "";
                document.getElementById("edit_nome").value = colaborador.nome || "";
                document.getElementById("edit_empresa").value = colaborador.empresa || "";
                document.getElementById("edit_setor").value = colaborador.setor || "";
                document.getElementById("edit_email").value = colaborador.email || "";
                document.getElementById("edit_telefone").value = colaborador.telefone || "";
                document.getElementById("edit_teams").value = colaborador.teams || "";
                document.getElementById("edit_status").value = colaborador.status || "ativo";
                document.getElementById("edit_observacoes").value = colaborador.observacoes || "";
                
                const modalElement = document.getElementById("editModal");
                const existingModal = bootstrap.Modal.getInstance(modalElement);
                if (existingModal) {
                    existingModal.dispose();
                }
                
                document.querySelectorAll(".modal-backdrop").forEach(backdrop => {
                    backdrop.remove();
                });
                
                const modal = new bootstrap.Modal(modalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                modal.show();
            } else {
                alert("Erro ao carregar dados do colaborador: " + (data.message || "Erro desconhecido"));
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            alert("Erro ao carregar dados do colaborador");
        });
}

function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === "ativo" ? "inativo" : "ativo";
    const confirmMessage = "Tem certeza que deseja " + (newStatus === "ativo" ? "ativar" : "desativar") + " este colaborador?";
    
    if (confirm(confirmMessage)) {
        const form = document.createElement("form");
        form.method = "POST";
        form.style.display = "none";
        
        const actionInput = document.createElement("input");
        actionInput.type = "hidden";
        actionInput.name = "action";
        actionInput.value = "toggle_status";
        
        const idInput = document.createElement("input");
        idInput.type = "hidden";
        idInput.name = "id";
        idInput.value = id;
        
        const statusInput = document.createElement("input");
        statusInput.type = "hidden";
        statusInput.name = "status";
        statusInput.value = newStatus;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".btn-delete").forEach(btn => {
        btn.addEventListener("click", function(e) {
            if (!confirm("Tem certeza que deseja excluir este colaborador?")) {
                e.preventDefault();
            }
        });
    });
    
    const clearFiltersBtn = document.getElementById("clearFilters");
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener("click", function() {
            document.getElementById("filterEmpresa").value = "";
            document.getElementById("filterSetor").value = "";
            document.getElementById("filterStatus").value = "";
            document.getElementById("searchInput").value = "";
            window.location.href = window.location.pathname;
        });
    }
    
    document.querySelectorAll(".colaborador-nome").forEach(element => {
        const nome = element.textContent.trim();
        const avatarContainer = element.closest("tr").querySelector(".colaborador-avatar-container");
        if (avatarContainer && nome) {
            avatarContainer.innerHTML = gerarAvatar(nome);
        }
    });
});
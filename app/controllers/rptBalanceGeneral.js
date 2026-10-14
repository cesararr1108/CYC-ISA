const cargarCuentas = async () => {
    try {
        const resp = await enviarPeticion({
            link: '../models/rptBalanceGeneral.php',
            op: 'cuentas'
        });

        if (resp.success) {
            let options = '<option value="">Todas</option>';
            resp.data.forEach(item => {
                options += `<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion}</option>`;
            });
            $("#cu_inicial").html(options).select2({
                placeholder: 'Seleccione cuenta inicial',
                allowClear: true,
                width: '100%'
            });
            $("#cu_final").html(options).select2({
                placeholder: 'Seleccione cuenta final',
                allowClear: true,
                width: '100%'
            });
        }
    } catch (e) {
        console.error(e);
    }
};

const consultarBalance = async () => {
    showLoadingWithTetrisLogo('Cargando balance general', 'ISA Software');
    try {
        const data = {
            link: '../models/rptBalanceGeneral.php',
            op: 'consultar',
            fecha_inicial: $("#fecha_inicial").val(),
            fecha_final: $("#fecha_final").val(),
            cu_inicial: $("#cu_inicial").val() || '',
            cu_final: $("#cu_final").val() || ''
        };

        const resp = await enviarPeticion(data);
        dissminSwal();

        if (!resp.success) {
            Swal.fire("Error", resp.message, "error");
            return;
        }

        if (resp.data.length === 0) {
            $("#contenedor-balance").html(`<div class="alert alert-info"><i class="fas fa-info-circle"></i> No se encontraron datos.</div>`);
            return;
        }

        mostrarBalanceJerarquico(resp.data);

    } catch (e) {
        console.error(e);
        dissminSwal();
        Swal.fire("Error", "Error interno de la aplicación", "error");
    }
};

const mostrarBalanceJerarquico = (data) => {
    // Agrupar datos jerárquicamente
    const estructura = {};
    let totalesPorTipo = {};

    data.forEach(row => {
        if (!estructura[row.tipo]) {
            estructura[row.tipo] = {};
            totalesPorTipo[row.tipo] = 0;
        }
        if (!estructura[row.tipo][row.grupo]) {
            estructura[row.tipo][row.grupo] = [];
        }
        estructura[row.tipo][row.grupo].push(row);
        totalesPorTipo[row.tipo] += parseFloat(row.saldo) || 0;
    });

    // Generar HTML
    let html = `<table class="table table-sm table-hover border-bottom">
        <thead class="table-light">
            <tr>
                <th style="width: 50%;">Descripción</th>
                <th style="width: 15%;">Cuenta</th>
                <th style="width: 35%;" class="text-end">Saldo</th>
            </tr>
        </thead>
        <tbody>`;

    Object.keys(estructura).forEach(tipo => {
        const gruposDelTipo = estructura[tipo];
        const totalTipo = totalesPorTipo[tipo];
        const tipoId = tipo.replace(/\s+/g, '_');

        // Fila del Tipo (nivel 1 - expandible)
        html += `
            <tr class="nivel-tipo" data-tipo="${tipoId}" style="background-color: #f8f9fa; font-weight: bold; cursor: pointer;">
                <td>
                    <i class="fas fa-chevron-right" style="cursor: pointer; margin-right: 10px;"></i>
                    ${tipo}
                </td>
                <td></td>
                <td class="text-end text-primary" style="font-weight: bold;">
                    ${formatNum(totalTipo, '$')}
                </td>
            </tr>`;

        // Grupos (nivel 2)
        Object.keys(gruposDelTipo).forEach(grupo => {
            const cuentasDelGrupo = gruposDelTipo[grupo];
            const totalGrupo = cuentasDelGrupo.reduce((sum, row) => sum + (parseFloat(row.saldo) || 0), 0);
            const grupoId = tipo.replace(/\s+/g, '_') + '_' + grupo.replace(/\s+/g, '_');

            html += `
                <tr class="nivel-grupo grupo-${tipoId}" style="display: none; background-color: #ffffff; cursor: pointer;">
                    <td style="padding-left: 50px;">
                        <i class="fas fa-chevron-right" style="cursor: pointer; margin-right: 10px;"></i>
                        ${grupo}
                    </td>
                    <td></td>
                    <td class="text-end text-info">
                        ${formatNum(totalGrupo, '$')}
                    </td>
                </tr>`;

            // Cuentas (nivel 3)
            cuentasDelGrupo.forEach(cuenta => {
                html += `
                    <tr class="nivel-cuenta cuenta-${grupoId}" style="display: none; background-color: #fafbfc; border-bottom: 1px solid #e3e6f0;">
                        <td style="padding-left: 80px;">
                            ${cuenta.descripcion}
                        </td>
                        <td style="padding-left: 20px; font-family: monospace; font-size: 0.9rem;">
                            ${cuenta.cuenta}
                        </td>
                        <td class="text-end">
                            ${formatNum(cuenta.saldo, '$')}
                        </td>
                    </tr>`;
            });
        });
    });

    html += `
        </tbody>
    </table>`;

    $("#contenedor-balance").html(html);

    // Eventos para expandir/contraer
    $(".nivel-tipo").on("click", function () {
        const tipoId = $(this).data("tipo");
        const grupos = $(".grupo-" + tipoId);
        const icono = $(this).find(".fa-chevron-right");

        if (grupos.first().is(":visible")) {
            grupos.slideUp();
            $("[class*='cuenta-" + tipoId + "_']").slideUp();
            icono.removeClass("fa-chevron-down").addClass("fa-chevron-right");
        } else {
            grupos.slideDown();
            icono.removeClass("fa-chevron-right").addClass("fa-chevron-down");
        }
    });

    $(".nivel-grupo").on("click", function (e) {
        e.stopPropagation();
        const clases = $(this).attr("class");
        const match = clases.match(/grupo-([^\s]+)/);
        
        if (!match) return;
        
        const grupoId = match[1];
        const allCuentas = $("[class*='cuenta-" + grupoId + "_']");
        const icono = $(this).find(".fa-chevron-right");

        if (allCuentas.first().is(":visible")) {
            allCuentas.slideUp();
            icono.removeClass("fa-chevron-down").addClass("fa-chevron-right");
        } else {
            allCuentas.slideDown();
            icono.removeClass("fa-chevron-right").addClass("fa-chevron-down");
        }
    });
};

const limpiar = () => {
    $("#contenedor-balance").html("");
    $("#fecha_inicial").val("");
    $("#fecha_final").val("");
    $("#cu_inicial").val("").trigger("change");
    $("#cu_final").val("").trigger("change");
};

$(function () {
    // Establecer fechas por defecto
    const hoy = new Date();
    const hace1Mes = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
    
    const formatoFecha = (fecha) => fecha.toISOString().split('T')[0].substring(0, 7);
    
    $("#fecha_inicial").val(formatoFecha(hace1Mes));
    $("#fecha_final").val(formatoFecha(hoy));

    cargarCuentas();

    $("#btn-busqueda").click(function () {
        const fechaIni = $("#fecha_inicial").val();
        const fechaFin = $("#fecha_final").val();

        if (!fechaIni || !fechaFin) {
            Swal.fire("Advertencia", "Debe seleccionar ambas fechas", "warning");
            return;
        }

        consultarBalance();
    });

    $("#btn-limpiar").click(function () {
        limpiar();
    });

    dissminSwal();
});

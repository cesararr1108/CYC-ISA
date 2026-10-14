/**
 * ============================================
 * Controlador: Control de Temperatura
 * Descripción: Manejo AJAX y eventos de temperatura/humedad
 * ============================================
 */

const link_ = '../models/controlTemperatura.php';
let registrosData = [];
let registroActual = {
    id: 0,
    fecha: '',
    hora_registro: '',
    temperatura: 0,
    humedad: 0,
    estado_climatico: ''
};

// ============================================
// FUNCIONES PRINCIPALES CRUD
// ============================================

/**
 * Cargar registros del día seleccionado
 */
const listarRegistros = async () => {
    try {
        showLoadingWithTetrisLogo('Cargando registros...', 'ISA Software');
        
        const fecha = $("#inputFecha").val() || new Date().toISOString().split('T')[0];
        
        console.log('📡 Enviando petición AJAX:', {link: link_, op: 'listar', fecha});
        
        const resp = await enviarPeticion({
            link: link_,
            op: 'listar',
            fecha: fecha
        });

        console.log('✅ Respuesta recibida:', resp);

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            dissminSwal();
            return;
        }

        registrosData = resp.data || [];
        
        // Mostrar 3 registros (9AM, 1PM, 5PM)
        renderizarRegistros(registrosData, fecha);
        
        dissminSwal();

    } catch (e) {
        console.error('❌ Error listarRegistros:', e);
        dissminSwal();
        Swal.fire('Error', 'Error al cargar registros: ' + e.message, 'error');
    }
};

/**
 * Renderizar los 3 registros del día
 */
const renderizarRegistros = (datos, fecha) => {
    const horarios = ['9AM', '1PM', '5PM'];
    let html = '<div class="row g-3">';

    horarios.forEach((hora, index) => {
        const registro = datos.find(r => r.hora_registro === hora) || {
            id: 0,
            fecha: fecha,
            hora_registro: hora,
            temperatura: 22,
            humedad: 60,
            estado_climatico: ''
        };

        html += `
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-clock me-2"></i>${hora}
                        </h6>
                        
                        <!-- GAUGE SVG -->
                        <div class="gauge-container mb-3" style="position: relative; width: 150px; height: 150px; margin: 0 auto;">
                            <svg id="gauge-${hora}" viewBox="0 0 200 200" style="width: 100%; height: 100%;">
                                <!-- Fondo del gauge -->
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#e9ecef" stroke-width="15"/>
                                
                                <!-- Arco de humedad (azul) - atrás -->
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#0dcaf0" stroke-width="15" 
                                        stroke-dasharray="141.3 471" stroke-dashoffset="0"
                                        transform="rotate(-90 100 100)"
                                        style="transition: stroke-dasharray 0.5s ease-in-out; opacity: 0.4;"
                                        id="humedad-arc-${hora}"/>
                                
                                <!-- Arco de temperatura (rojo) - adelante -->
                                <circle cx="100" cy="100" r="90" fill="none" stroke="#dc3545" stroke-width="15" 
                                        stroke-dasharray="141.3 471" stroke-dashoffset="0"
                                        transform="rotate(-90 100 100)"
                                        style="transition: stroke-dasharray 0.5s ease-in-out;"
                                        id="temp-arc-${hora}"/>
                                
                                <!-- Centro -->
                                <circle cx="100" cy="100" r="60" fill="white" stroke="#dee2e6" stroke-width="1"/>
                                
                                <!-- Valor de temperatura -->
                                <text id="temp-value-${hora}" x="100" y="95" text-anchor="middle" 
                                      font-size="20" font-weight="bold" fill="#dc3545">
                                    ${registro.temperatura}°C
                                </text>
                                
                                <!-- Valor de humedad -->
                                <text id="humedad-value-center-${hora}" x="100" y="115" text-anchor="middle" 
                                      font-size="14" fill="#0dcaf0">
                                    ${registro.humedad}%
                                </text>
                            </svg>
                        </div>

                        <!-- Datos de Temperatura -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">
                                    <i class="fas fa-thermometer-half text-danger me-1"></i>Temperatura
                                </label>
                                <small class="text-muted">${registro.temperatura}°C</small>
                            </div>
                            <input type="range" class="form-range temperatura-slider" 
                                   id="slider-temp-${hora}" 
                                   data-hora="${hora}" 
                                   min="-50" max="60" step="0.5" 
                                   value="${registro.temperatura}"
                                   style="cursor: pointer;">
                        </div>

                        <!-- Datos de Humedad -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">
                                    <i class="fas fa-droplet text-primary me-1"></i>Humedad
                                </label>
                                <small class="text-muted" id="humedad-value-${hora}">${registro.humedad}%</small>
                            </div>
                            <input type="range" class="form-range humedad-slider" 
                                   id="slider-humedad-${hora}" 
                                   data-hora="${hora}" 
                                   min="0" max="100" step="0.5" 
                                   value="${registro.humedad}"
                                   style="cursor: pointer;">
                        </div>

                        <!-- Estado Climático -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-cloud me-1"></i>Clima
                            </label>
                            <select class="form-select form-select-sm estado-climatico" 
                                    id="select-clima-${hora}" 
                                    data-hora="${hora}">
                                <option value="">Seleccionar...</option>
                                <option value="Soleado" ${registro.estado_climatico === 'Soleado' ? 'selected' : ''}>
                                    ☀️ Soleado
                                </option>
                                <option value="Parcialmente Nublado" ${registro.estado_climatico === 'Parcialmente Nublado' ? 'selected' : ''}>
                                    ⛅ Parcialmente Nublado
                                </option>
                                <option value="Nublado" ${registro.estado_climatico === 'Nublado' ? 'selected' : ''}>
                                    ☁️ Nublado
                                </option>
                                <option value="Lluvioso" ${registro.estado_climatico === 'Lluvioso' ? 'selected' : ''}>
                                    🌧️ Lluvioso
                                </option>
                            </select>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="d-flex gap-2 justify-content-center">
                            <button class="btn btn-sm btn-primary btn-guardar-registro" 
                                    data-id="${registro.id}" 
                                    data-hora="${hora}">
                                <i class="fas fa-save"></i> ${registro.id ? 'Actualizar' : 'Guardar'}
                            </button>
                            ${registro.id ? `
                                <button class="btn btn-sm btn-danger btn-eliminar-registro" 
                                        data-id="${registro.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    $("#registrosContainer").html(html);

    // Reasignar event listeners
    asignarEventListeners();
};

/**
 * Asignar event listeners a sliders y botones
 */
const asignarEventListeners = () => {
    // Sliders de temperatura
    $(".temperatura-slider").on('input', function() {
        const hora = $(this).data('hora');
        const valor = parseFloat($(this).val());
        actualizarEtiqueta(hora, valor, 'temperatura');
    });

    // Sliders de humedad
    $(".humedad-slider").on('input', function() {
        const hora = $(this).data('hora');
        const valor = parseFloat($(this).val());
        actualizarEtiqueta(hora, valor, 'humedad');
    });

    // Botones guardar/actualizar
    $(".btn-guardar-registro").on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const hora = $(this).data('hora');
        const temperatura = parseFloat($(`#slider-temp-${hora}`).val());
        const humedad = parseFloat($(`#slider-humedad-${hora}`).val());
        const estado = $(`#select-clima-${hora}`).val();

        if (id) {
            actualizarRegistro(id, temperatura, humedad, estado);
        } else {
            guardarRegistro(hora, temperatura, humedad, estado);
        }
    });

    // Botones eliminar
    $(".btn-eliminar-registro").on('click', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Confirmar eliminación?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarRegistro(id);
            }
        });
    });
};

/**
 * Actualizar gauge visual
 */
const actualizarGuage = (hora, valor, tipo) => {
    const circunferencia = 471; // 2 * PI * 75
    
    if (tipo === 'temperatura') {
        // Arco rojo de temperatura
        const porcentaje = (valor + 50) / 110; // Convertir -50 a 60 al rango 0-1
        const dasharray = circunferencia * porcentaje;
        $(`#temp-arc-${hora}`).css('stroke-dasharray', `${dasharray} ${circunferencia}`);
    } else if (tipo === 'humedad') {
        // Arco azul de humedad
        const porcentaje = valor / 100; // Convertir 0-100 al rango 0-1
        const dasharray = circunferencia * porcentaje;
        $(`#humedad-arc-${hora}`).css('stroke-dasharray', `${dasharray} ${circunferencia}`);
    }
};

/**
 * Actualizar etiqueta de valor
 */
const actualizarEtiqueta = (hora, valor, tipo) => {
    if (tipo === 'temperatura') {
        $(`#temp-value-${hora}`).text(`${valor}°C`);
        actualizarGuage(hora, valor, 'temperatura');
    } else if (tipo === 'humedad') {
        $(`#humedad-value-center-${hora}`).text(`${valor}%`);
        $(`#humedad-value-${hora}`).text(`${valor}%`);
        actualizarGuage(hora, valor, 'humedad');
    }
};

/**
 * GUARDAR registro nuevo
 */
const guardarRegistro = async (hora, temperatura, humedad, estado) => {
    try {
        showLoadingSwal('Guardando registro...', 'Por favor espere');
        
        const fecha = $("#inputFecha").val() || new Date().toISOString().split('T')[0];

        const resp = await enviarPeticion({
            link: link_,
            op: 'guardar',
            fecha: fecha,
            hora_registro: hora,
            temperatura: temperatura,
            humedad: humedad,
            estado_climatico: estado
        });

        dissminSwal();

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            return;
        }

        Swal.fire('Éxito', resp.message, 'success');
        listarRegistros(); // Recargar

    } catch (e) {
        console.error('Error guardarRegistro:', e);
        dissminSwal();
        Swal.fire('Error', 'Error al guardar registro', 'error');
    }
};

/**
 * ACTUALIZAR registro existente
 */
const actualizarRegistro = async (id, temperatura, humedad, estado) => {
    try {
        showLoadingSwal('Actualizando registro...', 'Por favor espere');

        const resp = await enviarPeticion({
            link: link_,
            op: 'actualizar',
            id: id,
            temperatura: temperatura,
            humedad: humedad,
            estado_climatico: estado
        });

        dissminSwal();

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            return;
        }

        Swal.fire('Éxito', resp.message, 'success');
        listarRegistros(); // Recargar

    } catch (e) {
        console.error('Error actualizarRegistro:', e);
        dissminSwal();
        Swal.fire('Error', 'Error al actualizar registro', 'error');
    }
};

/**
 * ELIMINAR registro
 */
const eliminarRegistro = async (id) => {
    try {
        showLoadingSwal('Eliminando registro...', 'Por favor espere');

        const resp = await enviarPeticion({
            link: link_,
            op: 'eliminar',
            id: id
        });

        dissminSwal();

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            return;
        }

        Swal.fire('Éxito', resp.message, 'success');
        listarRegistros(); // Recargar

    } catch (e) {
        console.error('Error eliminarRegistro:', e);
        dissminSwal();
        Swal.fire('Error', 'Error al eliminar registro', 'error');
    }
};

/**
 * Cambiar fecha
 */
const cambiarFecha = () => {
    listarRegistros();
};

/**
 * Buscar historial por rango de fechas
 */
const buscarHistorial = async () => {
    try {
        const fechaDesde = $("#fechaDesde").val();
        const fechaHasta = $("#fechaHasta").val();
        const hora = $("#horaFiltro").val();

        if (!fechaDesde || !fechaHasta) {
            Swal.fire('Validación', 'Debe seleccionar fecha desde y hasta', 'warning');
            return;
        }

        if (new Date(fechaDesde) > new Date(fechaHasta)) {
            Swal.fire('Validación', 'La fecha desde no puede ser mayor que la fecha hasta', 'warning');
            return;
        }

        showLoadingWithTetrisLogo('Buscando registros...', 'ISA Software');

        const resp = await enviarPeticion({
            link: link_,
            op: 'historial',
            fecha_desde: fechaDesde,
            fecha_hasta: fechaHasta,
            hora_registro: hora
        });

        dissminSwal();

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            return;
        }

        renderizarHistorial(resp.data || []);

    } catch (e) {
        console.error('Error buscarHistorial:', e);
        dissminSwal();
        Swal.fire('Error', 'Error al buscar historial: ' + e.message, 'error');
    }
};

/**
 * Renderizar tabla de historial
 */
const renderizarHistorial = (datos) => {
    if (!datos || datos.length === 0) {
        $("#resultadoHistorial").html(`
            <div class="text-center text-muted py-5">
                <i class="fas fa-inbox fa-2x mb-3"></i>
                <p>No se encontraron registros en el rango seleccionado</p>
            </div>
        `);
        return;
    }

    // Crear tabla
    let html = `
        <table id="tbHistorial" class="table table-sm table-bordered table-hover" width="100%">
            <thead class="table-light">
                <tr>
                    <th><i class="fas fa-calendar"></i> Fecha</th>
                    <th><i class="fas fa-clock"></i> Hora</th>
                    <th><i class="fas fa-thermometer-half text-danger"></i> Temperatura</th>
                    <th><i class="fas fa-droplet text-primary"></i> Humedad</th>
                    <th><i class="fas fa-cloud"></i> Estado Climático</th>
                    <th><i class="fas fa-user"></i> Creado</th>
                    <th><i class="fas fa-edit"></i> Actualizado</th>
                    <th style="width: 100px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
    `;

    datos.forEach(registro => {
        html += `
            <tr>
                <td>${registro.fecha}</td>
                <td>
                    <span class="badge bg-info">${registro.hora_registro}</span>
                </td>
                <td>
                    <strong class="text-danger">${parseFloat(registro.temperatura).toFixed(2)}°C</strong>
                </td>
                <td>
                    <strong class="text-primary">${parseFloat(registro.humedad).toFixed(2)}%</strong>
                </td>
                <td>${registro.estado_climatico || '<span class="text-muted">-</span>'}</td>
                <td><small>${registro.fecha_creacion}</small></td>
                <td><small>${registro.fecha_actualizacion}</small></td>
                <td>
                    <button class="btn btn-xs btn-warning btn-editar-historial" 
                            data-id="${registro.id}" 
                            data-hora="${registro.hora_registro}"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-xs btn-danger btn-eliminar-historial" 
                            data-id="${registro.id}"
                            title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
    `;

    $("#resultadoHistorial").html(html);

    // DataTable
    if ($.fn.DataTable.isDataTable("#tbHistorial")) {
        $("#tbHistorial").DataTable().clear().destroy();
    }

    $("#tbHistorial").DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.15/i18n/Spanish.json'
        },
        pageLength: 10,
        responsive: true
    });

    // Event listeners para botones de historial
    $(".btn-editar-historial").on('click', function() {
        const id = $(this).data('id');
        const hora = $(this).data('hora');
        editarDelHistorial(id, hora);
    });

    $(".btn-eliminar-historial").on('click', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: '¿Confirmar eliminación?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarRegistro(id);
            }
        });
    });
};

/**
 * Editar registro desde historial
 */
const editarDelHistorial = async (id, hora) => {
    try {
        const resp = await enviarPeticion({
            link: link_,
            op: 'obtener_registro',
            id: id
        });

        if (resp.error) {
            Swal.fire('Error', resp.message, 'error');
            return;
        }

        const registro = resp.data;

        // Navegar a la pestaña de registro diario
        const tab = new bootstrap.Tab(document.querySelector('[data-bs-target="#navs-registro"]'));
        tab.show();

        // Establecer la fecha
        $("#inputFecha").val(registro.fecha);

        // Cargar registros
        setTimeout(() => {
            listarRegistros();
            
            // Mostrar alerta
            Swal.fire(
                'Listo',
                `Registro cargado. Haz cambios en la barra ${hora} y haz clic "Actualizar"`,
                'info'
            );
        }, 500);

    } catch (e) {
        console.error('Error editarDelHistorial:', e);
        Swal.fire('Error', 'Error al obtener registro', 'error');
    }
};

/**
 * Inicialización de la página
 */
document.addEventListener('DOMContentLoaded', function() {
    // Establecer fecha por defecto a hoy
    const hoy = new Date().toISOString().split('T')[0];
    $("#inputFecha").val(hoy);
    
    // Inicializar fechas de historial con rango últimos 7 días
    const hace7Dias = new Date();
    hace7Dias.setDate(hace7Dias.getDate() - 7);
    $("#fechaDesde").val(hace7Dias.toISOString().split('T')[0]);
    $("#fechaHasta").val(hoy);
    
    // Event listeners
    $("#inputFecha").on('change', cambiarFecha);
    $("#btnBuscarHistorial").on('click', buscarHistorial);
    
    // Cargar registros iniciales
    listarRegistros();
});

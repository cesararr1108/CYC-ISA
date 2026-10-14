const PARAMETROS = {
    formatos: [],
    cuentas: [],
    parametros: [],
    formatoSeleccionado: null
};

const llenarSelectVigencias = () => {
    const $vigencia = $('#selVigencia');
    $vigencia.empty();

    const currentYear = new Date().getFullYear();
    const ultimoAnio = currentYear - 1;

    // Mostrar los últimos 5 años (incluyendo el anterior)
    for (let year = ultimoAnio; year >= ultimoAnio - 4; year--) {
        $vigencia.append(new Option(year, year, false, year === ultimoAnio));
    }
};

const listarCuentas = async () => {
    try {
        const data = {
            link: '../models/cuentasContables.php',
            op: 'listar-todos'
        };
        const resp = await enviarPeticion(data);

        if (!resp || !Array.isArray(resp.data)) {
            Swal.fire('Imposible continuar!', 'No hay un plan de cuentas cargado, por favor validar.', 'info');
            return null;
        }

        if (resp.data.length === 0) {
            Swal.fire('Imposible continuar!', 'No hay un plan de cuentas cargado, por favor validar.', 'info');
            return null;
        }

        return resp.data;
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Error interno de la aplicación', 'error');
        return null;
    }
};

const llenarSelectCuentas = (cuentas) => {
    const $selectCuenta = $('#selectCuenta');
    $selectCuenta.empty();
    $selectCuenta.append(`<option value="">Seleccione una cuenta</option>`);

    if (!Array.isArray(cuentas)) {
        return;
    }

    cuentas.forEach(item => {
        $selectCuenta.append(`<option value="${item.cuenta}">${item.cuenta} - ${item.descripcion || ''}</option>`);
    });
};

const seleccionarFormato = (event, el, codigo, nombre) => {
    event.preventDefault();
    $('#listaFormatos a').removeClass('active');
    $(el).addClass('active');
    $('#lblFormato').text(`${codigo} - ${nombre}`);
    $('#inputFormato').val(codigo);
    PARAMETROS.formatoSeleccionado = codigo;
    listarConceptos(codigo);
    listarParametros(codigo, $('#selVigencia').val());
};

const listarFormatos = async () => {
    const resp = await enviarPeticion({
        link: '../models/parametrosExogena.php',
        op: 'listar-formatos'
    });

    const $lista = $('#listaFormatos');
    $lista.empty();

    if (!Array.isArray(resp)) {
        Swal.fire('Error', 'No fue posible cargar los formatos.', 'error');
        return;
    }

    resp.forEach(fmt => {

        $lista.append(`
            <a href="#"
               class="list-group-item list-group-item-action formato-item d-flex justify-content-between align-items-center"
               onclick="seleccionarFormato(event,this,'${fmt.formato}','${fmt.nombre}')">

                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-file-lines formato-icon me-2"></i>
                    <span class="formato-nombre">${fmt.nombre}</span>
                </div>

                <span class="badge bg-primary rounded-pill">
                    ${fmt.formato}
                </span>

            </a>
        `);

    });

    const primero = $lista.find('.formato-item').first()[0];

    if (primero) {
        const formato = $(primero).find('.badge').text().trim();
        const nombre = $(primero).find('.formato-nombre').text().trim();

        seleccionarFormato({ preventDefault() {} }, primero, formato, nombre);
    }
};

const listarConceptos = async (formato) => {
    const resp = await enviarPeticion({
        link: '../models/parametrosExogena.php',
        op: 'listar-conceptos',
        formato
    });

    $('#selConceptos').empty();
    $('#selConceptos').append(`<option value="">Seleccione un concepto</option>`);

    if (!Array.isArray(resp)) {
        return;
    }

    resp.forEach((concepto) => {
        $('#selConceptos').append(`<option value="${concepto.concepto}">${concepto.concepto} - ${concepto.descripcion}</option>`);
    });
    $('#selConceptos').val(null).trigger('change');
};

const listarParametros = async (formato, vigencia) => {
    if (!formato || !vigencia) {
        PARAMETROS.parametros = [];
        renderTablaParametros();
        return;
    }

    showLoadingWithTetrisLogo('Cargando parámetros', 'ISA Software');
    try {
        const resp = await enviarPeticion({
            link: '../models/parametrosExogena.php',
            op: 'listar-parametros',
            formato,
            vigencia
        });

        if (!Array.isArray(resp)) {
            PARAMETROS.parametros = [];
            renderTablaParametros();
            return;
        }

        PARAMETROS.parametros = resp;
        renderTablaParametros();
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No fue posible cargar los parámetros', 'error');
    } finally {
        dissminSwal();
    }
};

const getNaturalezaLabel = (naturaleza) => {
    switch (naturaleza) {
        case 'D': return 'Débito';
        case 'C': return 'Crédito';
        case 'S': return 'Saldo';
        default: return naturaleza || '';
    }
};

const renderTablaParametros = () => {
    const body = $('#tbodyParametros');
    if (!Array.isArray(PARAMETROS.parametros) || PARAMETROS.parametros.length === 0) {
        body.html(`<tr><td colspan="5" class="text-center text-muted">No hay parámetros registrados para el formato y vigencia seleccionados.</td></tr>`);
        return;
    }

    const rows = PARAMETROS.parametros.map((item) => {
        return `
            <tr>
                <td>${item.concepto || ''}</td>
                <td>${item.cuenta || ''}</td>
                <td>${item.descripcion_cuenta || ''}</td>
                <td>${getNaturalezaLabel(item.naturaleza)}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-warning me-1 btn-editar-parametro" data-id="${item.id}">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-parametro" data-id="${item.id}">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    body.html(rows);
};

const limpiarFormularioEx = () => {
    $('#param_id').val('');
    $('#selConceptos').val(null).trigger('change');
    $('#selectCuenta').val(null).trigger('change');
    $('#selectNaturaleza').val(null).trigger('change');
    $('#form-parametro-exogena').removeClass('was-validated');
};

const validarFormulario = () => {
    const form = document.getElementById('form-parametro-exogena');
    if (!$('#selVigencia').val()) {
        Swal.fire('Atención', 'Debe seleccionar una vigencia.', 'warning');
        return false;
    }
    if (!PARAMETROS.formatoSeleccionado) {
        Swal.fire('Atención', 'Debe seleccionar un formato.', 'warning');
        return false;
    }
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return false;
    }
    return true;
};

const guardarParametro = async () => {
    if (!validarFormulario()) {
        return;
    }

    const id = $('#param_id').val();
    const data = formatearArrayRequest($('#form-parametro-exogena').serializeArrayAll());
    data.vigencia = $('#selVigencia').val();
    data.formato = $('#inputFormato').val();
    data.link = '../models/parametrosExogena.php';
    data.op = id ? 'u_parametro' : 'i_parametro';

    showLoadingWithTetrisLogo('Guardando parámetro', 'ISA Software');
    try {
        const resp = await enviarPeticion(data);
        if (resp.error) {
            Swal.fire('Error', resp.mensaje || 'No fue posible guardar el parámetro', 'error');
            return;
        }

        Swal.fire('Éxito', resp.mensaje || 'Parámetro guardado correctamente', 'success');
        limpiarFormularioEx();
        listarParametros(PARAMETROS.formatoSeleccionado, $('#selVigencia').val());
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error interno al guardar el parámetro', 'error');
    } finally {
        dissminSwal();
    }
};

const copiarParametros = async () => {
    if (!PARAMETROS.formatoSeleccionado) {
        Swal.fire('Atención', 'Debe seleccionar un formato.', 'warning');
        return;
    }

    const origen = $('#selVigencia').val();
    if (!origen) {
        Swal.fire('Atención', 'Debe seleccionar la vigencia origen.', 'warning');
        return;
    }

    const currentYear = new Date().getFullYear();
    const years = [currentYear - 1, currentYear, currentYear + 1, currentYear + 2];
    const options = years.filter(y => String(y) !== String(origen)).map(y => `<option value="${y}">${y}</option>`).join('');

    const { value: destino } = await Swal.fire({
        title: 'Copiar configuración de vigencia',
        html: `
            <div class="mb-2">Formato: <strong>${PARAMETROS.formatoSeleccionado}</strong></div>
            <div class="mb-2">Origen: <strong>${origen}</strong></div>
            <select id="swalTargetVig" class="form-select">${options}</select>
        `,
        focusConfirm: false,
        showCancelButton: true,
        preConfirm: () => {
            return $('#swalTargetVig').val();
        }
    });

    if (!destino) {
        return;
    }

    if (String(destino) === String(origen)) {
        Swal.fire('Atención', 'La vigencia destino debe ser diferente a la origen.', 'warning');
        return;
    }

    showLoadingWithTetrisLogo('Copiando configuración', 'ISA Software');
    try {
        const resp = await enviarPeticion({
            link: '../models/parametrosExogena.php',
            op: 'copiar-parametros',
            formato: PARAMETROS.formatoSeleccionado,
            from_vigencia: origen,
            to_vigencia: destino
        });

        if (resp.error) {
            Swal.fire('Error', resp.mensaje || 'No fue posible copiar la configuración', 'error');
            return;
        }

        Swal.fire('Éxito', resp.mensaje || 'Configuración copiada correctamente', 'success');
        listarParametros(PARAMETROS.formatoSeleccionado, $('#selVigencia').val());
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error interno al copiar la configuración', 'error');
    } finally {
        dissminSwal();
    }
};

const editarParametro = (id) => {
    const item = PARAMETROS.parametros.find(x => Number(x.id) === Number(id));
    if (!item) {
        Swal.fire('Error', 'No se encontró el parámetro seleccionado', 'error');
        return;
    }

    $('#param_id').val(item.id);
    $('#selConceptos').val(item.concepto).trigger('change');
    $('#selectCuenta').val(item.cuenta).trigger('change');
    $('#selectNaturaleza').val(item.naturaleza).trigger('change');
};

const eliminarParametro = async (id) => {
    const confirmacion = await Swal.fire({
        title: 'Eliminar parámetro',
        text: '¿Confirma que desea eliminar este parámetro?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!confirmacion.isConfirmed) {
        return;
    }

    showLoadingWithTetrisLogo('Eliminando parámetro', 'ISA Software');
    try {
        const resp = await enviarPeticion({
            link: '../models/parametrosExogena.php',
            op: 'd_parametro',
            id
        });

        if (resp.error) {
            Swal.fire('Error', resp.mensaje || 'No fue posible eliminar el parámetro', 'error');
            return;
        }

        Swal.fire('Éxito', resp.mensaje || 'Parámetro eliminado correctamente', 'success');
        limpiarFormularioEx();
        listarParametros(PARAMETROS.formatoSeleccionado, $('#selVigencia').val());
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Error interno al eliminar el parámetro', 'error');
    } finally {
        dissminSwal();
    }
};

$(async function () {
    llenarSelectVigencias();

    try {
        showLoadingWithTetrisLogo('Cargando datos', 'ISA Software');

        const [cuentas] = await Promise.all([listarCuentas()]);

        if (!cuentas) {
            dissminSwal();
            return;
        }

        PARAMETROS.cuentas = cuentas;
        llenarSelectCuentas(cuentas);

        $('#selectCuenta, #selConceptos, #selectNaturaleza, #selVigencia').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            language: {
                noResults: function () {
                    return 'No se encontraron resultados';
                }
            }
        });

        $('#btnGuardarParametro').on('click', guardarParametro);
        $('#btnCancelarParametro').on('click', limpiarFormularioEx);
        $('#btnCopiarVigencia').on('click', copiarParametros);
        $('#selVigencia').on('change', function () {
            listarParametros(PARAMETROS.formatoSeleccionado, $(this).val());
        });

        $(document).on('click', '.btn-editar-parametro', function () {
            editarParametro($(this).data('id'));
        });

        $(document).on('click', '.btn-eliminar-parametro', function () {
            eliminarParametro($(this).data('id'));
        });

        await listarFormatos();
    } catch (e) {
        console.error(e);
        Swal.fire('Error', 'Error al cargar los datos', 'error');
    } finally {
        dissminSwal();
    }
});
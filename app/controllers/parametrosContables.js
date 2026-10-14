const listarCuentas = async () => {
  try {
    const data = {
      link: '../models/cuentasContables.php',
      op: 'listar-todos'
    }
    const resp = await enviarPeticion(data);
    if (resp.data.length == 0) {
      Swal.fire({
        title: 'Imposible continuar!',
        text: 'No hay un plan de cuentas cargado, por favor validar.',
        icon: 'info',
        showCloseButton: false,
        showConfirmButton: false,
        allowOutsideClick: false
      })
      return null;
    }
    return resp.data;
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
    return null;
  }
}

let modalCuentasTributarias = null;
let cuentasTributarias = [];

const tiposTributarios = {
  IVA_GENERADO: 'IVA Generado',
  IVA_DESCONTABLE: 'IVA Descontable',
  RETEFUENTE_COMPRA: 'Retefuente Compra',
  RETEFUENTE_VENTA: 'Retefuente Venta',
  RETEIVA_COMPRA: 'ReteIVA Compra',
  RETEIVA_VENTA: 'ReteIVA Venta',
  RETEICA_COMPRA: 'ReteICA Compra',
  RETEICA_VENTA: 'ReteICA Venta'
};

const tipoLegible = (tipo) => tiposTributarios[tipo] || tipo;

const limpiarFormularioCuentaTributaria = () => {
  $('#ct_id').val('');
  $('#ct_tipo').val('').trigger('change');
  $('#ct_cuenta_busqueda').val(null).trigger('change');
};

const renderTablaCuentasTributarias = () => {
  const body = $('#tbl-cuentas-tributarias tbody');

  if (!Array.isArray(cuentasTributarias) || cuentasTributarias.length === 0) {
    body.html('<tr><td colspan="3" class="text-center text-muted">No hay cuentas configuradas</td></tr>');
    return;
  }

  const rows = cuentasTributarias.map((item) => {
    const cuenta = `${item.cuenta || ''} - ${item.descripcion || ''}`;
    return `
      <tr>
        <td>${tipoLegible(item.tipo)}</td>
        <td>${cuenta}</td>
        <td>
          <button type="button" class="btn btn-sm btn-outline-warning btn-editar-cuenta-tributaria" data-id="${item.id}" title="Editar">
            <i class="fa fa-pen"></i>
          </button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar-cuenta-tributaria" data-id="${item.id}" title="Eliminar">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');

  body.html(rows);
};

const cargarCuentasTributarias = async () => {
  showLoadingWithTetrisLogo('Cargando parametros tributarios', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: '../models/parametrosContables.php',
      op: 's_cuentas_tributarias'
    });

    if (resp.error) {
      Swal.fire('Error', resp.mensaje || 'No fue posible cargar los datos', 'error');
      return;
    }

    cuentasTributarias = Array.isArray(resp.data) ? resp.data : [];
    renderTablaCuentasTributarias();
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'Error al cargar las cuentas tributarias', 'error');
  } finally {
    dissminSwal();
  }
};

const validarFormularioCuentaTributaria = () => {
  const tipo = $('#ct_tipo').val();
  const idCuentaContable = Number($('#ct_cuenta_busqueda').val() || 0);

  if (!tipo) {
    Swal.fire('Ups', 'Debe seleccionar el tipo tributario', 'warning');
    return false;
  }

  if (!idCuentaContable) {
    Swal.fire('Ups', 'Debe seleccionar una cuenta contable valida de la lista', 'warning');
    return false;
  }

  return true;
};

const guardarCuentaTributaria = async () => {
  if (!validarFormularioCuentaTributaria()) {
    return;
  }

  const id = Number($('#ct_id').val() || 0);
  const data = {
    link: '../models/parametrosContables.php',
    op: id > 0 ? 'u_cuenta_tributaria' : 'i_cuenta_tributaria',
    id,
    tipo: $('#ct_tipo').val(),
    id_cuenta_contable: Number($('#ct_cuenta_busqueda').val())
  };

  showLoadingWithTetrisLogo('Guardando configuracion', 'ISA Software');
  try {
    const resp = await enviarPeticion(data);
    if (resp.error) {
      Swal.fire('Error', resp.mensaje || resp.message || 'No fue posible guardar el registro', 'error');
      return;
    }

    Swal.fire('Exito', resp.mensaje || resp.message || 'Operacion exitosa', 'success');
    await cargarCuentasTributarias();
    limpiarFormularioCuentaTributaria();
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'Error al guardar la configuracion', 'error');
  } finally {
    dissminSwal();
  }
};

const editarCuentaTributaria = (id) => {
  const item = cuentasTributarias.find((x) => Number(x.id) === Number(id));
  if (!item) {
    Swal.fire('Error', 'No se encontro el registro a editar', 'error');
    return;
  }

  $('#ct_id').val(item.id);
  $('#ct_tipo').val(item.tipo).trigger('change');

  const option = new Option(`${item.cuenta} - ${item.descripcion}`, String(item.id_cuenta_contable), true, true);
  $('#ct_cuenta_busqueda').append(option).trigger('change');
};

const eliminarCuentaTributaria = async (id) => {
  const confirmacion = await Swal.fire({
    title: 'Confirmar',
    text: 'Desea eliminar este tipo tributario?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar'
  });

  if (!confirmacion.isConfirmed) return;

  showLoadingWithTetrisLogo('Eliminando configuracion', 'ISA Software');
  try {
    const resp = await enviarPeticion({
      link: '../models/parametrosContables.php',
      op: 'd_cuenta_tributaria',
      id
    });

    if (resp.error) {
      Swal.fire('Error', resp.mensaje || 'No fue posible eliminar el registro', 'error');
      return;
    }

    Swal.fire('Exito', resp.mensaje || 'Operacion exitosa', 'success');
    await cargarCuentasTributarias();
    limpiarFormularioCuentaTributaria();
  } catch (error) {
    console.error(error);
    Swal.fire('Error', 'Error al eliminar el registro', 'error');
  } finally {
    dissminSwal();
  }
};

const getParametros = async () => {
  try {
    const dataSend = {
      link: '../models/parametrosContables.php',
      op: 'listar-parametros'
    };
    const resp = await enviarPeticion(dataSend);
    console.log(resp);
    return resp.length > 0 ? resp[0] : null;
  } catch (e) {
    console.log(e);
    return null;
  }
};

const llenarSelectCuentas = (cuentas) => {
  const $selects = $('.form-select').not('#ct_tipo, #ct_cuenta_busqueda');
  
  // Llenar cada select con las cuentas
  $selects.each(function() {
    const fragment = document.createDocumentFragment();
    
    // Opción vacía
    const emptyOption = new Option('', '', false, false);
    fragment.appendChild(emptyOption);
    
    // Agregar todas las cuentas de una vez al fragment
    cuentas.forEach(item => {
      const option = new Option(item.cuenta + ' - ' + item.descripcion, item.cuenta, false, false);
      fragment.appendChild(option);
    });
    
    // Agregar todo el fragment de una sola vez
    this.appendChild(fragment);
  });
};
$(async function () {
  showLoadingWithTetrisLogo('Cargando datos', 'ISA Software');
  
  try {
    // Ejecutar ambas consultas en paralelo
    const [cuentas, parametros] = await Promise.all([
      listarCuentas(),
      getParametros()
    ]);
    
    if (!cuentas) {
      dissminSwal();
      return;
    }
    
    // Llenar el select con todas las cuentas de una vez
    llenarSelectCuentas(cuentas);
    
    // Inicializar Select2 después de agregar todas las opciones
    $('.form-select').not('#ct_tipo, #ct_cuenta_busqueda').select2({
      theme: 'bootstrap-5',
      allowClear: true,
      language: {
        noResults: function () {
          return "No se encontraron resultados"; 
        }
      }
    });
    
    // Llenar los parámetros
    $('#form-parametros-contables').removeClass('was-validated');
    if (parametros) {
      ShowDataFrom(parametros, 'form-parametros-contables', '');
    }
    
    dissminSwal();
  } catch (e) {
    console.error(e);
    dissminSwal();
    Swal.fire("Error", "Error al cargar los datos", "error");
  }

  const modalEl = document.getElementById('modalCuentasTributarias');
  if (modalEl) {
    modalCuentasTributarias = new bootstrap.Modal(modalEl);

    $('#ct_tipo').select2({
      theme: 'bootstrap-5',
      width: '100%',
      placeholder: 'Seleccione...',
      allowClear: true,
      dropdownParent: $('#modalCuentasTributarias')
    });

    $('#ct_cuenta_busqueda').select2({
      theme: 'bootstrap-5',
      width: '100%',
      placeholder: 'Buscar por cuenta o descripcion',
      allowClear: true,
      dropdownParent: $('#modalCuentasTributarias'),
      minimumInputLength: 2,
      ajax: {
        delay: 250,
        transport: function (params, success, failure) {
          enviarPeticion({
            link: '../models/parametrosContables.php',
            op: 'buscar_cuenta_contable',
            filtro: params.data.term || ''
          }).then(success).catch(failure);
        },
        processResults: function (resp) {
          const resultados = !resp || resp.error || !Array.isArray(resp.data)
            ? []
            : resp.data;

          return {
            results: resultados.map((item) => ({
              id: item.id,
              text: `${item.cuenta} - ${item.descripcion}`
            }))
          };
        }
      },
      language: {
        inputTooShort: () => 'Escriba al menos 2 caracteres',
        noResults: () => 'Sin coincidencias',
        searching: () => 'Buscando...'
      }
    });

    $('#btn-configurar-tributarios').on('click', async function () {
      limpiarFormularioCuentaTributaria();
      modalCuentasTributarias.show();
      await cargarCuentasTributarias();
    });

    $('#btn-guardar-cuenta-tributaria').on('click', guardarCuentaTributaria);
    $('#btn-cancelar-cuenta-tributaria').on('click', limpiarFormularioCuentaTributaria);

    $(document).on('click', '.btn-editar-cuenta-tributaria', function () {
      editarCuentaTributaria($(this).data('id'));
    });

    $(document).on('click', '.btn-eliminar-cuenta-tributaria', function () {
      eliminarCuentaTributaria($(this).data('id'));
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
      limpiarFormularioCuentaTributaria();
    });
  }

  // Manejar el envío del formulario
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
          form.classList.add('was-validated');
          return;
        }
        let data = formatearArrayRequest($(this).serializeArray());
        data.link = '../models/parametrosContables.php';
        data.op = 'actualizar';
        try {
          const resp = await enviarPeticion(data);
          if (resp.error) {
            dissminSwal();
            Swal.fire("Error", resp.message, "error");
            return;
          }
          dissminSwal();
          Swal.fire("Ok", resp.message, "success");

        } catch (e) {
          dissminSwal();
          console.error(e);
          Swal.fire("Error", "Error interno de la aplicacion", "error");
        }
      }, false)
    });
});

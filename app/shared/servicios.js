let ConfGeneral;
let ConfModulos;

/** FIN SERVICIO PARA EL MANEJO DE LAS COOKIE */

/** INICIO SERVICIOS LOCALSTORAGE */
// Función para crear un nuevo elemento en localStorage 
function crearElemento(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

// Función para leer un elemento de localStorage
function leerElemento(key) {
  const value = localStorage.getItem(key);
  return value ? JSON.parse(value) : null;
}

// Función para actualizar un elemento en localStorage
function actualizarElemento(key, newValue) {
  if (localStorage.getItem(key)) {
    localStorage.setItem(key, JSON.stringify(newValue));
    return true; // Actualización exitosa
  }
  return false; // Elemento no encontrado
}

// Función para eliminar un elemento de localStorage
function eliminarElemento(key) {
  if (localStorage.getItem(key)) {
    localStorage.removeItem(key);
    return true; // Eliminación exitosa
  }
  return false; // Elemento no encontrado
}


/**FIN LOCALSTORAGE */
/*
 @ Paises 
*/

const paises = async (id, link) => {
  try {
    const data = {
      link: '../shared/servicios/servicios.php',
      op: 'paises'
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.paises.forEach(function (d) {
      option += `<option value="${d.pais}">${d.descripcion.toUpperCase()}</option>`;
    });
    $("#" + id).html(option);
  } catch (e) {
    console.log(e);
  }
}


/*
@ Departamentos 
*/

const departamentos = async (codigo_pais, id) => {

  try {
    const data = {
      link: '../shared/servicios/servicios.php',
      op: 'departamentos',
      codigo_pais
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.departamentos.forEach(function (d) {
      option += `<option value="${d.codigo_dpto}">${d.departamento.toUpperCase()}</option>`;
    });
    $("#" + id).html(option);
  } catch (e) {
    console.log(e);
  }
}

/*
 @ Municipios 
*/

const municipios = async (codigo_pais, codigo_dpto, id) => {
  try {
    const data = {
      link: '../shared/servicios/servicios.php',
      op: 'municipios',
      codigo_dpto,
      codigo_pais
    };
    var resp = await enviarPeticion(data);
    let option = ``;
    resp.municipios.forEach(function (d) {
      option += `<option value="${d.codigo}">${d.municipio.toUpperCase()}</option>`;
    });
    $("#" + id).html(option);
  } catch (e) {
    console.log(e);
  }
}

const condicionesPago = async (tipo, id, transformarData = null) => {
  let resp = await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'condiciones_pago',
    tipo
  })

  if (resp.length) {
    let p = ''
    if (transformarData) {
      resp = transformarData(resp); // Aplica la transformación a los datos
    }
    resp.forEach(item => {
      p += `<option value="${item.id}">${item.descripcion} (${item.dto} %)</option>`
    })
    $("#" + id).html(p);
  }
}

const tiposDocumentos = async (sw, bodega, id) => {

  const data = {
    link: '../models/tiposDocumentos.php',
    sw,
    op: 'listar_sw',
    bodega
  }
  const resp = await enviarPeticion(data)

  if (resp.data.length) {
    p = ''

    resp.data.forEach(item => {
      p += `<option value="${item.id}" data-element='${JSON.stringify(item)}'>${item.tipo}</option>`
    })

    $("#" + id).html(p);
  }
}

const bodegasUsuarios = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'bodegas-usuarios',
  });

}

const configService = async () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios-confg-sys.php',
    id_modulo
  });
}

const validadCierreMes = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'validar-cierre-mes'
  });
}

const ValorCajaUsuario = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'valor-caja-turno'
  });
}

const getRol = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'get-rol'
  });
}

const generarContabilizacion = async (id_transaccion) => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'contabilizar-documento',
    id_transaccion
  });
}

const listasPrecios = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'listas-precios'
  });
}

const cargarListaAlmacen = async () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'lista-almacen'
  });
}

const procesarConfiguraciones = (data) => {
  const general = {};
  const modulos = {};

  if (data) {
    // Procesar General
    if (Array.isArray(data.confGeneral)) {
      data.confGeneral.forEach(item => {
        const claves = Object.keys(item).filter(k => k !== "descripcion");
        claves.forEach(k => {
          general[k] = item[k];
        });
      });
    }

    // Procesar Módulos
    if (Array.isArray(data.confModulos)) {
      data.confModulos.forEach(item => {
        const idModulo = item.id_modulo;
        if (!modulos[idModulo]) modulos[idModulo] = {};
        const claves = Object.keys(item).filter(k => k !== "descripcion" && k !== "id_modulo");
        claves.forEach(k => {
          modulos[idModulo][k] = item[k];
        });
      });
    }
  }

  return { general, modulos };
}

const cargarConfiguraciones = async (id_modulo) => {
  try {
    const resp = await configService(id_modulo)
    return procesarConfiguraciones(resp);

  } catch (error) {
    $("body").html(`
        <div class="alert alert-danger"><h4>Ups no se pudo iniciar el sistema!. Error al cargar la configuración</h4></div>
      `)
    return;
  }
}
  
const permisosUsuario = async (id_modulo) => {
  const resp = await enviarPeticion({
    link: '../shared/servicios/servicio-permiso.php',
    id_modulo
  });

  // Filtrar primero solo los que sean del id_modulo enviado
  const filtrados = resp.filter(item => item.id_modulo == id_modulo);

  // Convertir en objeto clave=permiso, valor=1
  const permisos = filtrados.reduce((acc, item) => {
    acc[item.permiso] = 1;
    return acc;
  }, {});

  return permisos;
};

const preferenciasUsuario = async (id_modulo) => {
  resp = await enviarPeticion({
    link: '../shared/servicios/servicios-preferencias.php',
    id_modulo
  })

  const objetoClaveValor = Object.fromEntries(
    resp.data.map(item => [item.clave, item.valor])
  );
  return objetoClaveValor;
}

const ValidarDataBasicaTerceroCliente = (item, permisos) => {

  let sw = true;
  let msj;
  if (!item) {
    throw new Error("No se envio la data del tercero");
    return;
  }
  if (!permisos) {
    throw new Error("No se envio la configuracion de permisos ");
    return;
  }
  //bloqueo
  if (!permisos.IgnorarBloqueoTercero && sw) {
    if (item.estado != 'A') {
      msj = 'El cliente se encuentra bloqueao'
      sw = false;
    }
  }
  if (!validarEmail(item.email_fe) && sw) {
    msj = 'El email del cliente no es valido'
    sw = false;
  }


  return { sw, msj };
}

const CreditManagement = async (nit, permisos) => {

  let sw = true;
  let msj;

  const resp = await enviarPeticion({
    link: '../shared/servicios/servicios-credit-management.php',
    op: 'validar-disponibilidad',
    nit
  })

  const item = resp[0]

  if (permisos.ControlCupoTercero && sw) {
    if (item.cupo_credito == 0) {
      msj = 'El cliente no tiene cupo de credito'
      sw = false;
    }
  }
  if (permisos.ValidarCarteraVencida && sw) {

    if (item.cartera_vencida > 0) {
      let link = '';

      if (permisos.VerDocumentosCarteraVencida) {
        link = `<a href="#" class="link-detalle-cartera">Ver detalle</a>`
      }
      msj = 'El cliente tiene cartera vencida ' + link
      sw = false;
    }
  }
  return { sw, msj };
}

const serviceTerceroEmpresa = () => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'tercero-empresa'
  })
}

const serviceListarUsuarios = (estado = '', filtro = '', rol = '') => {
  return enviarPeticion({
    link: '../models/usuarios.php',
    op: 's_usuario_all',
    estado,
    filtro,
    rol
  })
}


// SERVICIOS DE RETENCIÓN EN LA FUENTE
const serviceRetencionesFuente = async (id_tipo_documento) => {
  return enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 'get-tipos-documentos-concep-ret',
    id_tipo_documento
  });
}


const controlCreditoTercero = async (id_tercero) => {
  return await enviarPeticion({
    link: '../shared/servicios/servicios.php',
    op: 's_control_credito',
    id_tercero
  });
};

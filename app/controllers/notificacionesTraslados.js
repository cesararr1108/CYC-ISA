// Notificaciones de Traslados Recibidos
let trasladosNotificados = []; // Para almacenar los IDs ya notificados
let verificandoTraslados = false;

// Verificar traslados recibidos periódicamente
const verificarTrasladosRecibidos = async () => {
  if (verificandoTraslados) return;
  
  try {
    verificandoTraslados = true;
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'traslados-recibidos'
    });

    if (resp && resp.length > 0) {
      // Filtrar solo los traslados nuevos
      const trasladosNuevos = resp.filter(t => !trasladosNotificados.includes(t.id));
      
      if (trasladosNuevos.length > 0) {
        trasladosNotificados.push(...trasladosNuevos.map(t => t.id));
        mostrarBadgeTraslados(resp.length);
          /*      
        // Notificación visual
        Swal.fire({
          icon: 'info',
          title: 'Nuevo Traslado Recibido',
          text: `Tienes ${trasladosNuevos.length} traslado(s) nuevo(s) en tu bodega`,
          timer: 5000,
          timerProgressBar: true
        });*/
      }
    } else {
      ocultarBadgeTraslados();
    }
  } catch (e) {
    // Error silencioso
  } finally {
    verificandoTraslados = false;
  }
};

// Mostrar badge de notificación
const mostrarBadgeTraslados = (cantidad) => {
  const badge = document.getElementById('badge-traslados');
  const contador = document.getElementById('count-notificaciones');
  
  if (badge && contador) {
    contador.textContent = cantidad;
    badge.style.display = 'inline-block';
  }
};

// Ocultar badge de notificación
const ocultarBadgeTraslados = () => {
  const badge = document.getElementById('badge-traslados');
  if (badge) {
    badge.style.display = 'none';
  }
};

// Modal para mostrar traslados recibidos
const crearModalTrasladosRecibidos = () => {
  const modalHTML = `
    <div class="modal fade" id="modalTrasladosRecibidos" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Traslados asociados a mi bodega</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="contenidoTrasladosRecibidos"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  document.body.insertAdjacentHTML('beforeend', modalHTML);
};

// Mostrar tabla de traslados recibidos
const mostrarTrasladosRecibidos = async () => {
  try {
     showLoadingWithTetrisLogo('Cargando traslados por favor espere...', 'ISA Software');
    
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'traslados-recibidos'
    });
    console.log({
      resp
    })
    if (!resp || resp.length === 0) {
      dissminSwal();
      return;
    }

    let htmlTabla = `
      <div class="table-responsive">
        <table id="tblTrasladosRecibidos" class="table table-bordered table-hover">
          <thead class="table-primary">
            <tr>
              <th>Número</th>
              <th>Fecha</th>
              <th>De Bodega</th>
              <th>A Bodega</th>
              <th>Usuario</th>
              <th class="text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>`;

    resp.forEach((traslado) => {
      htmlTabla += `
        <tr>
          <td><strong>${traslado.numero}</strong></td>
          <td>${new Date(traslado.fecha).toLocaleDateString('es-CO')}</td>
          <td>${traslado.b_origen}</td>
          <td>${traslado.destino}</td>
          <td>${traslado.usuario_generador}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary" onclick="verTrasladoRecibido(${traslado.id})" title="Ver detalle">
              <i class="fa-solid fa-eye"></i>
            </button>
          </td>
        </tr>`;
    });

    htmlTabla += `
          </tbody>
        </table>
      </div>`;

    document.getElementById('contenidoTrasladosRecibidos').innerHTML = htmlTabla;
    
    // Mostrar modal
    let modal = document.getElementById('modalTrasladosRecibidos');
    if (!modal) {
      crearModalTrasladosRecibidos();
      modal = document.getElementById('modalTrasladosRecibidos');
    }
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Actualizar contador basado en JSON actual
    trasladosNotificados = resp.map(t => t.id);
    if (resp.length > 0) {
      mostrarBadgeTraslados(resp.length);
    } else {
      ocultarBadgeTraslados();
    }

    dissminSwal();

  } catch (e) {
    dissminSwal();
    Swal.fire('Error', 'Error al cargar traslados recibidos', 'error');
  }
};

// Ver detalle de traslado recibido
const verTrasladoRecibido = async (idTraslado) => {
  try {
    await ImprimirDocumento(3, idTraslado, 'Impresion de traslado', false);
     $("#modalTrasladosRecibidos").modal('hide');
     $("#contenidoTrasladosRecibidos").html('')

  } catch (e) {
    Swal.fire('Error', 'Error al imprimir el traslado', 'error');
  }
};



// Ejecutar cuando el DOM esté listo
//document.addEventListener('DOMContentLoaded', inicializarNotificacionesTraslados);

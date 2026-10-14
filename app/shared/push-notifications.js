// ============================================================
// push-notifications.js
// Versión SIN import/export (JS clásico con <script> normal).
//
// Antes de este archivo, en tu HTML debes cargar (en este orden):
//
// <script src="https://www.gstatic.com/firebasejs/12.18.0/firebase-app-compat.js"></script>
// <script src="https://www.gstatic.com/firebasejs/12.18.0/firebase-messaging-compat.js"></script>
// <script src="js/push-notifications.js"></script>
// ============================================================

// Misma config que en firebase-messaging-sw.js
var firebaseConfig = {
    apiKey: "AIzaSyATCXBm38Vm0rWPTL4lK3oPV0Jfa8vektE",

    authDomain:
        "cyc-notifications-push.firebaseapp.com",

    projectId:
        "cyc-notifications-push",

    storageBucket:
        "cyc-notifications-push.firebasestorage.app",

    messagingSenderId:
        "943820201444",

    appId:
        "1:943820201444:web:171e3531c7b861bfa7fbf1"
};

// Clave pública VAPID: Firebase Console > Configuración del proyecto
// > Cloud Messaging > "Certificados push web" > generar par de claves
// TODO: pégala aquí cuando la generes (ver instrucciones)
var VAPID_KEY = "BMkxZL77tHSmA4F5cheCQdqkMy2UBMpFTD1y4Lja7ZY5BFSySxIcTs7K1IZTlBtC1E8fFxY7ekEMDgTHPARx8RU";

firebase.initializeApp(firebaseConfig);
var messaging = firebase.messaging();

// Decide qué hacer con el clic, venga de donde venga
const manejarClickNotificacion  = async (data)=> {
mostrarTrasladosRecibidos()

}

// Mensajes que manda el Service Worker cuando se hace clic con la pestaña en segundo plano
if ("serviceWorker" in navigator) {
  navigator.serviceWorker.addEventListener("message", function (event) {
    if (event.data && event.data.accion === "click-notificacion") {
      manejarClickNotificacion(event.data.data);
    }
  });
}

// Mostrar badge de notificación
const mostrarBadgeTraslados = (cantidad) => {
  const badge = document.getElementById('badge-traslados');
  const contador = document.getElementById('count-notificaciones');
  
  if (badge && contador) {
    contador.textContent = cantidad;
    badge.style.display = 'inline-block';
  }
};


const mostrarConteoTraslados =async ()=>{

  try{
      const resp  = await enviarPeticion({
        link : '../models/TrasladosBodegas.php',
        op:'traslados-salidas'
      })
      console.log({
        resp
      })
      if(resp.length==0){
        ocultarBadgeTraslados()
        return;
      }
     mostrarBadgeTraslados(resp.length)
  }catch(e){
     ocultarBadgeTraslados()
    console.error({e})
  }
}


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

    if (!resp || resp.length === 0) {
      mostrarConteoTraslados()
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
            <button class="btn btn-sm btn-outline-primary" onclick="verTrasladoRecibido(${traslado.id},${traslado.id_alerta})" title="Ver detalle">
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
const eliminarAlerta = async (idAlerta) => {
  try {
   
    const resp = await enviarPeticion({
      link: '../models/TrasladosBodegas.php',
      op: 'eliminar-alerta',
      id: idAlerta
    });

    if (resp.success) {
      mostrarConteoTraslados();
    }
  } catch (error) {
    console.error(error);
  }
};
// Ver detalle de traslado recibido
const verTrasladoRecibido = async (idTraslado,idAlerta) => {
  try {
    await ImprimirDocumento(3, idTraslado, 'Impresion de traslado', false);
    eliminarAlerta(idAlerta)
     $("#modalTrasladosRecibidos").modal('hide');
     $("#contenidoTrasladosRecibidos").html('')

  } catch (e) {
    Swal.fire('Error', 'Error al imprimir el traslado', 'error');
  }
};
// Ocultar badge de notificación
const ocultarBadgeTraslados = () => {
  const badge = document.getElementById('badge-traslados');
  if (badge) {
    badge.style.display = 'none';
  }
};


messaging.onMessage(function (payload) {
  console.log("Push recibido en primer plano:", payload);

  var n = payload.notification || {};
  var d = payload.data || {};
  var link = (payload.fcmOptions && payload.fcmOptions.link) || d.url || d.link || "";

  if (!("Notification" in window) || Notification.permission !== "granted") return;

  var titulo = n.title || d.title || "Notificación";
  var datosClick = Object.assign({}, d, { link: link });
  var opciones = {
    body: n.body || d.body || "",
    icon: n.icon || d.icon || "../../assets/img/logo-mini.png",
    data: datosClick
  };

  mostrarConteoTraslados();

  try {
    var notif = new Notification(titulo, opciones);
    notif.onclick = function (event) {
      event.preventDefault();
      window.focus();
      manejarClickNotificacion(n);
      notif.close();
    };
  } catch (e) {
    // Android: el clic lo maneja notificationclick del Service Worker
    navigator.serviceWorker.ready.then(function (reg) {
      reg.showNotification(titulo, opciones);
    });
  }
});

  document.addEventListener('DOMContentLoaded', function(){
        mostrarConteoTraslados()


        const btnNotificaciones = document.getElementById('btn-notificaciones-traslados');

        if (btnNotificaciones) {
            btnNotificaciones.addEventListener('click', mostrarTrasladosRecibidos);
        }

        // Crear modal si no existe
        if (!document.getElementById('modalTrasladosRecibidos')) {
            crearModalTrasladosRecibidos();
        }
  });
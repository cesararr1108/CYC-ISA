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
function manejarClickNotificacion(data) {
  data = data || {};
  alert(data.tipo)
  if (data.tipo === "traslado" && typeof abrirModalTraslado === "function") {
    abrirModalTraslado(data.id);        // Estás en el home: abre el modal
  } else if (data.url) {
    window.location.href = data.url;    // Otra página o una venta: redirige
  }
}

// Mensajes que manda el Service Worker cuando se hace clic con la pestaña en segundo plano
if ("serviceWorker" in navigator) {
  navigator.serviceWorker.addEventListener("message", function (event) {
    if (event.data && event.data.accion === "click-notificacion") {
      manejarClickNotificacion(event.data.data);
    }
  });
}

const mostrarConteoTraslados =async ()=>{
  console.log("mostrarConteoTraslados")
  try{
      const resp  = await enviarPeticion({
        link : '../../../models/TrasladosBodegas.php',
        op:'traslados-salidas'
      })
      console.log({
        resp
      })
  }catch(e){
    console.error({e})
  }
}

messaging.onMessage(function (payload) {
  console.log("Push recibido en primer plano:", payload);
  var d = payload.data || {};

  if (!("Notification" in window) || Notification.permission !== "granted") return;

  var titulo = d.title || "Notificación";
  var opciones = { body: d.body || "", icon: d.icon || "../../assets/img/logo-mini.png", data: d };

  try {
    var notif = new Notification(titulo, opciones);
    mostrarConteoTraslados()
    notif.onclick = function (event) {

      event.preventDefault();
      window.focus();
      manejarClickNotificacion(d);
      notif.close();
    };
  } catch (e) {
    // Android: el clic lo maneja notificationclick del Service Worker
    navigator.serviceWorker.ready.then(function (reg) {
      reg.showNotification(titulo, opciones);
    });
  }
});
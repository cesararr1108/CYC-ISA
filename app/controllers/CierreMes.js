const consultarPeriodos = async () => {
  const data = {
    link: '../models/CierreMes.php',
    op: 'periodos'
  }
  const resp = await enviarPeticion(data); 
  $("#periodo_actual").val(resp.periodo_actual);
  $("#periodo_cierre").val(resp.nuevo_periodo);
  $("#periodo_cerrado").val(resp.periodo_cerrado);
  $("#fecha_actual").val(resp.fecha_actual);
  dissminSwal();
}

const confirmarCierre = () => {
  Swal.fire({
    title: '¿Está seguro?',
    text: '¿Desea realizar el cierre del período?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      realizarCierre();
    } else {
      Swal.fire('Operación cancelada', '', 'info');
    }
  });
}

const realizarCierre = async () => {
  showLoadingWithTetrisLogo('Realizando cierre de mes', 'ISA Software')
  let pCierre = $("#periodo_cierre").val();
  const data = {
    link: '../models/CierreMes.php',
    op: 'cierre',
    periodo: pCierre
  }
  try {
    const resp = await enviarPeticion(data);
    await consultarPeriodos();
    Swal.fire(resp.title, resp.message, resp.option);
  } catch (e) {
    console.log(e);
    Swal.fire('¡Error!', 'Ha ocurrido un error inesperado, verifique e intente nuevamente.', 'error');
  }

}
$(async function () {
 await consultarPeriodos();
  $("#btn-cierre").click(async function () {
    await consultarPeriodos();

    let pActual = $("#periodo_actual").val();
    let pCierre = $("#periodo_cierre").val();
    let fActual = $("#fecha_actual").val();

    if (pActual === fActual) {
      Swal.fire({
        html: `
        <h4 style="font-weight: bold;">¡Advertencia!</h4>
        <p>
            El período actual es igual a la fecha actual, <strong>no es necesario hacer el cierre</strong>.<br>
            ¿Aún así desea continuar?
        </p>
    `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          confirmarCierre(); // Llamar a la función para confirmar el cierre
        } else {
          Swal.fire('Operación cancelada', '', 'info'); // Mostrar mensaje de cancelación
        }
      });
    } else {
      confirmarCierre();
    }
  });

});

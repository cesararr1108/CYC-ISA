const listarBodegas = async () => {

  try {
    showLoadingWithTetrisLogo('Cargando bodegas', 'ISA Software')

    const resp = await bodegasUsuarios();

    img = $("#logo").val()
    $(".container").html(`<div class="shadow-lg card mx-auto p-2 p-sm-3" style="width: 100%; max-width: 25rem; border-radius: 15px; max-height: auto; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: center; align-items: center; padding: 5px 10px; flex-shrink: 0;">
                              <div class="tetris-logo-container" style="animation: none; transform: scale(0.6);">
                                <div class="tetris-block size-1 purple-light" style="animation: none;"></div>
                                <div class="tetris-block size-2 purple-medium" style="animation: none;"></div>
                                <div class="tetris-block size-3-tall purple-dark" style="animation: none;"></div>
                                <div class="tetris-block size-4-v purple-light" style="animation: none;"></div>
                                <div class="tetris-block size-4-v purple-medium" style="animation: none;"></div>
                                <div class="tetris-block size-2-tall purple-dark" style="animation: none;"></div>
                              </div>
                            </div>
                            <div class="card-title mt-1 text-muted text-center" style="flex-shrink: 0;">
                                    <h5 class="mb-0"><strong>SELECCIONE LA BODEGA DE TRABAJO</strong></h5>
                              </div>
                            <div class="card-body" style="overflow-y: auto; max-height: 60vh; flex: 1;">
                              
                              <ul class="list-group" id="list-bodega"></ul>
                            </div>
                            <div class="card-foot" style="flex-shrink: 0;">
                              <div class="col text-center">
                                    <a class="btn btn-outline-danger btn-sm" href="login.php">
                                        <i class="fa-solid fa-arrow-left"></i> Volver
                                    </a>
                              </div>
                            </div>
                          </div>  `)

    if (resp.length == 0) {
      $("body").html(`<div class="alert alert-danger" role="alert">No se encontraron bodegas </div> <br><button class="btn btn-sm btn-outline-primary" onclick="regresar()"></button>`)
      return;
    }

    let li = '';

    resp.sort((a, b) => a.bodega.localeCompare(b.bodega));

    resp.forEach((element) => {
      li += `<li class="list-group list-group-item btn p-2 m-1 btn-outline-primary text-start" 
            onclick="seleccionarBodega('${element.bodega}','${element.descripcion}',${element.id} )">
            <i class="fa-solid fa-map-location-dot"></i> ${element.descripcion}</li>`
    });


    $("#list-bodega").html(li)
    dissminSwal();
  } catch (error) {
    dissminSwal();
    console.error(error)
    $("body").html(`<div class="alert alert-danger" role="alert">Error al cargar las bodegas</div> <br><button class="btn btn-sm btn-outline-primary" onclick="regresar()"></button>`)
    return;
  }
}

$(async function () {
  await listarBodegas();
})

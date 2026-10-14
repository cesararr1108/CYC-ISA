
const listarAlmacenes = async () => {
  try {

    const resp = await bodegasUsuarios();
    console.warn({ resp })
    if (resp.length == 0) {
      $("#result").html(ShowAlert('No se encontraron resultados', 'warning'))
      return;
    }
    let options = ``;
    resp.forEach((d) => {
      options += `<option value="${d.id}">${d.bodega} - ${d.descripcion}</option>`;
    });
    $("#bodegas").html(options);
    $("#bodegas").val($("#id_bodega_session").val())
  } catch (e) {
    console.error(e)
    $("#result").html(ShowAlert('Error interno de la aplicación', 'error'))
  }
}
const usuarios = async () => {

  try {
    const resp = await enviarPeticion({
      link: '../models/usuarios.php',
      filtro: '',
      op: 's_usuario_all',
      estado: 'A',
    })

    let p = ``;

    resp.data.forEach(element => {
      p += `<option value="${element.id}">${element.usuario} ( ${element.nombres} )</option>`
    });
    $("#usuarios").html(p).select2({
      theme: 'bootstrap-5',
      allowClear: true
    });
    //$('#seleccionar-totos').trigger("click")
    console.log({ resp })
  } catch (error) {
    console.error(error)
  }
}

const actualizarConteo = (conteo, usuario, bodega, ob) => {

  enviarPeticion({
    link: '../models/UsuariosTomaFisica.php',
    op: 'update',
    conteo,
    usuario,
    bodega
  })
    .then(resp => {
      if (resp.ok) {
        if (ob != null)
          $(ob).parent().find("td.td-conteo").html('')
        $(ob).html(`<i class="fa-solid fa-circle-check text-success text-center"></i>`)
      }
    })
    .catch(e => {
      console.error(e)
      Swal.fire("Error", "error al actualizar el estado", "error")
    })
}

const eliminarUsuario = (id_usuario, id_bodega) => {
  //confirm de swal 
  Swal.fire({
    title: '¿Está seguro de eliminar el usuario?',
    text: "No se podrá deshacer la acción",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(async (result) => {
    if (result.isConfirmed) {
      // Aquí va tu lógica de eliminación
      console.log('Usuario eliminado');

      const res = await enviarPeticion({
        link: '../models/UsuariosTomaFisica.php',
        op: 'elimnar-usuario',
        id_usuario,
        id_bodega
      })
      if (res.ok) {
        Swal.fire("Eliminado", "Usuario eliminado correctamente", "success")
        listarUsuarios();
        usuarios()
      } else {
        Swal.fire("Error", "Error al eliminar el usuario", "error")
      }
    }
  })
}

const listarUsuarios = async () => {
  const bodega = $("#bodegas").val()
  const resp = await enviarPeticion({
    link: '../models/UsuariosTomaFisica.php',
    op: 'listar',
    bodega
  });

  if (resp.length == 0) {
    $("#result").html(`<p class="text-center text-muted  ">No se encontraron usuarios</p>`);
    return;
  }
  let tab = `
        <table class="table table-sm table-striped table-hover table-bordered usuarios" id="tabla-usuarios" >
        <thead class="table-primary">
            <tr>
                <th>Usuario</th>
                <th>Bodega</th>
                <th>Conteo 1</th>
                <th>Conteo 2</th>
                <th>Conteo 3</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
    `
  resp.forEach(usuario => {
    let conteo1 = ''
    let conteo2 = ''
    let conteo3 = ''
    switch (usuario.conteo) {
      case "1":
        conteo1 = `<i class="fa-solid fa-circle-check text-success text-center"></i>`
        break;
      case "2":
        conteo2 = `<i class="fa-solid fa-circle-check text-success text-center"></i>`
        break;
      case "3":
        conteo3 = `<i class="fa-solid fa-circle-check text-success text-center"></i>`
        break;
    }

    tab += `<tr>
                <td>${usuario.usuario}</td>
                <td>${usuario.bodega}</td>
                <td data-id_usuario=${usuario.id_usuario} data-id_bodega=${usuario.id_bodega} onclick="actualizarConteo(1,${usuario.id_usuario},${usuario.id_bodega},this)" class="td-conteo text-center td-conteo-1">${conteo1}</td>
                <td data-id_usuario=${usuario.id_usuario} data-id_bodega=${usuario.id_bodega} onclick="actualizarConteo(2,${usuario.id_usuario},${usuario.id_bodega},this)" class="td-conteo text-center td-conteo-2">${conteo2}</td>
                <td data-id_usuario=${usuario.id_usuario} data-id_bodega=${usuario.id_bodega} onclick="actualizarConteo(3,${usuario.id_usuario},${usuario.id_bodega},this)" class="td-conteo text-center td-conteo-3">${conteo3}</td>
                <td><button class="btn btn-outline-danger btn-sm" onclick="eliminarUsuario(${usuario.id_usuario},${usuario.id_bodega})"> <i class="fa fa-trash" ></i></button></td>
        </tr>`
  })
  tab += `</tbody>
    </table>`
  $("#result").html(tab);

  $(".btn-conteo-1").on('click', function () {
    $("#tabla-usuarios tbody tr").each(function () {
      const tr = $(this);
      const id_usuario = tr.find("td").eq(2).data("id_usuario");
      const id_bodega = tr.find("td").eq(2).data("id_bodega");
      actualizarConteo(1, id_usuario, id_bodega, null);

    })
    listarUsuarios();
    usuarios()
  })


  $(".btn-conteo-2").on('click', function () {
    $("#tabla-usuarios tbody tr").each(function () {
      const tr = $(this);
      const id_usuario = tr.find("td").eq(3).data("id_usuario");
      const id_bodega = tr.find("td").eq(3).data("id_bodega");
      actualizarConteo(2, id_usuario, id_bodega, null);
      //tr.find("td").eq(3).html(`<i class="fa-solid fa-circle-check text-success text-center"></i>`)
    })
    listarUsuarios();
    usuarios()
  });


  $(".btn-conteo-3").on('click', function () {
    $("#tabla-usuarios tbody tr").each(function () {
      const tr = $(this);
      const id_usuario = tr.find("td").eq(4).data("id_usuario");
      const id_bodega = tr.find("td").eq(4).data("id_bodega");
      actualizarConteo(3, id_usuario, id_bodega, null);
      //tr.find("td").eq(3).html(`<i class="fa-solid fa-circle-check text-success text-center"></i>`)
    })
    listarUsuarios();
    usuarios()
  })

  $(".usuario").DataTable()
}
$(async function () {
  usuarios()
  await listarAlmacenes();
  listarUsuarios();


  $('#seleccionar-totos').on('click', function () {
    if ($(this).is(':checked')) {
      // Seleccionar todas las opciones
      let allValues = [];
      $('#usuarios option').each(function () {
        allValues.push($(this).val());
      });

      $('#usuarios').val(allValues).trigger('change');
    } else {
      // Deseleccionar todas
      $('#usuarios').val(null).trigger('change');
    }
  });
  $("#bodegas").change(function () {
    listarUsuarios();
  })
  $("#agregar").click(async function () {
    showLoadingWithTetrisLogo('Agregando usuarios', 'ISA Software')
    const usuarios = $("#usuarios").val();
    console.log({ usuarios })
    if (usuarios.length == 0) {
      dissminSwal();
      Swal.fire("Ups", "Debe seleccionar un usuario", "error")
      return;
    }

    try {
      const resp = await enviarPeticion({
        //   UsuriosTomaFisica
        link: '../models/UsuariosTomaFisica.php',
        op: 'add-usuarios',
        usuarios: JSON.stringify(usuarios),
        conteo: 1,
        bodega: $("#bodegas").val()
      })
      console.log({ resp })
      if (!resp.ok) {
        dissminSwal();
        Swal.fire("Ups", resp.message, "error")
        return;
      }

      listarUsuarios();
      usuarios()
      $("#seleccionar-totos").attr("checked", false)

      dissminSwal();

    } catch (error) {
      console.error(error)
      dissminSwal();
      Swal.fire("Ups", "Error al insertar los usuarios", "error")
    }

  })
  dissminSwal();
})
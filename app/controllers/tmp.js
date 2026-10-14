const id_modulo = '39';
let confMod;

let carrito_enc =[];
let carrito_det =[];
let permisos =[];


 const enviarFacturaElectronica =async (id,sw)=>{

    try {
      showLoadingSwalAlert2('<i class="fa-solid fa-paper-plane"></i>Enviando factura','Por favor espere!',false)
        const resp = await enviarDocumentoElctronico(id,5);
        console.log({resp})
        dissminSwal();
     
        if(resp.data.IsValid=='false' ){ 
          if(resp.data?.ErrorMessage){
             if( Array.isArray(resp.data.ErrorMessage)  ){
              const mensajes = generarListaHTML(resp.data.ErrorMessage);
                Swal.fire("Error",mensajes,"error");
             }else{
              Swal.fire("Error",resp.data.ErrorMessage,"error");
             }
          }else{
            console.error(resp.data.ErrorMessage)
          }
          return;
        }
         Swal.fire("Ok","Documento enviado correctamente","success")
        await listarFacturas();
       

    } catch (error) {
      dissminSwal();
      Swal.fire("Ups","Ha ocurrido un error al enviar el documento","error")
      console.error(error)
    }
 }

const reenviarMail = async (tipo, numero, email) => {
  Swal.fire({
    title: 'Reenviar correo',
    input: 'email',
    inputLabel: 'Dirección de correo',
    inputValue: email,
    showCancelButton: true,
    confirmButtonText: 'Enviar',
    cancelButtonText: 'Cancelar',
    inputValidator: (value) => {
      if (!value) {
        return 'Debes ingresar un correo válido';
      }
    }
  }).then(async (result) => {
    if (result.isConfirmed) {
      const nuevoEmail = result.value;
      if (!validarEmail(nuevoEmail)) {
        Swal.fire('Error', 'El email es inválido!', 'error');
        return;
      }

      // Mostrar loading
      Swal.fire({
        title: 'Enviando correo...',
        html: 'Por favor espera un momento',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      try {
        const resp = await enviarPeticion({
          link: '../models/Fe/Controllers/ReenviarCorreo.php',
          numero,
          tipo,
          nuevoEmail
        });
        Swal.close();

        if (resp.data.error) {
          Swal.fire('Error', resp.data.mensaje, 'error');
        } else {
          Swal.fire('Excelente!', resp.data.mensaje, 'success');
        }
      } catch (error) {
        // Cerrar loading en caso de error
        Swal.close();
        Swal.fire('Error', 'Ocurrió un error al enviar el correo', 'error');
        console.error('Error al reenviar correo:', error);
      }
    }
  });
}


//envio de documentos a la dian
  const listarFacturas =async ()=>{

    try {
 
      const fecha_inicial = $("#fe_fecha_inicial").val()
      const fecha_final   = $("#fe_fecha_final").val()
      const data_tipos    =  JSON.parse( $("#tipos option:selected").attr("data-element") );
      

       showLoadingSwalAlert2('<i class="fa-solid fa-magnifying-glass"></i>Consultando información','Por favor espere!',false)
       const resp= await enviarPeticion({
         link :'../models/Pos.php',
         op:'listar-facturas',
         fecha_inicial,
         fecha_final,
         id_tipo_documento : $("#tipos").val()
       });
       dissminSwal();
       console.log({
        resp
       })

       if(resp.length==0){
        $("#listado-facturas").html(`
             <div class="mt-3 alert alert-warning"><i class="fa-solid fa-triangle-exclamation"></i> No se encontrarón resultados!</div>
          `);
        return;
       }
      
        if(data_tipos.documento_electronico=='S'){
        tb =  `<table class="table table-sm" >
                  <thead>
                    <tr>
                      <th>Numero</th>
                      <th>Tipo</th>
                      <th>Fecha</th>
                      <th>Nit</th>
                      <th>Cliente</th>
                      <th>Valor total</th>
                      <th>Email</th>
                      <th>Estado</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody>`
        }else{
          tb =  `<table class="table table-sm" >
                <thead>
                  <tr>
                    <th>Numero</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Nit</th>
                    <th>Cliente</th>
                    <th>Valor total</th>
                    <th>Opc</th>
                  </tr>
                </thead>
                <tbody>`
        }

          resp.forEach(item=>{

             let opc=''; 
                          
              let estado=`<button class="btn btn-sm btn-outline-danger" onclick="enviarFacturaElectronica(${item.id},5)"><i class="fa-solid fa-paper-plane"></i></button>`
              
           if(data_tipos.documento_electronico=='S'){
  
              if(item.cufe !=''){
                  estado =  `<button class="btn btn-sm btn-outline-success" ><i class="fa-solid fa-circle-check text-success"></i></button>`;
                  opc = `<div class="btn-group ">
                    <button class="btn btn-sm btn-outline-primary"  onclick="ImprimirDocumento(5,${item.id},'Factura electronica de venta',false)"><i class="fa-solid fa-print"></i></button>
                    <button class="btn btn-sm btn-outline-secondary" data-element='${JSON.stringify(item)}'
                    onclick="reenviarMail('${item.tipo}',${item.numero},'${item.email}')"><i class="fa-solid fa-envelope"></i></button>
                  </div>`
                }
          
            }else{
                 estado='<i class="fa-regular fa-circle-check text-success"></i>';
                 opc=`<button class="btn btn-sm btn-outline-primary"  onclick="ImprimirDocumento(5,${item.id},'Factura de venta',false)"><i class="fa-solid fa-print"></i></button>`
            }
           // let btn = `<button class="btn btn-outline-primary btn-sm" onclick="ImprimirDocumento(5,${item.id},'Factura electronica de venta')"><i class="fa-solid fa-print"></i>Imprimir</button>`


            tb+=`<tr>
                    <td>${item.numero}</td>
                    <td>${item.tipo}</td>
                    <td>${item.fecha}</td>
                    <td>${item.nit}</td>
                    <td>${item.cliente}</td>
                    <td>${item.valor_total}</td>`;
             if(data_tipos.documento_electronico=='S'){
                tb+=`
                    <td>${item.email}</td>
                    <td align="center">${estado}</td> `;
              }
              tb+=`<td>${opc}</td>`;

            tb+=`</tr>`
          })
          tb+=`</tbody>
          </table>`
        $("#listado-facturas").html(tb);


    } catch (error) {
        console.error(error)
        dissminSwal();
    }
  }
// fin envio de documento a la dian 
 //**************************** */








 const actualizacionCantidadPedido =(data)=>{

    enviarPeticion({
      link:'../models/Facturacion.php',
      op:'update-cantidad',
      id_det: data.id_det,
      cantidad:data.new_can,
      can_und : data.can_und
    })
    .then(resp=>{
      console.log({resp})
      if(resp.ok){
        data.tr.find("td.carrito-cantidad-despachada").text(data.new_can);
        data.tr.find("td.carrito-total").text(formatNum(data.total,'$'))

        return;
      }
    })
    .catch(e=>{
      console.error(e)
    })
 }

 const agregarCantidadDespachada =async ()=>{
     
     const cantidad = isNaN(parseInt($("#cantidad_grid").val())) ? 0 :$("#cantidad_grid").val()

     if(cantidad>0){
      let existe = false;
      $("#carrito tbody tr").each(async function(){ 
         const tr =$(this);
         const codigo = $.trim(tr.find("td.carrito-codigo").text())

         if(codigo == $.trim($("#codigo_grid").val())){
            existe=true;
            const cantidad_despachada = $.trim(tr.find("td.carrito-cantidad-despachada").text())
            const cantidad_pedido = parseInt($.trim(tr.find("td.carrito-cantidad").text()));
            const new_can = parseInt(cantidad_despachada) + parseInt(cantidad);
            const id_det = $.trim(tr.find("td.carrito-id-detalle").html());
            const can_und = $.trim(tr.find("td.carrito-cantidad-und").text())

            if(!permisos.AgregarMayorCantidadPedida){

                if(new_can>cantidad_pedido){
                   dissminSwal();
                   limpiarReferencia();
                  Swal.fire("Ups","La cantidad despachada no puede ser mayor a la pedida","error")
                  return;
                }
            }
          
            const precio_neto = unformatNum(tr.find("td.carrito-precio-neto").text());
            const total = precio_neto *new_can ;
            const data={
              id_det ,
              cantidad , 
              precio_neto , 
              total , 
              tr ,
              new_can ,
              can_und
            }
             await actualizacionCantidadPedido(data);
             await limpiarReferencia();
             $("#filtro").focus()
             dissminSwal();
            return;
          } 
      });

      if(!existe){

          if(!permisos.FacturarPoductosDiferentes){
              //no tiene permisos para agregar productos diferentes a lo pedido
                Swal.fire("Error","No tienes permiso para agregar productos diferentes al pedido","error")
                refValida=false;
                return;  
          }
              //agrego el item a el pedido y actualizo la cantidad despachada y loa grego al carrito
              //inserto el detalle 
              let cantidad_despachada =0;
              if(ConfGeneral.ManejaFechaLote=='N' || (ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N')){
                  cantidad_despachada= $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").val())
              }
              const datosDetalle = await enviarPeticion({
                link: '../shared/servicios/servicios.php',
                op: 'add_pedido_detalle',
                id_pedido: $("#id_pedido").val(),
                id_referencia: $("#id_referencia").val(),
                cantidad: $("#cantidad_grid").val() * parseInt($("#und_grid option:selected").val()),
                valor_unitario: $("#precio_unitario").val(),
                costo_unitario: $("#costo_promedio").val(),
                pcj_iva: $("#iva_grid").val(),
                pcj_descuento: $("#dto_grid").val(),
                und: $.trim($("#und_grid option:selected").text().split("-")[0]),
                can_und: parseInt($("#und_grid option:selected").val()),
                sw: 8,
                id_bodega: $("#bodegas").val(),
                cantidad_desp: cantidad_despachada
              });

              if (datosDetalle.error) {
                dissminSwal()
                Swal.fire("Error", "No se pudo insertar el producto, por favor intente nuevamente!", "error")
                return;
              }

              const itemCart = {
                codigo : $("#codigo_grid").val(),
                descripcion : $.trim($("#descripcion_grid").val()),
                laboratorio : $.trim($("#laboratorio_grid").val()),
                preciob     :$("#preciob_grid").val(),
                dto         :$("#dto_grid").val(),
                iva         :$("#iva_grid").val(),
                precion     :$("#precion_grid").val(),
                cantidad    :$("#cantidad_grid").val() ,
                cantidad_despachada,
                und: $.trim($("#und_grid option:selected").text().split("-")[0]),
                can_und: parseInt($("#und_grid option:selected").val()),
                id : datosDetalle.data.id,
                valor_unitario : $("#precio_venta").val(),
                total : $("#total_grid").val()
              }
              addCart(itemCart);

              if(cantidad_despachada==0){
                //cargo el item 
                cargarItemGridLote()
              }
          }
      
       /// actualizarTotales() 
    }
 }


 const agregarProducto =async (cantidad,e)=>{

   if(isNaN( parseInt(cantidad)) || cantidad==0){
      return;
    }

   const totales = calcularTotal( unformatNum($("#preciob_grid").val()) ,
                                  $("#dto_grid").val(),
                                  $("#iva_grid").val(),
                                  cantidad);
 
   $("#total_grid").val(formatNum(totales.ttotal,'$'))

   //validacion del vendedor 
   const validacionVen =await validacionVendedor();

   if(!validacionVen){
     return;
   }

   if(e?.key=='Enter'){

    const refValida = validacionReferencia()

    if(!refValida){
      return;
    }
     
     showLoadingSwalAlert2(' <i class="fa-solid fa-circle-notch fa-spin fa-1x"></i> Guardando item','Por favor espere!',false);
     try{
 
         //
         const numero=$("#numero").val()
         const numero_pedido = $("#numero_pedido").val()
         let sw_add  =true;

         if(!isNaN(numero_pedido) && numero_pedido>0){ 
            agregarCantidadDespachada()
            return;
         }

        let cantidad_agregada = cantidad;
        let preciob           =  unformatNum($("#preciob_grid").val());
        let precion           =  unformatNum($("#precion_grid").val());
        let und_cargada       = $.trim($("#und_grid option:selected").text().split("-")[0]);
        let can_und_cargada   = parseInt($("#und_grid option:selected").val()); 
        let vencimiento       = $("#vcmto_grid").val();
        let lote              = $.trim($("#lote_grid").val());
        let total             = parseFloat(unformatNum($("#total_grid").val()));
        let opcion            = 0;
        let idDet             = 0;
        let opc ='add_transacciones_detalle_tmp'
         //VERIFICO SI EL PRODUCTO YA FUE AGREGADO   

         if($("#id_detalle").val()==0){

           $("#carrito tbody tr").each(async function(){

            const codigo = $.trim($(this).find("td").eq(0).html()).toUpperCase();

            if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){

                 const lote        = $.trim( $(this).find("td").eq(10).html() ).toUpperCase();
                 und_cargada       = $.trim( $(this).find("td").eq(7).html() ).toUpperCase();
                

                  if(und_cargada==$.trim($("#und_grid option:selected").text().split("-")[0])){
                    TextPromt ="Ya se encuentra agregada "+(cantidad_agregada)+" "+und_cargada+" , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
                  }else{
                    TextPromt ="Ya se encuentra agregada "+(cantidad_agregada)+" "+und_cargada+" , que desea hacer?  \n 2. Reemplazar.";
                  }
                    

                  if( codigo == $.trim($("#codigo_grid").val().toUpperCase() )
                  &&  lote   == $.trim($("#lote_grid").val()).toUpperCase() ){

                    sw_add            = false;
                    opcion            = prompt(TextPromt);
                    cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                    idDet             = $(this).find("td:last").html();
                    total_agregado    = unformatNum( $(this).find("td").eq(11).html() );
                   
                    if(opcion ==1){//sumar 

                      opc    = 'update_transacciones_detalle_tmp'
                      cantidad_agregada = cantidad_agregada + parseInt(  cantidad );
                      und_cargada       = $.trim( $(this).find("td").eq(7).html() ).toUpperCase();
                      can_und_cargada   = $(this).find("td").eq(8).html();
                      total_agregado    = (cantidad_agregada)*unformatNum($(this).find("td").eq(5).html()) ;
                      sw_add                = true;

                      // modifico los datos en las tablas 
                      $(this).find("td").eq(6).html(cantidad_agregada)
                      $(this).find("td").eq(11).html( formatNum(total_agregado,'$')); 
                     
                    }else if(opcion==2){ // reemplazar
                        opc               =  'update_transacciones_detalle_tmp'
                        sw_add            =  true
                        cantidad_agregada = $("#cantidad_grid").val();
                        und_cargada       = $.trim($("#und_grid option:selected").text().split("-")[0]);
                        can_und_cargada   = parseInt($("#und_grid option:selected").val()); 
                        total_agregado    = (cantidad_agregada)*unformatNum($(this).find("td").eq(5).html()) ;
                        // // modifico los datos en las tablas 
                        $(this).find("td").eq(2).html(formatNum(preciob,'$'));
                        $(this).find("td").eq(5).html(formatNum(precion,'$'));
                        $(this).find("td").eq(6).html(cantidad_agregada);
                        $(this).find("td").eq(7).html(und_cargada);
                        $(this).find("td").eq(8).html(can_und_cargada);
                        $(this).find("td").eq(11).html( formatNum(total_agregado,'$')); 
                    }
                  }
                }else{
                  // no maneja fecha lote 
                    if(codigo == $.trim($("#codigo_grid").val()).toUpperCase() ){
                      cantidad_agregada = parseInt($(this).find("td").eq(6).html());
                      idDet             = $(this).find("td:last").html();
                      und_cargada       = $.trim( $(this).find("td").eq(7).html() ).toUpperCase();
                      sw_add                = false;
                      total_agregado    = unformatNum( $(this).find("td").eq(9).html() );

                      if(und_cargada==$.trim($("#und_grid option:selected").text().split("-")[0])){
                        TextPromt ="Ya se encuentra agregada "+(cantidad_agregada)+" "+und_cargada+" , que desea hacer? \n 1. Sumar al actual.  \n 2. Reemplazar.";
                      }else{
                          TextPromt ="Ya se encuentra agregada "+(cantidad_agregada)+" "+und_cargada+" , que desea hacer?  \n 2. Reemplazar.";
                      }
                    
                      opcion = prompt(TextPromt);           
                      
                      if(opcion ==1){//sumar
                        opc     = 'update_transacciones_detalle_tmp'
                        cantidad_agregada  = cantidad_agregada + parseInt(cantidad); 
                        und_cargada        = $.trim( $(this).find("td").eq(7).html() ).toUpperCase();
                        can_und_cargada    = $(this).find("td").eq(8).html();
                        sw_add                 = true
                        total_agregado     = (cantidad_agregada)*unformatNum($(this).find("td").eq(5).html()) ;
                        //

                      // modifico los datos en las tablas 
                      $(this).find("td").eq(6).html(cantidad_agregada)
                      $(this).find("td").eq(9).html( formatNum(total_agregado,'$')); 

                      }else if(opcion==2){ //Reemplazar
                        opc    = 'update_transacciones_detalle_tmp'
                        sw_add                =  true
                        cantidad_agregada = cantidad;
                        und_cargada       = $.trim($("#und_grid option:selected").text().split("-")[0]);
                        can_und_cargada   = parseInt($("#und_grid option:selected").val()); 
                        total_agregado    = (cantidad_agregada)*unformatNum($(this).find("td").eq(5).html()) ;
                        // // modifico los datos en las tablas 
                        $(this).find("td").eq(2).html(formatNum(preciob,'$'));
                        $(this).find("td").eq(5).html(formatNum(precion,'$'));
                        $(this).find("td").eq(6).html(cantidad_agregada);
                        $(this).find("td").eq(7).html(und_cargada);
                        $(this).find("td").eq(8).html(can_und_cargada);
                        $(this).find("td").eq(9).html( formatNum(total_agregado,'$')); 
                      }
                    }
                 }
 
            });
         }else{
           opc    = 'update_transacciones_detalle_tmp'
           idDet  = $("#id_detalle").val()

           if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(6).html(cantidad_agregada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(7).html(und_cargada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(8).html(can_und_cargada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(11).html( formatNum(total,'$')); 
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob,'$'));
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(5).html(formatNum(precion,'$'));
             
           }else{
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(6).html(cantidad_agregada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(7).html(und_cargada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(8).html(can_und_cargada);
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(9).html( formatNum(total,'$')); 
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(2).html(formatNum(preciob,'$'));
              $("#id_detalle"+$("#id_detalle").val()).find("td").eq(5).html(formatNum(precion,'$'));
             
           }
          
         }
    
         if(!sw_add){
           dissminSwal()
           return;
         }

         let datosDetalle;
           datosDetalle ={
              link              : '../shared/servicios/servicios.php',
              op                : opc,
              id_transaccion    : $("#id_transaccion").val(),
              id_referencia     : $("#id_referencia").val(),
              cantidad          : cantidad_agregada*parseInt($("#und_grid option:selected").val()),
              valor_unitario    : $("#precio_unitario").val(),
              costo_unitario    : $("#costo_promedio").val(),
              id_lote           : $("#id_lote").val(),
              pcj_iva           : $("#iva_grid").val(),
              pcj_descuento     : $("#dto_grid").val(),
              und               : und_cargada,
              can_und           : parseInt(can_und_cargada),
              vmcto             : vencimiento,
              lote              : lote,
              sw                : 5 ,
              id_bodega         : $("#bodegas").val(),
              id_pedido         : 0,
              id                :idDet
            }


         if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){
            //inserto el detalle 
            datosDetalle.id_lote = $("#id_lote").val();
            datosDetalle.vmcto = vencimiento;
            datosDetalle.lote = lote;

         }
         const resDet = await enviarPeticion(datosDetalle);

         if(resDet.error){
           dissminSwal()
           Swal.fire("Error","No se pudo insertar el producto, por favor intente nuevamente!","error")
           return;
         }
         let itemCart = {};
 
         itemCart.id= resDet.data.id
         itemCart.codigo     = $("#codigo_grid").val();
         itemCart.descripcion= $("#descripcion_grid").val();
         itemCart.laboratorio= $("#laboratorio_grid").val();
         itemCart.preciob    = $("#preciob_grid").val();
         itemCart.dto=$("#dto_grid").val();
         itemCart.iva=$("#iva_grid").val();
         itemCart.precion=$("#precion_grid").val();
         itemCart.cantidad=cantidad_agregada;
         itemCart.und=und_cargada;
         itemCart.can_und = can_und_cargada;
         addCart.valor_unitario=$("#precio_unitario").val()
         if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){
          
            itemCart.vcmto= vencimiento
            itemCart.lote=lote
         }
         
         itemCart.total=formatNum( total ,'$')

         if(opc=='add_transacciones_detalle_tmp'){
            await addCart(itemCart)
         }
        limpiarReferencia()
         await actualizarTotales(resDet.data.totales[0])
         await dissminSwal();
        setTimeout(function(){
          $("#filtro").focus()
        },500)
     }catch(e){
       dissminSwal()
       Swal.fire("Error","Se genero un error interno en la aplicación","error")
       console.error(e)
     }
 
   }
 }

$(async function(){
  await setDateNow(['fecha_inicial','fecha_final','fecha_inicial_pedido','fecha_final_pedido'])
  await listarAlmacenes();
  await condicionesPago('C','id_condicion',noContado);

  const preferencias = await preferenciasUsuario(id_modulo);

   if(preferencias.CheckearBarras=='S'){
      $("#cod_barras").attr("checked", true)
   }else{
    $("#cod_barras").attr("checked",false)
   }
  
  let soloStock = false;
  if(preferencias.SoloProductoSock=='S'){
     soloStock=true;
   }


   const modalBusquedaRef = new ModalBusquedaProducto((rowData) => {
      console.log({
         rowData
      })
      if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){
        cargarLote(rowData);
      }else{
          cargarItemGrid(rowData);
      }
    
   },{check_stock:soloStock,cod_barra: $("#cod_barras").is(":checked")?1:0});

   $("#cod_barras").change(function(){

        const opcion = $(this).is(":checked")? 'S' : 'N';
    console.log({opcion})
        enviarPeticion({
           link :'../shared/servicios/servicios.php',
           op:'cambiar-preferencias',
           CheckearBarras : opcion
        })
        .then(resp=>{
          console.log({resp})
        })
        .catch(err=>{
          console.error({err})
        })
   })

        

$("#filtro").on('keyup',async function(e){

  filtro=  $.trim($(this).val())
  
  if(filtro.length==0){
    return;
  }

 if(e.key=='Enter'){

    const resp = await  modalBusquedaRef.getRecords(filtro);
    if(modalBusquedaRef.getNrecords()>0){
      if(modalBusquedaRef.getNrecords()==1){
        cargarItemGrid(resp[0]);
        $("#cantidad_grid")
        .val(1)
        .trigger(
          jQuery.Event("keyup", {
            key: "Enter",
            keyCode: 13,
            which: 13
          })
        );
      }else{
        modalBusquedaRef.show();
      }
      
    }
  }
   
});

$("#btnBusquedaAvanzada").click(function(){
  modalBusquedaRef.show();
  $("#busqueda-descripcion").val(filtro);
  setTimeout(() => {
    $("#busqueda-descripcion").focus(); 
  }, 200);
});



  await tiposDocumentos(5,$("#bodegas").val(),'tipos');

     permisos = await  permisosUsuario(id_modulo)
     const {general,modulos} = await cargarConfiguraciones(); 
     ConfGeneral=general;
     ConfModulos=modulos;

     /** */
     if(permisos.CargarPedido){
      $("#div_Btnpedidos").html(`
        <button type="button" class="btn btn-sm btn-outline-danger" id="Btnpedidos"><i class="fa-solid fa-circle-down"></i> Cargar pedido</button>  
      `)
     }
     /** */
   /**fecha y lotes */

  if(ConfGeneral){

    if(ConfGeneral.ManejaFechaLote=='S' && ConfGeneral.ManejaFIFO=='N'){
      tb_ref = `
    
      `
      $("#dvReferencias").html(`

          `)
    }else{
      tb_ref=`
      
          <table  width="100%" class=" table-data-referencia" cellpadding="2" cellspacing="0" >
              <thead>
                <tr>
                  <th><b>Código</b></th>
                  <th><b>Precio b</b></th>
                  <th><b>Dto</b></th>
                  <th><b>Iva</b></th>
                  <th><b>Precio n.</b></th>
                  <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                  <th><b>Cant</b></th>
                  <th><b>Total</b></th>
                </tr>
                <tr>
                  <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly></td>
                  <td><input type="number" min="1"  class="form-control form-control-sm table-primary"data-grid id="cantidad_grid" onkeyup="agregarProducto(this.value,evenet)" ></td>
                  <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                </tr>
              </thead>
           </table>
      `
      $("#dvReferencias").html(`
              <table class="table table-sm display nowrap" style="width: 100%" width="100%" id="carrito">
                  <thead class="table-primary" >
                      <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Precio b</th>
                        <th  class="text-center">Dcto</th>
                        <th  class="text-center">Iva</th>
                        <th>Precio n</th>
                        <th  class="text-center">Cant</th>
                        <th  class="text-center">Und</th>
                        <th  class="text-center">Conv</th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                      </tr>
                    </thead>
                  <tbody>
                  </tbody>
            </table>  
          `)
    }
    $(".div-table-data-referencia").html(tb_ref);
  }


   $("#consultar-fe").click(function(){
      listarFacturas()
   })
   
   $("#nuevo").click(async function(){
      nuevoPedido();
      $("#carrito tbody tr").remove()
   })
 
   $("#notas").focus(function(){
     $("#tmp").val($(this).val())
   })
   

  $("#guardar").on('click',async function(){

    let idFactura = 0;
    const id_transaccion = $("#id_transaccion").val()
    try {
        if( isNaN(parseInt($("#numero").val())) || $("#numero").val()==0 ){
          Swal.fire("Error","No se encontro un numero de documento","warning");
          return;
        }
        
        const respDoc= await enviarPeticion({
              link :'../shared/servicios/servicios.php',
              op:'volcar_documento',
              id_transaccion,
              updateVendedor:ConfModulos.solicitarAlGuardar,
              vendedor:$("#vendedor").val()
        });

        if(!respDoc.ok){
          Swal.fire("error",respDoc.message,"error");
          dissminSwal()
          return;
        }
        idFactura = respDoc.id;
      
    } catch (error) {
        console.error(error);
        dissminSwal()
        Swal.fire("Ups","Se genero un error al guardar el documento","error")
        return;
    }

      //envio a la DIAN 
      const data_tipos= JSON.parse( $("#tipos option:selected").attr("data-element") );

      dissminSwal()
      nuevoPedido();
      ImprimirDocumento(5,idFactura,'Impresion de pedido de venta',false);
      const respDian =  await enviarDocumentoElctronico(idFactura,5);
      console.log({
        respDian
      })
   });
 
   $("#notas").on('focusout',async function(){
 
     try{
 
       const nota =$.trim($(this).val());
       let sw=true
       if($("#id_transaccion").val()>0){
         sw=false
       }  
  
       if(sw){
          if(nota.length==0){
            return;
          }
       }
  
       const resp = await enviarPeticion({
          link :'../shared/servicios/servicios.php',
          op:'update_encabezado_transaccion',
          id:$("#id_transaccion").val(),
          notas:$("#notas").val()
       })
  
       if(!resp.ok){
          Swal.fire("Error","Error al actualizar el registro","error");
          return;
       }
       $("#tmp").val("");
 
     }catch(e){
       console.error(e)
       $(this).val($("#tmp").val())
     }
 
   });
 
   $("#buscar-temporal").on('click',function(){
     buscarTemporales();
   })
 
   $('input[type="number"]').keydown(function(e) {
     // Evitar que las flechas de dirección cambien el valor
     if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
       e.preventDefault();
     }
   });
 

   
  $("#und_grid").on('keyup',function (e) { 
     e.preventDefault();

     if(e.key=='ArrowUp' || e.key=='ArrowDown'){

        if($("#codigo_grid").val()!=''){

          const datosPrecio = calcularPrecioUnidad(  $("#precio_unitario").val(),
                                                  $("#und_grid option:selected").val(),
                                                  $("#dto_grid").val(),$("#iva_grid").val())

            $("#preciob_grid").val(formatNum(datosPrecio.preciob,'$'))
            $("#precion_grid").val(formatNum(datosPrecio.precion,'$'));

            const stock =mostrarStockDisponible($("#stock_real").val(),
                                                $("#und_grid option:selected").val(),
                                                $("#und_grid option:selected").text().split("-")[0])
            $("#stock_grid").val(stock);

            const totales = calcularTotal( unformatNum($("#preciob_grid").val()) ,
                                          $("#dto_grid").val(),
                                          $("#iva_grid").val(),
                                          $("#cantidad_grid").val());
            $("#total_grid").val(formatNum(totales.ttotal,'$'))

        }
     }

  });
 
  $("#und_grid").on('keyup',function (e) { 
     if(e.key=='Enter'){
      if($("#codigo_grid").val()!=''){
        $("#cantidad_grid").focus().select();
      }
     }
  });  
 

  $('#txtBusquedaTercero').keyup(function(){
    tercero =$.trim($(this).val())
  
      if(tercero.length==0){
  
        $('#tabs button[data-bs-target="#navs-creacion"]').attr('disabled',true);
        limpiarTercero();
        return;
      }
  });

  $("#cliente_pedido").keyup(function(){
      if($(this).val().length==0){
      $("#nit_tercero_pedido").val(0)
      }
  })
     $('#cliente_pedido').autocomplete({
         source: async function (request, response) {
           const data = {
             link: '../models/Terceros.php',
             filtro: $.trim(request.term),
             tipo_tercero: '',
             estado: 'A',
             op: 's_tercero'
           };
           try {
             const resp = await enviarPeticion(data);
             let datos = resp.data;
      
             if (Array.isArray(datos)) {
               response(datos);
             } else {
               response([]);
             }
           } catch (error) {
             console.error("Error en la solicitud:", error);
             response([]);
           }
         },
         minLength: 3,
         select:async  function (event, ui) {

           item = ui.item;
           
          $("#nit_tercero_pedido").val(item.nit);
         }
       });
 
     $('#txtBusquedaTercero').autocomplete({
         source: async function (request, response) {
           const data = {
             link: '../models/Terceros.php',
             filtro: $.trim(request.term),
             tipo_tercero: '',
             estado: 'A',
             op: 's_tercero'
           };
           try {
             const resp = await enviarPeticion(data);
             let datos = resp.data;
      
             if (Array.isArray(datos)) {
               response(datos);
             } else {
               response([]);
             }
           } catch (error) {
             console.error("Error en la solicitud:", error);
             response([]);
           }
         },
         minLength: 3,
         select:async  function (event, ui) {

           item = ui.item;
           
          if(item.id_condicion ==0){
            Swal.fire("Error","El tercero no tiene condicion de pago para proveedor","error");
            return;
          }
           cargarTercero(item)
         }
       });

       $('#txtBusquedaTerceroTmp').autocomplete({
        source: async function (request, response) {
          const data = {
            link: '../models/Terceros.php',
            filtro: $.trim(request.term),
            tipo_tercero: '',
            estado: 'A',
            op: 's_tercero'
          };
          try {
            const resp = await enviarPeticion(data);
            let datos = resp.data;
      
            if (Array.isArray(datos)) {
              response(datos);
            } else {
              response([]);
            }
          } catch (error) {
            console.error("Error en la solicitud:", error);
            response([]);
          }
        },
        minLength: 3,
        select: async function (event, ui) {
          console.log({ui})
          $("#nit_tmp").val(ui.item.nit)
        }
      })
     

    $("#Btnpedidos").click(function(){

          $("#modalPedidos").modal("show");
    })
    $("#listar-pedidos").click(async function(){
       const nit            = $("#nit_tercero_pedido").val()
       const fecha_inicial  = $("#fecha_inicial_pedido").val()
       const fecha_final    = $("#fecha_final_pedido").val()

      showLoadingSwalAlert2('Buscando pedidos ','Por favor espere!')
       try {

          const pedidos = await enviarPeticion({
              link : '../shared/servicios/servicios.php',
              op:'busqueda_pedido',
              fecha_inicial,
              fecha_final,
              nit ,
              sw:8,
              estado:'P'
          }) ;
 

          if(pedidos.data.length==0){
            dissminSwal()
            $("#result-pedidos").html(`
              <div class="alert alert-danger">No se encontrarón resultados!</div>  
            `);
            return;
          }


          let tb = `<table class="table table-sm table-striped">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Numero</th>
                          <th>Cliente</th>
                          <th>Razón comercial</th>
                          <th>Condición</th>
                          <th>Valor total</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>`
          pedidos.data.forEach(element => {
              tb+=`
                <tr>
                  <td>${element.fecha_hora}</td>
                  <td>${element.numero}</td>
                  <td>${element.nit}</td>
                  <td>${element.nombres}</td>
                  <td>${element.razon_comercial}</td>
                  <td>${element.condicion}</td>
                  <td>${formatNum(element.valor_total, '$')}</td>
                  <td><button class="btn btn-sm btn-outline-primary cargar-pedido" data-element='${JSON.stringify(element)}' > <i class="fa-solid fa-circle-down"></i> Cargar  </button></td>
                </tr>
              `
          });
          
          tb+=`</tbody></table>`
          $("#result-pedidos").html(tb)

          $(".cargar-pedido").on('click',async function(){

              try {
                const encabezado =JSON.parse($.trim($(this).attr("data-element")));
           
                //cargo el cliente 
                if(isNaN( parseInt(encabezado.id_tercero) )  || encabezado.id_tercero==0){
                  Swal.fire("Error","No se detecto el id del tercero","error")
                  return;
                }
                showLoadingSwalAlert2('Cargando pedido','Por favor espere')
                const dataTercero = await enviarPeticion({
                  link :'../models/Terceros.php',
                  id_tercero:encabezado.id_tercero,
                  filtro:'',
                  op:'s_tercero',
                  tipo_tercero:''
                });

                const tercero = dataTercero.data[0];
                const cargaTercero = await cargarTercero(tercero);
          
                if(!cargaTercero.sw){
                  return;
                }
                $("#id_condicion").val(encabezado.condicion)
                $("#bodegas").val(encabezado.id_bodega)
                $("#id_tercero_dir").val(encabezado.id_tercero_dir)
                $("#numero_pedido").val(encabezado.numero)
                $("#id_pedido").val(encabezado.id_pedido)
                const id = encabezado.id_pedido;


                if($("#numero_pedido").val()!=''){
                  tb_ref=`
                        
                            <table  width="100%" class=" table-data-referencia" cellpadding="2" cellspacing="0" >
                                <thead>
                                  <tr>
                                    <th><b>Código</b></th>
                                    <th><b>Precio b</b></th>
                                    <th><b>Dto</b></th>
                                    <th><b>Iva</b></th>
                                    <th><b>Precio n.</b></th>
                                    <th rowspan="2"> <select class="form-control p-0 data-grid" id="und_grid" style="height: 60px;width: 120px;"  multiple ></select></th>
                                    <th><b>Cant</b></th>
                                    <th><b>Total</b></th>
                                  </tr>
                                  <tr>
                                    <td><input type="text" size="5"  class="form-control form-control-sm data-grid " id="codigo_grid" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm data-grid" id="preciob_grid" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm data-grid" id="dto_grid" size="4" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm data-grid" id="iva_grid" size="4" readonly></td>
                                    <td><input type="text" class="form-control form-control-sm data-grid" id="precion_grid" readonly></td>
                                    <td><input type="number" min="1"  class="form-control form-control-sm table-primary" data-grid id="cantidad_grid" onkeyup="agregarProducto(this.value,event)" ></td>
                                    <td><input type="text" class="form-control form-control-sm data-grid" id="total_grid" readonly></td>
                                  </tr>
                                </thead>
                            </table>
                        `
                        $("#dvReferencias").html(`
                                <table class="table table-sm display nowrap" style="width: 100%" width="100%" id="carrito">
                                    <thead class="table-primary" >
                                        <tr>
                                          <th>Código</th>
                                          <th>Descripción</th>
                                          <th>Precio b</th>
                                          <th  class="text-center">Dcto</th>
                                          <th  class="text-center">Iva</th>
                                          <th>Precio n</th>
                                          <th  class="text-center table-danger">Cant ped.</th>
                                          <th  class="text-center table-success">Cant desp.</th>
                                          <th  class="text-center">Und</th>
                                          <th  class="text-center">Conv</th>
                                          <th>Total</th>
                                          <th></th>
                                        </tr>
                                      </thead>
                                    <tbody>
                                    </tbody>
                              </table>  
                            `)
                      $(".div-table-data-referencia").html(tb_ref);
                }

                const detalle =await  enviarPeticion({
                  link :'../shared/servicios/servicios.php',
                  id,
                  op: "detalle_pedido"
              
                });
              
                detalle.forEach(item=>{
                
                    carrito_det= {
                      id: item.id_detalle,
                      codigo:item.codigo,
                      descripcion:item.descripcion,
                      laboratorio: item.laboratorio,
                      dto:item.pcj_descuento,
                      iva:item.iva,
                      preciob: formatNum(Math.round((item.valor_unitario*item.can_und)/100)*100,'$'),
                      precion: formatNum( Math.round(((item.valor_unitario*(1-(item.pcj_descuento/100)))*((item.iva/100)+1)*item.can_und)/100)*100,'$'),
                      cantidad:item.cantidad,
                      und:item.und ,
                      can_und :item.can_und,
                      cantidad_despachada: item.cantidad_desp,
                      total: formatNum( Math.round(  (((item.valor_unitario*(1-(item.pcj_descuento/100)))*((item.iva/100)+1)*item.can_und)*item.cantidad)  /100)*100,'$'),
                      valor_unitario : item.valor_unitario
                    };
                    addCart(carrito_det)
                })
                

                $("#modalPedidos").modal("hide")
                dissminSwal();

              }catch (error) {
                dissminSwal()
                console.error({error})
                Swal.fire("Error","Error al cargar el detalle del pedido","error")
              }
          })
          dissminSwal();
       } catch (error) {
        dissminSwal()
          Swal.fire("Errpr","Error al consultar los pedidos","error")
          console.error(error)
       }
    })
     
     $("#txtBusquedaTercero").focus();
     dissminSwal();

  $(".container-fluid").removeClass("d-none")
});





 
 
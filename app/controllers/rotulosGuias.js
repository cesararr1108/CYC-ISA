$(async function () {
     await tiposDocumentos(5, $("#id_bodega_session").val(), 'slcTipoFact');
     $("#btnImprimir").click(async function () {

          const tipo = $("#slcTipoImp").val();
          const tipoFact = $("#slcTipoFact option:selected").text();
          const NumFact = $("#numFact").val();
          const cajas = $("#cantCajas").val();
          const notas = $("#notas").val();

          if (!tipoFact || !NumFact) {
               Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Debe seleccionar el tipo y número de factura'
               });
               return;
          }
          const data = {
               link: '../models/rotulosGuias.php',
               op: 'validar-factura',
               tipo: tipoFact,
               numero: NumFact
          };

          try {
               const resp = await enviarPeticion(data);
               // Si viene como texto convertirlo a JSON
               const resultado = typeof resp === 'string'
                    ? JSON.parse(resp)
                    : resp;

               if (resultado.respuesta === true) {
                    const iframeSrc = `../pdf/guias-rotulos.php?tipo=${tipo}&tipoFact=${tipoFact}&numFact=${NumFact}&cajas=${cajas}&notas=${notas}`;
                    $("#mdRotuloBody").html(`  <iframe width="100%" style="height:400px;" src="${iframeSrc}" frameborder="0"></iframe>`);
                    $("#mdRotulo").modal('show');
               } else {
                    Swal.fire({
                         icon: 'error',
                         title: 'Factura no válida',
                         text: resultado.mensaje || 'La factura no existe o está anulada'
                    });
               }

          } catch (e) {
               console.error(e);
               Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No fue posible validar la factura'
               });

          }


     });
     dissminSwal();
});
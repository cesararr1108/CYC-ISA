const consultar = async () => {

    showLoadingWithTetrisLogo('Buscando información', 'ISA Software')
    const dato = $.trim($("#busqueda-descripcion").val())

    const resp = await enviarPeticion({
        link: '../models/RptVencimientos.php',
        dato,
        marca: $("#marcas").val(),
        institucional: $("#busqueda-institucional").is(":checked") ? 1 : 0,
        regulado: $("#busqueda-regulado").is(":checked") ? 1 : 0,
        controlado: $("#busqueda-controlado").is(":checked") ? 1 : 0,
    });

    if (resp.length == 0) {
        dissminSwal();
        Swal.fire("Ups", "No se encontraron resultados", "info")
        return;
    }

    $("#result").html(`<table id="productos" class="table table-sm table-borderless table-hover">
                          <thead class="table-primary"></thead> 
                      </table>`)
    createDataTable('productos', resp,
        ['codigo', 'descripcion', 'marca', 'lote', 'vencimiento', 'meses', 'stock', 'costo'],
        ['Codigo', 'Descripcion', 'Marca', 'Lote', 'Vencimiento', 'Meses rest.', 'Stock', 'Costo'],
        null, null, false, false, '', true,
        ['costo'], 'COP',
        [],
        ['costo'])
    dissminSwal();

}
const listarMarcas = async () => {
    const resp = await enviarPeticion({
        link: '../models/Marcas.php',
        op: 'listar',

    })
    let p = '<option value="0">Todas</option>';

    resp.data.forEach(element => {
        p += `<option value="${element.id}">${element.descripcion}</option>`
    });
    $("#marcas").html(p)
}
$(function () {
    listarMarcas();
    $("#btn-busqueda").click(function () {
        consultar();
    });
    dissminSwal();
});

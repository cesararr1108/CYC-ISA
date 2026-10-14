
const usuarios = async () => {
    const usuarios = await enviarPeticion({
        link: '../models/RptSeguimientoCajas.php',
        op: 'usuarios',
    });
    let p = '<option value="0">Todos</option>';
    usuarios.forEach(element => {
        p += `<option value="${element.id}">${element.usuario}</option>`
    });
    $("#usuarios").html(p);
}
const consultar = async () => {
    showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software')
    try {
        const resp = await enviarPeticion({
            link: '../models/RptSeguimientoCajas.php',
            op: 'consultar',
            fecha_inicial: $("#fecha_inicial").val(),
            fecha_final: $("#fecha_final").val(),
            usuario: $("#usuarios").val()
        });

        if (resp.length == 0) {
            dissminSwal()
            Swal.fire("Ups", "No se encontraron resultados!", "error")
            return;
        }

        tb = `<table class="table table-sm" id="cajas">
               <thead>
               </thead>
               <tbody>
               </tbody>
               </table>`
        $("#result").html(tb);

        createDataTable('cajas', resp,
            ['usuario', 'fecha_apertura', 'fecha_cierre', 'base', 'efectivo', 'tarjetas', 'otros', 'sys_efectivo', 'sys_tarjeta', 'contado', 'dev_contado', 'diferencia'],
            ['usuario', 'fecha_apertura', 'fecha_cierre', 'base', 'efectivo', 'tarjetas', 'otros', 'sys_efectivo', 'sys_tarjeta', 'contado', 'devoluciones', 'diferencia']
            , null, null, false, false, '', true, ['base', 'efectivo', 'tarjetas', 'otros', 'sys_efectivo', 'sys_tarjeta', 'contado', 'dev_contado', 'diferencia'], 'COP',
            [
                { data: 'sys_efectivo', className: 'table-success' },
                { data: 'sys_tarjeta', className: 'table-info' },
                { data: 'diferencia', className: 'table-danger' },
                { data: 'base', className: 'table-primary' },
                { data: 'efectivo', className: 'table-primary' },
                { data: 'tarjetas', className: 'table-primary' },
                { data: 'otros', className: 'table-primary' },
            ],
            ['sys_efectivo', 'sys_tarjeta', 'contado', 'dev_contado', 'diferencia'])

        dissminSwal();
    } catch (error) {
        console.error(error)
        Swal.fire("Ups", "Error al generar los datos", "error")
    }
}
$(function () {
    usuarios();
    $("#btn-busqueda").click(function () {

        if ($("#fecha_inicial").val() == '' && $("#fecha_final").val() == '') {
            Swal.fire("Ups", "La fecha y hora inicial y final son obligatorias", "error")
            return;
        }
        consultar();
    });
    dissminSwal();
})
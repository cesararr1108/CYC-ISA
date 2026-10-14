const llenarSelectVigencias = () => {
    const $vigencia = $('#selVigencia');
    $vigencia.empty();

    const currentYear = new Date().getFullYear();
    const ultimoAnio = currentYear - 1;

    // Mostrar los últimos 5 años (incluyendo el anterior)
    for (let year = ultimoAnio; year >= ultimoAnio - 4; year--) {
        $vigencia.append(new Option(year, year, false, year === ultimoAnio));
    }
};

const listarFormatos = async () => {

    const resp = await enviarPeticion({
        link: '../models/parametrosExogena.php',
        op: 'listar-formatos'
    });
    console.log('listarFormatos response:', resp); const $select = $('#selFormato');
    $select.empty();

    $select.append(new Option('Seleccione un formato', ''));

    if (!Array.isArray(resp)) {
        Swal.fire('Error', 'No fue posible cargar los formatos.', 'error');
        return;
    }

    resp.forEach(fmt => {
        $select.append(
            new Option(
                `${fmt.formato} - ${fmt.nombre}`, // Texto visible
                fmt.formato                        // Valor
            )
        );
    });

    $select.prop('selectedIndex', 0);

    // Si usas Select2
    // $select.trigger('change'); 
};

const encabezadoTable = (formato) => {

    let table = '';
    switch (formato) {
        case '1008':

            table = `
              <table class="table table-sm " id="tblReporte">
                <thead class="table-primary">
                  <th>Concepto</th>
                  <th>Tipo</th>
                  <th>Nit</th>
                  <th>DV</th>
                  <th>Primer apellido</th>
                  <th>Segundo apellido</th>
                  <th>Primer nombre</th>
                  <th>Segundo nombre</th>
                  <th>Razon comercial</th>
                  <th>Direccion</th>
                  <th>Codigo dpto</th>
                  <th>Codigo municipio</th>
                  <th>Pais</th>
                  <th>Saldo</th>
                </thead>
            `;
            break;
        case '1009':

            table = `
              <table class="table table-sm " id="tblReporte">
                <thead class="table-primary">
                  <th>Concepto</th>
                  <th>Tipo</th>
                  <th>Nit</th>
                  <th>DV</th>
                  <th>Primer apellido</th>
                  <th>Segundo apellido</th>
                  <th>Primer nombre</th>
                  <th>Segundo nombre</th>
                  <th>Razon comercial</th>
                  <th>Direccion</th>
                  <th>Codigo dpto</th>
                  <th>Codigo municipio</th>
                  <th>Pais</th>
                  <th>Saldo</th>
                </thead>
            `;
            break;

        default:
            break;
    }
    return table;
}

$(async function () {
    llenarSelectVigencias();
    await listarFormatos();

    $("#btnBuscarParametros").on('click', async function () {
        const vigencia = $('#selVigencia').val();
        const formato = $('#selFormato').val();

        if (!vigencia || !formato) {
            Swal.fire('Error', 'Debe seleccionar una vigencia y un formato.', 'error');
            return;
        }
        console.log({
            link: '../models/rptExogena.php',
            op: formato,
            anio: 2026 // vigencia
        })
        try {
            showLoadingWithTetrisLogo('Generando reporte...', 'ISA Software')
            const resp = await enviarPeticion({
                link: '../models/rptExogena.php',
                op: formato,
                anio: 2026 //vigencia
            });
            console.log('rptExogena response:', resp);
            if (resp.length === 0) {
                dissminSwal();
                $("#result").html('');
                Swal.fire('Error', 'Sin resultados para la consulta.', 'error');
                return;
            }

            let tab = '';
            switch (formato) {
                case '1008':
                    tab += encabezadoTable('1008');
                    tab += `
                    <tbody>
                    `;
                    resp.forEach(item => {
                        tab += `
                        <tr>
                            <td>${item.concepto}</td>
                            <td>${item.tipo}</td>
                            <td>${item.nit}</td>
                            <td>${item.dv}</td>
                            <td>${item.primer_apellido}</td>
                            <td>${item.segundo_apellido}</td>
                            <td>${item.primer_nombre}</td>
                            <td>${item.segundo_nombre}</td>
                            <td>${item.razon_comercial}</td>
                            <td>${item.direccion}</td>
                            <td>${item.codigo_dpto}</td>
                            <td>${item.codigo_municipio}</td>
                            <td>${item.pais}</td>
                            <td>${item.saldo}</td>
                        </tr>
                        `;
                    });
                    tab += `
                    </tbody>
                    </table>
                    `;

                    dissminSwal();
                    break;
                case '1009':
                    tab += encabezadoTable('1009');
                    tab += `
                    <tbody>
                    `;
                    resp.forEach(item => {
                        tab += `
                        <tr>
                            <td>${item.concepto}</td>
                            <td>${item.tipo}</td>
                            <td>${item.nit}</td>
                            <td>${item.dv}</td>
                            <td>${item.primer_apellido}</td>
                            <td>${item.segundo_apellido}</td>
                            <td>${item.primer_nombre}</td>
                            <td>${item.segundo_nombre}</td>
                            <td>${item.razon_comercial}</td>
                            <td>${item.direccion}</td>
                            <td>${item.codigo_dpto}</td>
                            <td>${item.codigo_municipio}</td>
                            <td>${item.pais}</td>
                            <td>${item.saldo}</td>
                        </tr>
                        `;
                    });
                    tab += `
                    </tbody>
                    </table>
                    `;

                    dissminSwal();
                    break;

                default:
                    dissminSwal();
                    break;
            }
            $('#result').html(tab);
            // habilito los botones de exportar a excel 
            // NUMERO CON DECIMALES SEPARADOS POR .
            $('#tblReporte').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excel',
                        title: 'Reporte exógena',
                        messageTop: 'Reporte exógena',
                        messageBottom: 'Reporte exógena',
                        customize: function (xlsx) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];

                            $('row c[r^="N"]', sheet).each(function () {
                                var cell = $(this);

                                if (cell.find('v').length) {
                                    var valor = cell.find('v').text();
                                    valor = valor.replace(',', '.');

                                    cell.attr('t', 'inlineStr');
                                    cell.html('<is><t>' + valor + '</t></is>');
                                }
                            });
                        }
                    }
                ]
            });
            console.log('rptExogena response:', resp);
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'No fue posible generar el reporte.', 'error');
        }
    })

    dissminSwal();
});
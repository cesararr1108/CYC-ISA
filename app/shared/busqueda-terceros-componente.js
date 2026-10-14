class ModalBusquedaTerceros {

    nRecords;

    constructor(onSubmitCallback) {
        this.onSubmitCallback = onSubmitCallback;
        this.modalId = 'modalBusquedaTerceros';
        this.init();
    }

    // Inicializa y crea el modal en el DOM
    setNrecords(valor) {
        this.nRecords = valor
    }
    getNrecords() {
        return this.nRecords
    }



    init() {


        const modalHTML = `
                    
<div class="modal fade " id="${this.modalId}" aria-hidden="true" data-bs-backdrop="static">

    <div class="modal-dialog modal-fullscreen modal-dialog-scrollable " style="width:95%;height:95vh; margin:0 auto" role="document">
    <div class="modal-content" style="height:95vh;">
        <div class="modal-header">
        <h5 class="modal-title" >Búsqueda de terceros</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" >
            <div class="row">
                <div class="col-md-12">
                    <div class="input-group input-group-sm input-group-merge">
                        <span class="input-group-text" id="basic-addon-search31"><i class="bx bx-search"></i></span>
                        <input
                        type="text"
                        class="form-control form-control-sm busqueda-terceros"
                        placeholder="Nii/nombres/razon comercial"
                        aria-label="Descripcion"
                        aria-describedby="basic-addon-search31"
                        id="busqueda-terceros"
                        />
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12 d-flex align-content-end justify-content-end">
                    <!--  <button type="button" class="btn btn-sm btn-outline-primary" id="btn-busqueda-referencias" > Consultar <i class="fa fa-search"></i></button> -->
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <div id="result-busqueda-terceros"></div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
`;
        // Insertar el modal en el DOM
        document.getElementById('modal-container-terceros').innerHTML = modalHTML;
        // Supongamos que el modal tiene el id #myModal
        $(`#${this.modalId}`).on('hidden.bs.modal', function () {
            $(".busqueda-terceros input[type='text']").val("");
            $('#result-busqueda-terceros').html('')
        });
        // Asignar eventos
        this.attachEvents();
    }

    attachEvents() {
        const inputs = document.querySelectorAll('.busqueda-terceros');

        inputs.forEach(element => {
            element.addEventListener('keyup', async (e) => {
                if (e.key === 'Enter') {
                    this.getRecords('');
                }
            });
        });


        // Evento para habilitar las teclas de navegación en la tabla
        document.addEventListener('keydown', (event) => {
            if (document.getElementById(this.modalId).classList.contains('show')) {
                this.handleTableNavigation(event);
            }
        });

        // Delegar eventos para las filas en el contenedor de la tabla
        const tableContainer = document.querySelector('#result-busqueda-terceros');

        if (tableContainer) {

            tableContainer.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    const row = event.target.closest('tr');
                    if (row) {
                        const dataElement = $(row).attr('data-row-element')
                            ? JSON.parse($(row).attr('data-row-element'))
                            : null;
                        this.handleRowSelection(dataElement);
                        $("#modalBusquedaTerceros").modal("hide");
                    }
                }
            });

            tableContainer.addEventListener('click', (event) => {

                const row = event.target.closest('tr'); // Detecta la fila que contiene el evento

                // Verifica que la fila pertenece a tbody y no a thead
                if (row && row.parentElement.tagName === 'TBODY') {
                    const dataElement = $(row).attr('data-row-element')
                        ? JSON.parse($(row).attr('data-row-element'))
                        : null;

                    this.handleRowSelection(dataElement);

                    $("#modalBusquedaTerceros").modal("hide");
                }
            });

        }
    }

    // Nuevo método para manejar la selección de filas
    handleRowSelection(data) {

        if (typeof this.onSubmitCallback === 'function') {
            this.onSubmitCallback(data); // Llama al callback pasando los datos de la fila
        }
    }


    // Manejo de navegación en la tabla
    handleTableNavigation(event) {

        const table = document.querySelector('#modalBusquedaTerceros table'); // Ajusta el selector si cambias el HTML
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        if (!rows.length) return;

        // Asegúrate de que las filas sean focuseables
        rows.forEach(row => row.setAttribute('tabindex', '0'));

        const focusedRow = document.activeElement.closest('tr');
        let selectedIndex = Array.from(rows).indexOf(focusedRow);

        if (event.key === 'ArrowDown') {
            if (selectedIndex < rows.length - 1) {
                rows[selectedIndex + 1].focus();
            }
        } else if (event.key === 'ArrowUp') {
            if (selectedIndex > 0) {
                rows[selectedIndex - 1].focus();
            }
        }
    }



    // Mostrar el modal
    show() {
        const modal = new bootstrap.Modal(document.getElementById(this.modalId));
        modal.show();
    }

    // Ocultar el modal
    hide() {
        const modal = bootstrap.Modal.getInstance(document.getElementById(this.modalId));
        modal.hide();
    }
    getData(filtro) {
        if (filtro == '') {
            filtro = $("#busqueda-terceros").val()
        }
        const data = {
            filtro,
        }
        data.link = '../models/Terceros.php';
        data.op = 's_tercero';
        data.filtro = filtro;
        data.tipo_tercero = '';

        const response = enviarPeticion(data)
        return response
    }



    async getRecords(filtro) {

        showLoadingWithTetrisLogo(
            'Buscando coincidencias...',
            'ISA Software'
        );
        try {

            const resp = await this.getData(filtro);
            this.setNrecords(resp.data.length)

            if (resp.data.length == 0) {
                Swal.fire("Ups", "No se encontraron coincidencias", "error")
                return;
            }
            this.showResult(resp.data)
            dissminSwal();
        } catch (e) {
            dissminSwal();
            Swal.fire("Error", "Se ha generado un error interno en la aplicación", "error")
            $(".tabla-busqueda-referencias tbody tr").remove()
            console.error(e)
        }
    }

    showResult(data) {

        let tb = `<table class="table table-sm table-borderless tabla-busqueda-componete">
                <thead class="table-primary">
                    <tr >
                        <th>Nit</th>
                        <th>Tratamiento</th>
                        <th>Razón comercial</th>
                        <th>Cupo de credito</th>
                        <th>Email</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Condición</th>
                    </tr>
                </thead>
            <tbody> `

        let tab = 100;
        data.forEach((element, idx) => {

            tb += `<tr class="" data-row-element='${JSON.stringify(element)}' id="table-busqueda-terceros" tabindex="${tab}">
                <td>${element.nit}</td>
                <td>${element.tratamiento}</td>
                <td>${element.razon_comercial}</td>
                <td>${formatNum(element.cupo_credito, '$')}</td>
                <td class="table-primary">${element.email_fe}</td>
                <td>${element.direccion}</td>
                <td>${element.telefono}</td>
                <td>${element.des_condicion}</td>
            </tr>`
            tab++;
        });
        tb += `</tbody>
    </table>`
        $("#result-busqueda-terceros").html(tb);
        setTimeout(() => {
            $("#result-busqueda-terceros table tbody tr:first").focus();
        }, 300);

    }

}


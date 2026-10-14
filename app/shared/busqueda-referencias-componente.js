'use strict';

/**
 * ModalBusquedaProducto
 * ------------------------------------------------------------------
 * Componente de búsqueda de productos/referencias en modal, con tabla
 * de resultados navegable por teclado y preferencias de usuario
 * (checkbox "Con stock" y límite de registros) persistidas en servidor.
 *
 * CAMBIOS RESPECTO A LA VERSIÓN ORIGINAL
 * ------------------------------------------------------------------
 * 1. Preferencias:
 *    - Nuevo método cargarPreferencias(): consulta las preferencias del
 *      usuario al servidor ANTES de pintar el modal, para que el
 *      checkbox "Con stock" y el select de límite ya salgan con el
 *      valor guardado (antes solo se podían pasar por `opciones`).
 *    - Nuevo método guardarPreferencia(campo, valor): reemplaza los dos
 *      handlers casi idénticos que existían para stock y límite.
 *    - ASUNCIÓN: se asume que existe (o se debe crear) el op
 *      'obtener-preferencias' en servicios.php, simétrico al ya
 *      existente 'cambiar-preferencias', que devuelve
 *      { SoloProductoSock: 'S'|'N', LimiteRegistros: '20'|'40'|'-1' }.
 *      Ajusta el nombre del op / de los campos si en tu backend se
 *      llaman distinto.
 *    - Si el caller pasa `check_stock` o `limite_registros` explícito
 *      en `opciones`, ese valor NO se pisa con lo que llegue del
 *      servidor (las opciones explícitas siempre ganan).
 *
 * 2. Bugs corregidos:
 *    - `id_modulo` dependía de una variable global no declarada en
 *      ningún lugar del componente. Ahora se recibe como
 *      `opciones.id_modulo` (con fallback a la global `id_modulo` si
 *      existe, por compatibilidad con el resto del sistema).
 *    - Cada fila de resultados incrustaba el objeto completo como JSON
 *      dentro de un atributo `data-row-element`. Si la descripción u
 *      otro campo traía una comilla simple, se rompía el HTML de la
 *      fila. Ahora los datos se guardan en memoria (`this.currentData`)
 *      y la fila solo referencia un índice (`data-idx`).
 *    - El listener de `hidden.bs.modal` intentaba limpiar los filtros
 *      con el selector `.busqueda-referencias input[type='text']`, que
 *      nunca matcheaba nada porque esa clase está en los propios
 *      inputs, no en un contenedor. Se corrigió a
 *      `.busqueda-referencias[type='text']`.
 *    - En el catch de getRecords() se intentaban borrar filas con la
 *      clase `.tabla-busqueda-referencias`, que no existe (la tabla
 *      real usa `.tabla-busqueda-componete`). Se reemplazó por limpiar
 *      directamente el contenedor de resultados.
 *    - Cada `<tr>` de la tabla de resultados tenía el mismo
 *      `id="table-busqueda-referencias"` repetido (HTML inválido). Se
 *      cambió por una clase.
 *    - Variable `selected` declarada y nunca usada (código muerto).
 *
 * 3. Rendimiento:
 *    - La tabla de resultados se arma con `Array.map().join()` en vez
 *      de concatenar un string en un `forEach` (importante cuando el
 *      límite de registros es "Sin límite").
 *    - `formatCantidad` deja de recrearse como closure en cada fila;
 *      ahora es un método estático que recibe sus parámetros.
 *    - Los listeners de los inputs de filtro (Enter para buscar) se
 *      delegan en un único listener sobre el body del modal, en vez de
 *      uno por input.
 *    - Referencias al DOM del modal (checkbox, select, contenedores) se
 *      cachean una sola vez después de renderizar, en `this.dom`.
 *
 * El markup del modal (HTML) se mantiene igual al original: mismos ids,
 * clases y estructura, para no romper CSS ni otros scripts del sistema
 * que dependan de ellos.
 */
class ModalBusquedaProducto {

    static DEFAULT_OPCIONES = {
        check_stock: true,
        limite_registros: '20',
        lista: 1,
        id_modulo: null
    };

    static SERVICIOS_URL = '../shared/servicios/servicios.php';

    /**
     * Estilos del componente (se inyectan una sola vez por página).
     * En pantallas pequeñas la tabla de resultados se muestra como tarjetas,
     * la leyenda se colapsa y el modal ocupa toda la pantalla.
     */
    static ESTILOS = `
        @media (min-width: 992px) {
            #modalBusquedaRef .modal-busqueda-dialog { max-width: 95vw; width: 95vw; }
        }
        #modalBusquedaRef .tabla-busqueda-componete tbody tr { cursor: pointer; }
        #modalBusquedaRef .tabla-busqueda-componete tbody tr:focus { outline: 2px solid var(--bs-primary, #696cff); outline-offset: -2px; }
        #modalBusquedaRef .tabla-busqueda-componete th { white-space: nowrap; }
        /* Tema oscuro (<html data-color-scheme="dark">); funciona también fuera del POS */
        html[data-color-scheme="dark"] #modalBusquedaRef,
        html[data-bs-theme="dark"] #modalBusquedaRef {
            --pos-superficie: #2b2c40; --pos-borde: #45465b; --pos-texto-tenue: #a7acb2;
        }
        html[data-color-scheme="dark"] #modalBusquedaRef .tabla-busqueda-componete tbody tr:hover > *,
        html[data-bs-theme="dark"] #modalBusquedaRef .tabla-busqueda-componete tbody tr:hover > * {
            background-color: rgba(105, 108, 255, .10);
        }
        @media (max-width: 767.98px) {
            #modalBusquedaRef .modal-body { padding: .75rem; }
            #modalBusquedaRef input[type="search"], #modalBusquedaRef select { font-size: 16px; }
            #modalBusquedaRef .tabla-busqueda-componete,
            #modalBusquedaRef .tabla-busqueda-componete tbody { display: block; width: 100%; }
            #modalBusquedaRef .tabla-busqueda-componete thead { display: none; }
            #modalBusquedaRef .tabla-busqueda-componete tbody tr {
                display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .25rem .75rem;
                border: 1px solid var(--pos-borde, #d9dee3); border-radius: .5rem;
                padding: .6rem .75rem; margin-bottom: .5rem; background: var(--pos-superficie, #fff);
            }
            #modalBusquedaRef .tabla-busqueda-componete tbody td {
                display: flex; flex-direction: column; padding: 0 !important; border: 0; font-size: .8rem;
            }
            #modalBusquedaRef .tabla-busqueda-componete tbody td::before {
                content: attr(data-label); color: var(--pos-texto-tenue, #8592a3); font-size: .7rem; font-weight: 600;
            }
            #modalBusquedaRef .tabla-busqueda-componete tbody td.td-full { grid-column: 1 / -1; }
            #modalBusquedaRef .tabla-busqueda-componete tbody td.td-full::before { content: none; }
            #modalBusquedaRef .tabla-busqueda-componete tbody td.td-codigo { grid-column: 1 / -1; color: var(--pos-texto-tenue, #8592a3); font-size: .75rem; }
            #modalBusquedaRef .tabla-busqueda-componete tbody td.td-codigo::before { content: none; }
            #modalBusquedaRef .tabla-busqueda-componete tbody td.vacio { display: none; }
        }
    `;

    static inyectarEstilos() {
        if (document.getElementById('estilos-modal-busqueda-producto')) return;
        const style = document.createElement('style');
        style.id = 'estilos-modal-busqueda-producto';
        style.textContent = ModalBusquedaProducto.ESTILOS;
        document.head.appendChild(style);
    }
    static SERVICIOS_PREFERENCIAS_URL = '../shared/servicios/servicios-preferencias.php';

    constructor(onSubmitCallback, opciones = {}) {
        this.onSubmitCallback = onSubmitCallback;
        this.modalId = 'modalBusquedaRef';
        this.modalIdLote = 'modalFechaLotes';

        // Lo que el caller pasó explícitamente (tiene prioridad sobre
        // lo que se cargue del servidor).
        this._opcionesIniciales = { ...opciones };
        this.opciones = { ...ModalBusquedaProducto.DEFAULT_OPCIONES, ...opciones };

        this.events = {};
        this.nRecords = 0;
        this.currentData = []; // último resultado de búsqueda, para lookup por índice
        this.dom = {};         // referencias al DOM, cacheadas tras renderizar

        // init() es async porque primero consulta preferencias al
        // servidor. Guardamos la promesa para poder esperarla en
        // show()/destroy() y así no operar sobre un modal a medio pintar.
        this.ready = this.init();
    }

    setNrecords(valor) { this.nRecords = valor; }
    getNrecords() { return this.nRecords; }

    get idModulo() {
        return this.opciones.id_modulo ?? (typeof id_modulo !== 'undefined' ? id_modulo : null);
    }

    // =========================================================
    // PREFERENCIAS
    // =========================================================

    /**
     * Consulta las preferencias del usuario para este módulo y las
     * fusiona en this.opciones, respetando lo que el caller haya
     * pasado explícitamente al construir el componente.
     */
    async cargarPreferencias() {
        try {
            const resp = await enviarPeticion({
                link: ModalBusquedaProducto.SERVICIOS_PREFERENCIAS_URL,
                op: 'obtener-preferencias',
                id_modulo: this.idModulo
            });

            // El backend devuelve una fila por cada preferencia guardada:
            // [{ id_usuario, id_modulo, clave, valor }, ...]
            // Se arma un mapa clave -> valor para poder leerlas por nombre.
            const filas = Array.isArray(resp?.data) ? resp.data : [];
            const prefs = {};
            filas.forEach((fila) => { prefs[fila.clave] = fila.valor; });

            if (!('check_stock' in this._opcionesIniciales) && 'SoloProductoSock' in prefs) {
                this.opciones.check_stock = prefs.SoloProductoSock === 'S';
            }

            if (!('limite_registros' in this._opcionesIniciales) && prefs.LimiteRegistros) {
                this.opciones.limite_registros = String(prefs.LimiteRegistros);
            }
        } catch (e) {
            // Si falla la carga, seguimos con los valores por defecto
            // o los recibidos explícitamente; no bloquea el render del modal.
            console.error('No fue posible cargar las preferencias de búsqueda:', e);
        }
    }

    /**
     * Guarda una preferencia puntual en el servidor. Reemplaza los dos
     * handlers casi idénticos que existían para stock y límite.
     */
    async guardarPreferencia(campo, valor) {
        try {
            await enviarPeticion({
                link: ModalBusquedaProducto.SERVICIOS_URL,
                op: 'cambiar-preferencias',
                id_modulo: this.idModulo,
                [campo]: valor
            });
        } catch (e) {
            console.error(`No fue posible guardar la preferencia "${campo}":`, e);
        }
    }

    // =========================================================
    // INICIALIZAR COMPONENTE
    // =========================================================
    async init() {
        ModalBusquedaProducto.inyectarEstilos();
        await this.cargarPreferencias();

        const checkStock = this.opciones.check_stock ? 'checked' : '';
        const limiteSeleccionado = this.opciones.limite_registros || '20';

        const modalHTML = `
            <div class="modal fade"
                 id="${this.modalId}"
                 aria-hidden="true"
                 data-bs-backdrop="static">
                <div class="modal-dialog modal-fullscreen-lg-down modal-dialog-scrollable modal-busqueda-dialog"
                     role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Búsqueda de productos
                            </h5>
                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                         <button type="button"
                                 class="btn btn-sm btn-outline-primary w-100 d-md-none mb-2"
                                 data-bs-toggle="collapse"
                                 data-bs-target="#leyendaBusquedaRef"
                                 aria-expanded="false">
                            <i class="bx bx-info-circle me-1"></i> Ver leyenda de iconos
                         </button>
                         <div class="row mt-1 collapse d-md-flex" id="leyendaBusquedaRef">
                            <div class="col-12">
                                <div class="card border-0 shadow-sm mb-3 mb-md-4">
                                    <div class="card-body py-2 py-md-3 px-3 px-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bx bx-info-circle fs-5 me-2 text-primary"></i>
                                            <h6 class="fw-bold text-dark mb-0">Leyenda de iconos</h6>
                                        </div>
                                        <hr class="text-muted opacity-25 my-2">
                                        <div class="d-flex align-items-center gap-2 gap-md-4 flex-wrap pt-1 text-secondary small">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-hourglass-end text-danger fs-6"></i>
                                                <span>Vmcto menor a 3 meses</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-hourglass text-warning fs-6"></i>
                                                <span>Vmcto menor a 6 meses</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-hand-holding-dollar text-success fs-6"></i>
                                                <span>Incluye comisión</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fa-solid fa-certificate text-primary fs-6"></i>
                                                <span>Tiene descuento</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                         </div>
                         <div class="row g-2">
                                <div class="col-12 col-md-5">
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text">
                                            <i class="bx bx-search"></i>
                                        </span>
                                        <input
                                            type="text"
                                            inputmode="search"
                                            enterkeyhint="search"
                                            autocomplete="off"
                                            class="form-control form-control-sm busqueda-referencias"
                                            placeholder="Descripción"
                                            aria-label="Descripción"
                                            name="descripcion"
                                            id="busqueda-descripcion" />
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="input-group input-group-sm input-group-merge">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-flask"></i>
                                        </span>
                                        <input
                                            type="text"
                                            inputmode="search"
                                            enterkeyhint="search"
                                            autocomplete="off"
                                            class="form-control form-control-sm busqueda-referencias"
                                            placeholder="Laboratorio"
                                            aria-label="Laboratorio"
                                            name="laboratorio"
                                            id="busqueda-laboratorio"/>
                                    </div>
                                </div>
                                <div class="col-7 col-md-2">
                                    <select class="form-select form-select-sm" id="LimiteRegistros">
                                        <option value="20" ${limiteSeleccionado == '20' ? 'selected' : ''}>20 registros</option>
                                        <option value="40" ${limiteSeleccionado == '40' ? 'selected' : ''}>40 registros</option>
                                        <option value="-1" ${limiteSeleccionado == '-1' ? 'selected' : ''}>Sin limite</option>
                                    </select>
                                </div>
                                <div class="col-5 col-md-2 d-grid">
                                    <button type="button" class="btn btn-sm btn-primary" id="btn-buscar-referencias">
                                        <i class="bx bx-search me-1"></i>Buscar
                                    </button>
                                </div>
                            </div>

                            <div class="row mt-3 d-none">
                                <div class="col-3">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm busqueda-referencias"
                                        placeholder="Criterio 1"
                                        id="busqueda-criterio1"/>
                                </div>
                                <div class="col-3">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm busqueda-referencias"
                                        placeholder="Criterio 2"
                                        id="busqueda-criterio2"/>
                                </div>
                                <div class="col-3">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm busqueda-referencias"
                                        placeholder="Criterio 3"
                                        id="busqueda-criterio3"/>
                                </div>
                                <div class="col-3">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm busqueda-referencias"
                                        placeholder="Criterio 4"
                                        id="busqueda-criterio4"/>
                                </div>
                            </div>
                             

                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-check form-check-inline mt-2">
                                        <input
                                            class="form-check-input busqueda-referencias"
                                            type="checkbox"
                                            id="con_stock"
                                            ${checkStock}/>
                                        <label class="form-check-label" for="con_stock">
                                            Con stock
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            class="form-check-input busqueda-referencias"
                                            type="checkbox"
                                            id="busqueda-institucional"/>
                                        <label class="form-check-label" for="busqueda-institucional">
                                            Institucional
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            class="form-check-input busqueda-referencias"
                                            type="checkbox"
                                            id="busqueda-regulado"/>
                                        <label class="form-check-label" for="busqueda-regulado">
                                            Regulado
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input
                                            class="form-check-input busqueda-referencias"
                                            type="checkbox"
                                            id="busqueda-controlado" />
                                        <label class="form-check-label" for="busqueda-controlado">
                                            Controlado
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <div
                                        class="table-responsive"
                                        id="result-busqueda-referencias">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================
                 MODAL LOTES
            ====================================================== -->

            <div class="modal fade"
                 id="modalFechaLotes"
                 tabindex="-1"
                 aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                Selección de Lotes / Vencimiento
                            </h5>
                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                            </button>
                        </div>
                        <div class="modal-body">
                            <div id="result-lotes"></div>
                        </div>
                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-dismiss="modal">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('modal-container').innerHTML = modalHTML;

        this.cacheDom();
        this.attachEvents();
    }

    // =========================================================
    // REFERENCIAS AL DOM (cacheadas una sola vez)
    // =========================================================
    cacheDom() {
        this.dom.modal = document.getElementById(this.modalId);
        this.dom.modalLotes = document.getElementById(this.modalIdLote);
        this.dom.modalBody = this.dom.modal?.querySelector('.modal-body') ?? null;
        this.dom.resultContainer = document.getElementById('result-busqueda-referencias');
        this.dom.stockCheckbox = document.getElementById('con_stock');
        this.dom.limiteSelect = document.getElementById('LimiteRegistros');
        this.dom.btnBuscar = document.getElementById('btn-buscar-referencias');
        this.dom.inputDescripcion = document.getElementById('busqueda-descripcion');
    }

    // =========================================================
    // EVENTOS
    // =========================================================
    attachEvents() {

        // Enter en cualquier filtro de texto -> buscar (un solo listener
        // delegado en el body del modal, en vez de uno por input)
        this.events.inputKeyup = (e) => {
            const target = e.target;
            if (e.key !== 'Enter') return;
            if (!target.classList?.contains('busqueda-referencias')) return;
            if (target.tagName !== 'INPUT' || target.type !== 'text') return;

            target.blur?.();   // en móvil cierra el teclado para ver los resultados
            this.buscarDesdeModal();
        };
        this.dom.modalBody?.addEventListener('keyup', this.events.inputKeyup);

        // Botón Buscar (necesario en móvil)
        if (this.dom.btnBuscar) {
            this.events.btnBuscar = () => this.buscarDesdeModal();
            this.dom.btnBuscar.addEventListener('click', this.events.btnBuscar);
        }

        // Filas de resultados: click y Enter
        if (this.dom.resultContainer) {

            this.events.tableKeydown = (event) => {
                if (event.key !== 'Enter') return;
                const row = event.target.closest('tr');
                if (!row) return;
                this.seleccionarFila(row);
            };
            this.dom.resultContainer.addEventListener('keydown', this.events.tableKeydown);

            this.events.tableClick = (event) => {
                const row = event.target.closest('tr');
                if (row && row.parentElement.tagName === 'TBODY') {
                    this.seleccionarFila(row);
                }
            };
            this.dom.resultContainer.addEventListener('click', this.events.tableClick);
        }

        // Teclado global del componente
        this.events.documentKeydown = (event) => this.handleGlobalKeyDown(event);
        document.addEventListener('keydown', this.events.documentKeydown);

        // Modal de búsqueda cerrado: limpiar filtros y resultados
        if (this.dom.modal) {
            this.events.hiddenBusqueda = () => {
                // Antes: ".busqueda-referencias input[type='text']" (nunca matcheaba nada)
                $(".busqueda-referencias[type='text']").val('');
                if (this.dom.resultContainer) this.dom.resultContainer.innerHTML = '';
                this.currentData = [];
            };
            $(this.dom.modal).on('hidden.bs.modal', this.events.hiddenBusqueda);
        }

        // Modal de lotes cerrado
        if (this.dom.modalLotes) {
            this.events.hiddenLotes = () => $('.data-lotes tbody tr').remove();
            $(this.dom.modalLotes).on('hidden.bs.modal', this.events.hiddenLotes);
        }

        // Preferencia: con stock
        if (this.dom.stockCheckbox) {
            this.events.stockChange = (e) => {
                const opcion = e.currentTarget.checked ? 'S' : 'N';
                this.opciones.check_stock = opcion === 'S';
                this.guardarPreferencia('SoloProductoSock', opcion);
            };
            this.dom.stockCheckbox.addEventListener('change', this.events.stockChange);
        }

        // Preferencia: límite de registros
        if (this.dom.limiteSelect) {
            this.events.limiteChange = (e) => {
                const valorLimite = e.currentTarget.value;
                this.opciones.limite_registros = valorLimite;
                this.guardarPreferencia('LimiteRegistros', valorLimite)
                    .then(() => this.setNrecords(valorLimite));
            };
            this.dom.limiteSelect.addEventListener('change', this.events.limiteChange);
        }
    }

    buscarDesdeModal() {
        return this.getRecords({
            data: this.dom.inputDescripcion?.value ?? '',
            cod_barras: false,
            lista: this.opciones.lista ?? 1
        });
    }

    seleccionarFila(row) {
        const idx = Number(row.dataset.idx);
        const data = Number.isInteger(idx) ? this.currentData[idx] ?? null : null;
        this.handleRowSelection(data);
        this.hide();
    }

    // =========================================================
    // KEYBOARD
    // =========================================================
    handleGlobalKeyDown(e) {

        // Modal de lotes abierto
        if ($('#modalFechaLotes').is(':visible')) {

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const currentRow = $('.data-lotes tbody tr.focus');
                let nextRow = currentRow.next('tr');
                if (nextRow.length === 0) nextRow = $('.data-lotes tbody tr').first();
                currentRow.removeClass('focus');
                nextRow.addClass('focus');
                nextRow.focus();
                return;
            }

            if (e.key === 'ArrowUp') {
                e.preventDefault();
                const currentRow = $('.data-lotes tbody tr.focus');
                let prevRow = currentRow.prev('tr');
                if (prevRow.length === 0) prevRow = $('.data-lotes tbody tr').last();
                currentRow.removeClass('focus');
                prevRow.addClass('focus');
                prevRow.focus();
                return;
            }

            if (e.key === 'Enter') {
                const focusedRow = $('.data-lotes tbody tr.focus');
                if (focusedRow.length > 0) {
                    e.preventDefault();
                    cargarItemGridLote(focusedRow[0]);
                }
                return;
            }
        }

        // Modal de búsqueda de producto
        if ($(`#${this.modalId}`).is(':visible')) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                this.handleTableNavigation(e);
            }
            return;
        }

        // Fuera del modal
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            if ($('#codigo_grid').val() !== '') {
                $('#und_grid').focus();
            }
            return;
        }

        if (e.key === 'F4') {
            e.preventDefault();
            $('#guardar').trigger('click');
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            $('#filtro').focus();
            if (typeof limpiarReferencia === 'function') {
                limpiarReferencia();
            }
            return;
        }
    }

    // =========================================================
    // SELECCIONAR FILA
    // =========================================================
    handleRowSelection(data) {
        if (typeof this.onSubmitCallback === 'function') {
            this.onSubmitCallback(data);
        }
    }

    // =========================================================
    // NAVEGACION TABLA PRODUCTOS
    // =========================================================
    handleTableNavigation(event) {
        const table = document.querySelector(`#${this.modalId} table`);
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        if (!rows.length) return;

        rows.forEach(row => row.setAttribute('tabindex', '0'));

        const focusedRow = document.activeElement.closest('tr');
        let selectedIndex = Array.from(rows).indexOf(focusedRow);

        if (selectedIndex === -1) {
            rows[0].focus();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (selectedIndex < rows.length - 1) rows[selectedIndex + 1].focus();
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (selectedIndex > 0) rows[selectedIndex - 1].focus();
            return;
        }
    }

    // =========================================================
    // MOSTRAR / OCULTAR MODAL
    // =========================================================
    async show() {
        await this.ready;
        if (!this.dom.modal) return;
        if ($(this.dom.modal).hasClass('show')) return;
        bootstrap.Modal.getOrCreateInstance(this.dom.modal).show();
    }

    hide() {
        if (!this.dom.modal) return;
        const modal = bootstrap.Modal.getInstance(this.dom.modal);
        modal?.hide();
    }

    // =========================================================
    // DESTRUIR COMPONENTE
    // =========================================================
    async destroy() {
        await this.ready;

        if (this.events.documentKeydown) {
            document.removeEventListener('keydown', this.events.documentKeydown);
        }

        if (this.dom.modalBody && this.events.inputKeyup) {
            this.dom.modalBody.removeEventListener('keyup', this.events.inputKeyup);
        }

        if (this.dom.btnBuscar && this.events.btnBuscar) {
            this.dom.btnBuscar.removeEventListener('click', this.events.btnBuscar);
        }

        if (this.dom.resultContainer) {
            if (this.events.tableKeydown) {
                this.dom.resultContainer.removeEventListener('keydown', this.events.tableKeydown);
            }
            if (this.events.tableClick) {
                this.dom.resultContainer.removeEventListener('click', this.events.tableClick);
            }
        }

        if (this.dom.modal && this.events.hiddenBusqueda) {
            $(this.dom.modal).off('hidden.bs.modal', this.events.hiddenBusqueda);
        }

        if (this.dom.modalLotes && this.events.hiddenLotes) {
            $(this.dom.modalLotes).off('hidden.bs.modal', this.events.hiddenLotes);
        }

        if (this.dom.stockCheckbox && this.events.stockChange) {
            this.dom.stockCheckbox.removeEventListener('change', this.events.stockChange);
        }

        if (this.dom.limiteSelect && this.events.limiteChange) {
            this.dom.limiteSelect.removeEventListener('change', this.events.limiteChange);
        }

        [this.dom.modal, this.dom.modalLotes].forEach((el) => {
            if (!el) return;
            const instance = bootstrap.Modal.getInstance(el);
            if (instance) {
                instance.hide();
                instance.dispose();
            }
        });

        const container = document.getElementById('modal-container');
        if (container) container.innerHTML = '';

        this.events = {};
        this.dom = {};
        this.currentData = [];
        this.onSubmitCallback = null;
        this.opciones = null;
    }

    // =========================================================
    // CONSULTAR DATOS
    // =========================================================
    async getData(filtro = {}) {
        showLoadingWithTetrisLogo('Buscando coincidencias...', 'ISA Software');

        const modal = this.dom.modal;
        const val = (selector) => modal?.querySelector(selector)?.value ?? '';
        const checked = (el) => (el?.checked ? 'S' : '');

        const descripcion = filtro?.data ?? val('#busqueda-descripcion');

        const data = {
            descripcion,
            laboratorio: val('#busqueda-laboratorio'),
            criterio1: val('#busqueda-criterio1'),
            criterio2: val('#busqueda-criterio2'),
            criterio3: val('#busqueda-criterio3'),
            criterio4: val('#busqueda-criterio4'),
            con_stock: checked(this.dom.stockCheckbox),
            institucional: checked(modal?.querySelector('#busqueda-institucional')),
            controlado: checked(modal?.querySelector('#busqueda-controlado')),
            regulado: checked(modal?.querySelector('#busqueda-regulado')),
            cod_barras: filtro?.cod_barras ?? false,
            LimiteRegistros: this.dom.limiteSelect?.value ?? this.opciones.limite_registros,
            link: '../models/Referencias.php',
            op: 'listar_referencias'
        };


        if (filtro?.lista !== undefined && filtro.lista !== null) data.lista = filtro.lista;
        if (filtro?.bodega !== undefined && filtro.bodega !== null) data.bodega = filtro.bodega;

        const resp = await enviarPeticion(data);
        dissminSwal();

        return resp;
    }

    // =========================================================
    // CONSULTAR REGISTROS
    // =========================================================
    async getRecords(filtro) {
        try {
            const resp = await this.getData(filtro);
            this.setNrecords(resp.data.length);

            if (resp.data.length === 0) {
                Swal.fire('Ups', 'No se encontraron coincidencias', 'error');
                return;
            }

            const modalVisible = $(`#${this.modalId}`).hasClass('show');

            if (!modalVisible && resp.data.length === 1) {
                return resp.data;
            }

            this.showResult(resp.data);

            if (modalVisible) {
                return resp.data;
            }

        } catch (e) {
            Swal.fire('Error', 'Se ha generado un error interno en la aplicación', 'error');
            // Antes: removía filas de ".tabla-busqueda-referencias" (clase inexistente)
            if (this.dom.resultContainer) this.dom.resultContainer.innerHTML = '';
            console.error(e);
        }
    }

    // =========================================================
    // FORMATEO DE CANTIDADES (antes era un closure recreado en cada fila)
    // =========================================================
    static esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    static formatCantidad(valor, can_und, des_und_comp) {
        if (!(valor > 0)) return 0;

        const enteros = Math.floor(valor / can_und);
        const resto = ((valor / can_und) - enteros).toFixed(0);

        return `${enteros} ${des_und_comp} y ${resto}`;
    }

    // =========================================================
    // CONSTRUIR UNA FILA DE LA TABLA DE RESULTADOS
    // =========================================================
    buildRow(element, idx, tab) {
        let can_und = 1;
        let des_und_comp = 'UND';

        switch (element.und_compra) {
            case '1': can_und = element.cant_1; des_und_comp = element.und_1; break;
            case '2': can_und = element.cant_2; des_und_comp = element.und_2; break;
            case '3': can_und = element.cant_3; des_und_comp = element.und_3; break;
            case '4': can_und = element.cant_4; des_und_comp = element.und_4; break;
        }

        const costo = element.costo_unitario * can_und;
        const preciob = parseFloat(element.precio) * can_und;

        const precion_und1 = element.cant_1 > 0 ? element.precion * element.cant_1 : 0;
        const precion_und2 = element.cant_2 > 0 ? element.precion * element.cant_2 : 0;
        const precion_und3 = element.cant_3 > 0 ? element.precion * element.cant_3 : 0;
        const precion_und4 = element.cant_4 > 0 ? element.precion * element.cant_4 : 0;

        const stock_und = ModalBusquedaProducto.formatCantidad(element.total_stock, can_und, des_und_comp);
        const comprometidas = ModalBusquedaProducto.formatCantidad(element.comprometidas, can_und, des_und_comp);
        const disponible = ModalBusquedaProducto.formatCantidad(element.disponible, can_und, des_und_comp);

        element.stock_und = stock_und;
        element.des_und_comp = des_und_comp;
        element.can_und = can_und;

        // Antes se comparaba el string ya formateado (element.stock_und <= 0),
        // lo que funcionaba solo por coerción accidental de tipos. Se usa el
        // valor numérico original, que es más claro y robusto.
        const color_stock = element.total_stock <= 0 ? 'text-danger' : 'text-primary';

        const semaforoRojo = element.semaforo_rojo != '0'
            ? '<i class="fa-solid fa-hourglass-end text-danger"></i>' : '';
        const semaforoNaranja = element.semaforo_naranja != '0'
            ? '<i class="fa-solid fa-hourglass text-warning"></i>' : '';
        const iconoComision = element.comision === 'S'
            ? '<i class="fa-solid fa-hand-holding-dollar text-success fw-bold"></i>' : '';
        const iconoDescuento = parseFloat(element.dcto) > 0
            ? '<i class="fa-solid fa-certificate fa-flip text-primary fw-bold"></i>' : '';

        const esc = ModalBusquedaProducto.esc;
        const celdaUnd = (n, valor) =>
            `<td data-label="${esc(element['und_' + n] || 'Und ' + n)}" class="${valor > 0 ? '' : 'vacio'}">${formatNum(valor, '$')}</td>`;

        return `
            <tr class="fila-busqueda-referencias" data-idx="${idx}" tabindex="${tab}">
                <td class="td-codigo">${esc(element.codigo)}</td>
                <td class="td-full">
                    <div style="font-size: 0.95rem; font-weight: 600;">
                        <span>${esc(element.descripcion)}</span>
                        <span class="text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                            ${semaforoRojo}
                            ${semaforoNaranja}
                            ${iconoComision}
                            ${iconoDescuento}
                        </span>
                        <br>
                        <small class="text-primary">${esc(element.laboratorio)}</small>
                    </div>
                </td>
                <td class="d-none">${formatNum(costo, '$')}</td>
                <td data-label="P.bruto" class="fw-semibold text-primary">${formatNum(preciob, '$')}</td>
                <td data-label="Dcto">${esc(element.dcto)}</td>
                <td data-label="Iva">${esc(element.porcentaje_iva)}</td>
                ${celdaUnd(1, precion_und1)}
                ${celdaUnd(2, precion_und2)}
                ${celdaUnd(3, precion_und3)}
                ${celdaUnd(4, precion_und4)}
                <td data-label="Stock" class="${color_stock}">${stock_und}</td>
                <td data-label="Comp" class="text-danger">${comprometidas}</td>
                <td data-label="Disp" class="text-primary fw-semibold">${disponible}</td>
            </tr>
        `;
    }

    // =========================================================
    // MOSTRAR RESULTADOS
    // =========================================================
    showResult(data) {
        this.currentData = data;

        // Antes: concatenación de string en un forEach; ahora array + join
        // (más eficiente, sobre todo con LimiteRegistros = "Sin limite").
        const filas = data.map((element, idx) => this.buildRow(element, idx, 100 + idx)).join('');

        const tabla = `
            <table class="table table-sm table-hover tabla-busqueda-componete mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th class="d-none">Costo</th>
                        <th>P.bruto</th>
                        <th>Dcto</th>
                        <th>Iva</th>
                        <th>P.und_1</th>
                        <th>P.und_2</th>
                        <th>P.und_3</th>
                        <th>P.und_4</th>
                        <th>Stock</th>
                        <th>Comp</th>
                        <th>Disp</th>
                    </tr>
                </thead>
                <tbody>${filas}</tbody>
            </table>
        `;

        if (this.dom.resultContainer) {
            this.dom.resultContainer.innerHTML = tabla;
        }

        const esTactil = window.matchMedia?.('(pointer: coarse)').matches;
        if (esTactil) {
            this.dom.resultContainer?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            setTimeout(() => {
                this.dom.resultContainer?.querySelector('table tbody tr:first-child')?.focus();
            }, 300);
        }
    }
}
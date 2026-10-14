// /controllers/calendarioPagos.js
// NOTA: pese a estar dentro de /controllers por la estructura original del
// proyecto, este archivo es JavaScript de frontend (jQuery + AJAX), no un
// controlador PHP. El controlador PHP real es /controllers/calendarioPagos.php

$(document).ready(function () {

    // URL del endpoint AJAX. Ajustar si el proyecto usa un router central.
    const URL_CONTROLADOR = '../models/calendarioPagos.php';

    const NOMBRES_MES = [
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
    ];

    const hoy = new Date();
    let mesActual = hoy.getMonth() + 1; // JS: 0-11 -> convertimos a 1-12
    let anioActual = hoy.getFullYear();

    // Guardamos en memoria los datos del mes cargado para no volver a pedirlos
    // al abrir el modal de detalle (ya vienen completos desde el AJAX).
    let datosMesActual = {};

    inicializarCalendario();

    // Si esta vista está dentro de un iframe, informamos al padre nuestra
    // altura real para que ajuste el iframe y evite el doble scroll.
    $(window).on('resize', enviarAlturaAlPadre);

    $('#btnMesAnterior').on('click', function () {
        mesActual--;
        if (mesActual < 1) {
            mesActual = 12;
            anioActual--;
        }
        cargarMes(mesActual, anioActual);
    });

    $('#btnMesSiguiente').on('click', function () {
        mesActual++;
        if (mesActual > 12) {
            mesActual = 1;
            anioActual++;
        }
        cargarMes(mesActual, anioActual);
    });

    // Delegado porque las celdas/badges se generan dinámicamente
    $('#gridCalendario').on('click', '.badge-documento', function () {
        const fecha = $(this).data('fecha');
        const indice = $(this).data('indice');

        const documento = (datosMesActual[fecha] || [])[indice];

        if (!documento) {
            return;
        }

        mostrarDetalleDocumento(documento);
    });

    function inicializarCalendario() {
        cargarMes(mesActual, anioActual);
    }

    function cargarMes(mes, anio) {
        $('#tituloMesAnio').text(NOMBRES_MES[mes - 1] + ' ' + anio);
        $('#alertaErrorCalendario').addClass('d-none').text('');
        $('#encabezadoDias').addClass('d-none');
        $('#gridCalendario').empty();

        showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software');

        $.ajax({
            url: URL_CONTROLADOR,
            type: 'POST',
            data: { mes: mes, anio: anio },
            dataType: 'json',
            success: function (respuesta) {
                dissminSwal();

                if (!respuesta.success) {
                    mostrarError(respuesta.message || 'No fue posible cargar la información.');
                    return;
                }

                datosMesActual = respuesta.data || {};
                $('#encabezadoDias').removeClass('d-none');
                construirGrid(mes, anio, datosMesActual);
                enviarAlturaAlPadre();
            },
            error: function () {
                dissminSwal();
                mostrarError('Error de comunicación con el servidor. Intenta nuevamente.');
                enviarAlturaAlPadre();
            }
        });
    }

    function mostrarError(mensaje) {
        $('#alertaErrorCalendario').removeClass('d-none').text(mensaje);
    }

    /**
     * Envía al documento padre (si esta vista está dentro de un iframe)
     * la altura real del contenido, para que el padre pueda ajustar el
     * alto del iframe dinámicamente y evitar scroll interno duplicado.
     *
     * En la página padre debe existir un listener como:
     *
     *   window.addEventListener('message', function (event) {
     *       if (event.data && event.data.tipo === 'calendarioPagosAltura') {
     *           document.getElementById('iframeCalendarioFacturas')
     *               .style.height = event.data.altura + 'px';
     *       }
     *   });
     */
    function enviarAlturaAlPadre() {
        // Pequeño margen para no dejar el borde inferior justo al límite
        const altura = document.body.scrollHeight + 10;

        if (window.parent && window.parent !== window) {
            window.parent.postMessage({ tipo: 'calendarioPagosAltura', altura: altura }, '*');
        }
    }

    function construirGrid(mes, anio, datosPorFecha) {
        const $grid = $('#gridCalendario');
        $grid.empty();

        const primerDiaMes = new Date(anio, mes - 1, 1);
        const diaSemanaInicio = primerDiaMes.getDay(); // 0=domingo ... 6=sábado
        const diasEnMes = new Date(anio, mes, 0).getDate(); // maneja 28/29/30/31 automáticamente

        // Fecha de hoy, para resaltar su celda solo cuando el mes/año visible coincide
        const ahora = new Date();
        const esMesActualVisible = (ahora.getFullYear() === anio && (ahora.getMonth() + 1) === mes);
        const diaDeHoy = ahora.getDate();

        let $filaActual = $('<div class="row g-1 mb-1"></div>');
        $grid.append($filaActual);

        // Celdas vacías antes del día 1 (para alinear con el día de la semana correcto)
        for (let i = 0; i < diaSemanaInicio; i++) {
            $filaActual.append('<div class="col border rounded bg-light celda-vacia"></div>');
        }

        let columnasEnFilaActual = diaSemanaInicio;

        for (let dia = 1; dia <= diasEnMes; dia++) {
            if (columnasEnFilaActual === 7) {
                $filaActual = $('<div class="row g-1 mb-1"></div>');
                $grid.append($filaActual);
                columnasEnFilaActual = 0;
            }

            const fechaCelda = formatearFechaYMD(anio, mes, dia);
            const documentosDelDia = datosPorFecha[fechaCelda] || [];
            const esHoy = esMesActualVisible && dia === diaDeHoy;

            const $celda = $('<div class="col border rounded p-1 celda-dia"></div>');

            if (esHoy) {
                $celda.addClass('bg-opacity-10 border-primary border-2');
            }

            const $numeroDia = $('<div class="small fw-bold"></div>').text(dia);

            if (esHoy) {
                $numeroDia.addClass('text-primary');
            } else {
                $numeroDia.addClass('text-muted');
            }

            $celda.append($numeroDia);

            documentosDelDia.forEach(function (doc, indice) {
                const esVencido = doc.dias_transcurridos > 0;
                const claseColor = esVencido ? 'bg-danger' : 'bg-warning text-dark';
                const icono = esVencido ? 'fa-triangle-exclamation' : 'fa-clock';

                const $badge = $('<span></span>')
                    .addClass('badge ' + claseColor + ' badge-documento d-block text-truncate mb-1')
                    .css('cursor', 'pointer')
                    .attr('title', doc.documento + ' - ' + doc.razon_comercial)
                    .attr('data-fecha', fechaCelda)
                    .attr('data-indice', indice)
                    .html('<i class="fa-solid ' + icono + '"></i> ' + doc.documento);

                $celda.append($badge);
            });

            $filaActual.append($celda);
            columnasEnFilaActual++;
        }

        // Completar la última fila con celdas vacías si el mes no termina en sábado
        while (columnasEnFilaActual < 7 && columnasEnFilaActual !== 0) {
            $filaActual.append('<div class="col border rounded bg-light celda-vacia"></div>');
            columnasEnFilaActual++;
        }
    }

    function mostrarDetalleDocumento(doc) {
        const esVencido = doc.dias_transcurridos > 0;

        $('#detDocumento').text(doc.documento);
        $('#detTercero').text(doc.razon_comercial);
        $('#detNit').text(doc.nit);
        $('#detValorTotal').text(formatearMoneda(doc.valor_total));
        $('#detVencimiento').text(doc.vencimiento);

        const $saldo = $('#detSaldo').text(formatearMoneda(doc.saldo));
        $saldo.toggleClass('text-danger', esVencido).toggleClass('text-dark', !esVencido);

        $('#detDiasVencido').html(
            esVencido
                ? '<span class="text-danger"><i class="fa-solid fa-triangle-exclamation"></i> ' + doc.dias_transcurridos + ' día(s)</span>'
                : '<span class="text-success"><i class="fa-solid fa-circle-check"></i> Sin vencer</span>'
        );

        const $badgeEstado = $('#detEstadoBadge');
        if (esVencido) {
            $badgeEstado
                .removeClass('bg-warning text-dark')
                .addClass('bg-danger')
                .html('<i class="fa-solid fa-triangle-exclamation"></i> Vencido');
        } else {
            $badgeEstado
                .removeClass('bg-danger')
                .addClass('bg-warning text-dark')
                .html('<i class="fa-solid fa-clock"></i> Pendiente');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalDetalleDocumento'));
        modal.show();
    }

    function formatearFechaYMD(anio, mes, dia) {
        const mesStr = String(mes).padStart(2, '0');
        const diaStr = String(dia).padStart(2, '0');
        return anio + '-' + mesStr + '-' + diaStr;
    }

    function formatearMoneda(valor) {
        return '$ ' + Number(valor).toLocaleString('es-CO', { minimumFractionDigits: 2 });
    }

});
/* ============================================================
   ISA SOFTWARE - THEME TOGGLE
   - Usa el atributo "data-color-scheme" (NO "data-theme", ese ya
     lo usa el template para elegir el skin/tema base).
   - Aplica el tema guardado al cargar la página.
   - NO utiliza opacity para ocultar la página o los iframes.
   - NO utiliza transición de opacity.
   - Sincroniza RECURSIVAMENTE cualquier <iframe> del mismo origen,
     sin importar la profundidad.
   - También detecta iframes insertados dinámicamente.
   - Expone window.toggleTheme() para el switch de la interfaz.
   ============================================================ */

(function () {

  "use strict";

  var STORAGE_KEY = "isa_theme";
  var ATTR = "data-color-scheme";


  /* ============================================================
     OBTENER TEMA GUARDADO
     ============================================================ */

  function getSavedTheme() {

    try {

      return localStorage.getItem(STORAGE_KEY) || "light";

    } catch (e) {

      return "light";

    }

  }


  /* ============================================================
     OBTENER URL DEL DARK THEME
     ============================================================ */

  function getDarkThemeHref(doc) {

    /*
     * Primero busca dark-theme.css dentro del documento actual.
     */

    var link = doc.querySelector(
      'link[href*="dark-theme.css"]'
    );

    if (link) {

      return link.href;

    }


    /*
     * Si no existe dentro del iframe,
     * utiliza el dark-theme.css de la página principal.
     */

    var topLink = document.querySelector(
      'link[href*="dark-theme.css"]'
    );

    return topLink ? topLink.href : null;

  }


  /* ============================================================
     CARGAR DARK THEME
     
     IMPORTANTE:
     NO OCULTAMOS EL DOCUMENTO.
     NO USAMOS OPACITY.
     ============================================================ */

  function ensureDarkTheme(doc) {

    var darkHref = getDarkThemeHref(doc);

    if (!darkHref) {

      return;

    }


    /*
     * Evita insertar el mismo CSS varias veces.
     */

    if (
      doc.querySelector(
        'link[data-injected-dark-theme]'
      )
    ) {

      return;

    }


    var link = doc.createElement("link");

    link.rel = "stylesheet";

    link.href = darkHref;

    link.setAttribute(
      "data-injected-dark-theme",
      "true"
    );


    /*
     * Insertamos el CSS sin esconder el documento.
     */

    (doc.head || doc.documentElement)
      .appendChild(link);

  }


  /* ============================================================
     APLICAR TEMA A UN DOCUMENTO
     ============================================================ */

  function applyThemeToDocument(doc, theme) {

    if (
      !doc ||
      !doc.documentElement
    ) {

      return;

    }


    if (theme === "dark") {

      doc.documentElement.setAttribute(
        ATTR,
        "dark"
      );

    } else {

      doc.documentElement.removeAttribute(
        ATTR
      );

    }


    /*
     * Nos aseguramos de que exista el CSS oscuro.
     */

    ensureDarkTheme(doc);

  }


  /* ============================================================
     CONTROL DE IFRAMES
     
     IMPORTANTE:
     YA NO HACEMOS:

       opacity: 0
       opacity: 1
       theme-loading
       transition

     El iframe permanece visible.
     ============================================================ */

  function bindIframeOnce(frame) {

    if (
      !frame ||
      frame.__themeBound
    ) {

      return;

    }


    frame.__themeBound = true;


    /*
     * Cuando el iframe termina de cargar,
     * aplicamos inmediatamente el tema.
     */

    frame.addEventListener(
      "load",
      function () {

        try {

          var innerDoc =
            frame.contentDocument;


          if (innerDoc) {

            watchDocument(
              innerDoc,
              getSavedTheme()
            );

          }

        } catch (e) {

          /*
           * Si pertenece a otro origen,
           * no podemos modificarlo.
           */

        }

      }
    );


    /*
     * Detectar cambios dinámicos del src.
     */

    if (window.MutationObserver) {

      new MutationObserver(
        function () {

          try {

            var innerDoc =
              frame.contentDocument;


            if (
              innerDoc &&
              innerDoc.documentElement
            ) {

              watchDocument(
                innerDoc,
                getSavedTheme()
              );

            }

          } catch (e) {

            /*
             * Otro origen.
             */

          }

        }
      ).observe(
        frame,
        {
          attributes: true,
          attributeFilter: ["src"]
        }
      );

    }

  }


  /* ============================================================
     DETECTAR NUEVOS IFRAMES
     
     Esto permite que funcione cuando ISA crea un iframe
     dinámicamente, por ejemplo dentro de un modal.
     ============================================================ */

  function watchNewIframes(doc) {

    if (
      doc.__themeObserverBound ||
      !doc.body ||
      !window.MutationObserver
    ) {

      return;

    }


    doc.__themeObserverBound = true;


    new MutationObserver(
      function (mutations) {

        for (
          var m = 0;
          m < mutations.length;
          m++
        ) {

          var added =
            mutations[m].addedNodes;


          for (
            var n = 0;
            n < added.length;
            n++
          ) {

            var node =
              added[n];


            /*
             * Solo elementos HTML.
             */

            if (
              node.nodeType !== 1
            ) {

              continue;

            }


            var frames = [];


            /*
             * Si el nodo agregado es directamente
             * un iframe.
             */

            if (
              node.tagName === "IFRAME"
            ) {

              frames.push(node);

            }


            /*
             * Buscar iframes dentro del nodo.
             */

            if (
              node.querySelectorAll
            ) {

              var nested =
                node.querySelectorAll(
                  "iframe"
                );


              for (
                var k = 0;
                k < nested.length;
                k++
              ) {

                frames.push(
                  nested[k]
                );

              }

            }


            /*
             * Procesar todos los iframes encontrados.
             */

            for (
              var f = 0;
              f < frames.length;
              f++
            ) {

              bindIframeOnce(
                frames[f]
              );


              try {

                if (
                  frames[f]
                    .contentDocument
                ) {

                  watchDocument(
                    frames[f]
                      .contentDocument,
                    getSavedTheme()
                  );

                }

              } catch (e) {

                /*
                 * Otro origen.
                 */

              }

            }

          }

        }

      }
    ).observe(
      doc.body,
      {
        childList: true,
        subtree: true
      }
    );

  }


  /* ============================================================
     VIGILAR DOCUMENTO
     
     Esta es la función central del sistema.
     ============================================================ */

  function watchDocument(
    doc,
    theme
  ) {

    if (
      !doc ||
      !doc.documentElement
    ) {

      return;

    }


    try {

      /*
       * Aplicar tema inmediatamente.
       */

      applyThemeToDocument(
        doc,
        theme
      );

    } catch (e) {

      /*
       * Documento inaccesible.
       */

      return;

    }


    /*
     * Buscar todos los iframes.
     */

    var frames =
      doc.querySelectorAll(
        "iframe"
      );


    /*
     * Recorrer cada iframe.
     */

    for (
      var i = 0;
      i < frames.length;
      i++
    ) {

      bindIframeOnce(
        frames[i]
      );


      try {

        var innerDoc =
          frames[i]
            .contentDocument;


        /*
         * Si podemos acceder al documento
         * interno, aplicar el tema.
         */

        if (
          innerDoc &&
          innerDoc.documentElement
        ) {

          watchDocument(
            innerDoc,
            theme
          );

        }

      } catch (e) {

        /*
         * Otro origen.
         */

      }

    }


    /*
     * Vigilar nuevos iframes.
     */

    watchNewIframes(doc);

  }


  /* ============================================================
     APLICAR TEMA
     ============================================================ */

  function applyTheme(theme) {

    /*
     * Normalizar valor.
     */

    theme =
      theme === "dark"
        ? "dark"
        : "light";


    /*
     * Guardar preferencia.
     */

    try {

      localStorage.setItem(
        STORAGE_KEY,
        theme
      );

    } catch (e) {

      /*
       * localStorage no disponible.
       */

    }


    /*
     * Aplicar a la página principal
     * y todos sus iframes.
     */

    watchDocument(
      document,
      theme
    );


    /* ========================================================
       SWITCH
       ======================================================== */

    var switchEl =
      document.getElementById(
        "theme-switch"
      );


    if (switchEl) {

      switchEl.checked =
        theme === "dark";

    }


    /* ========================================================
       ICONO SOL / LUNA
       ======================================================== */

    var iconEl =
      document.getElementById(
        "theme-toggle-icon"
      );


    if (iconEl) {

      iconEl.classList.remove(
        "bx-sun",
        "bx-moon"
      );


      iconEl.classList.add(
        theme === "dark"
          ? "bx-sun"
          : "bx-moon"
      );

    }

  }


  /* ============================================================
     INICIALIZACIÓN
     
     Se ejecuta inmediatamente.
     ============================================================ */

  applyTheme(
    getSavedTheme()
  );


  /* ============================================================
     TOGGLE THEME
     ============================================================ */

  window.toggleTheme =
    function () {

      var current =
        document.documentElement
          .getAttribute(ATTR)
          === "dark"
          ? "dark"
          : "light";


      applyTheme(
        current === "dark"
          ? "light"
          : "dark"
      );

    };


  /* ============================================================
     SET THEME
     ============================================================ */

  window.setTheme =
    function (theme) {

      applyTheme(
        theme === "dark"
          ? "dark"
          : "light"
      );

    };


})();

$(function() {
  // Configuración de Dropzone
  var myDropzone = new Dropzone("#form2", {
    url: "/upload", // URL donde se enviará el archivo
    method: "post", // Método HTTP
    paramName: "file", // Nombre del campo que recibirá el archivo en el servidor
    parallelUploads: 1, // Número máximo de archivos a subir a la vez//
    maxFilesize: 5, // Tamaño máximo de archivo en MB
    acceptedFiles: ".jpg,.jpeg,.png,.gif", // Tipos de archivos aceptados
    maxFiles: 1, // Número máximo de archivos
    addRemoveLinks: true, // Agregar links para eliminar archivos subidos
    previewTemplate: `<div class="dz-preview dz-file-preview text-center">
                        <div class="dz-details">
                          <div class="dz-thumbnail">
                            <img data-dz-thumbnail>
                            <span class="dz-nopreview">No preview</span>
                            <div class="dz-success-mark"></div>
                            <div class="dz-error-mark"></div>
                            <div class="dz-error-message"><span data-dz-errormessage></span></div>
                            <div class="progress">
                              <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
                            </div>
                          </div>
                          <div class="dz-filename" data-dz-name></div>
                          <div class="dz-size" data-dz-size></div>
                        </div>
                      </div>`,
    init: function() {
      // Este evento se ejecuta después de que la configuración haya sido inicializada
      this.on("success", function(file, response) {
        console.log("Archivo cargado exitosamente:", response);
      });

      this.on("error", function(file, response) {
        console.log("Error al cargar el archivo:", response);
      });
    }
  });

});

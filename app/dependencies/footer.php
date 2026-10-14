<script src="../../assets/vendor/libs/jquery/jquery.js"></script>
<script src="../../assets/vendor/libs/jquery/jquery-ui/jquery-ui.js"></script>
<script src="../../assets/vendor/js/helpers.js"></script>
<script src="../../assets/js/config.js"></script>
<script src="../../assets/vendor/libs/popper/popper.js"></script>
<script src="../../assets/vendor/js/bootstrap.js"></script>
<script src="../../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="../../assets/vendor/js/menu.js"></script>
<script src="../../assets/vendor/css/SwalAlert2/dist/js/sweetalert2.all.min.js"></script>
<script src="../../assets/vendor/libs/apex-charts/apexcharts.js"></script>
<script src="../../assets/js/main.js"></script>
<script src="../../assets/js/ui-toasts.js"></script>
<script src="../shared/servicios.js?<?= rand() ?>"></script>
<script src="../../libs/DataTable/datatables.min.js"></script>
<script src="../shared/shared.js?<?= rand() ?>"></script>
<script src="../shared/Storage.js?<?php echo (rand()); ?>"></script>
<script src="../shared/busqueda-referencias-componente.js?<?php echo  rand();  ?>"></script>
<script src="../shared/busqueda-terceros-componente.js?<?php echo  rand();  ?>"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>
    showLoadingWithTetrisLogo('Cargando por favor espere...', 'ISA Software');
</script>

<?php if (!empty($scriptsPagina)): ?>

    <?php foreach ($scriptsPagina as $script): ?>
        <script src="<?= htmlspecialchars($script) ?>"></script>
    <?php endforeach; ?>

<?php endif; ?>
<!-- URL -->
<script src="https://cdn.jsdelivr.net/npm/cleave.js/dist/cleave.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="../../libs/jQuery-Mask-Plugin-master/dist/jquery.mask.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js" integrity="sha512-F5Ul1uuyFlGnIT1dk2c4kB4DBdi5wnBJjVhL7gQlGh46Xn0VhvD8kgxLtjdZ5YN83gybk/aASUAlpdoWUjRR3g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</body>

</html>
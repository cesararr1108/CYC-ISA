#!/bin/bash
# VERIFICADOR DE INSTALACIÓN - Listados de Precios Proveedores

echo "================================================"
echo "  VERIFICADOR DE INSTALACIÓN"
echo "  Módulo: Listados de Precios Proveedores"
echo "================================================"
echo ""

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para verificar archivo
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1"
        return 1
    fi
}

# Función para verificar directorio
check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} Directorio: $1"
        return 0
    else
        echo -e "${RED}✗${NC} Directorio: $1"
        return 1
    fi
}

echo "1. VERIFICANDO DIRECTORIOS"
echo "=========================="
check_dir "app/models"
check_dir "app/shared/servicios"
check_dir "app/controllers"
check_dir "app/views"
echo ""

echo "2. VERIFICANDO ARCHIVOS DE CÓDIGO"
echo "================================="
check_file "app/models/ListadosPreciosProveedor.php"
check_file "app/shared/servicios/procesarListadoPrecios.php"
check_file "app/controllers/ListadosPreciosProveedor.js"
check_file "app/views/Config.php"
echo ""

echo "3. VERIFICANDO ARCHIVOS SQL"
echo "==========================="
check_file "app/models/create_tabla_listados_precios.sql"
echo ""

echo "4. VERIFICANDO DOCUMENTACIÓN"
echo "============================="
check_file "GUIA_RAPIDA.md"
check_file "INSTALACION_LISTADOS_PRECIOS.md"
check_file "RESUMEN_IMPLEMENTACION.md"
check_file "EJEMPLO_LISTADO_PRECIOS.csv"
check_file "REGISTRO_CAMBIOS.md"
echo ""

echo "5. VERIFICACIÓN DE PERMISOS"
echo "==========================="
if [ -w "app/models" ]; then
    echo -e "${GREEN}✓${NC} app/models: escritura habilitada"
else
    echo -e "${RED}✗${NC} app/models: sin permisos de escritura"
fi

if [ -w "app/shared/servicios" ]; then
    echo -e "${GREEN}✓${NC} app/shared/servicios: escritura habilitada"
else
    echo -e "${RED}✗${NC} app/shared/servicios: sin permisos de escritura"
fi
echo ""

echo "6. TAMAÑO DE ARCHIVOS"
echo "===================="
echo -n "ListadosPreciosProveedor.php: "
[ -f "app/models/ListadosPreciosProveedor.php" ] && wc -c < "app/models/ListadosPreciosProveedor.php" || echo "N/A"

echo -n "procesarListadoPrecios.php: "
[ -f "app/shared/servicios/procesarListadoPrecios.php" ] && wc -c < "app/shared/servicios/procesarListadoPrecios.php" || echo "N/A"

echo -n "ListadosPreciosProveedor.js: "
[ -f "app/controllers/ListadosPreciosProveedor.js" ] && wc -c < "app/controllers/ListadosPreciosProveedor.js" || echo "N/A"
echo ""

echo "7. PRÓXIMOS PASOS"
echo "================"
echo "1. Ejecuta el SQL para crear la tabla:"
echo "   - Abre phpMyAdmin"
echo "   - Selecciona tu base de datos"
echo "   - Ve a la pestaña SQL"
echo "   - Copia el contenido de: app/models/create_tabla_listados_precios.sql"
echo "   - Ejecuta el script"
echo ""
echo "2. Recarga tu aplicación en el navegador (Ctrl+Shift+R)"
echo ""
echo "3. Ve a Configuración → Listados de Precios Proveedor"
echo ""
echo "4. Descarga la plantilla y prueba con un archivo de ejemplo"
echo ""

echo "================================================"
echo -e "${GREEN}✓ VERIFICACIÓN COMPLETADA${NC}"
echo "================================================"

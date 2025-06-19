#!/bin/bash

# Colores para mensajes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🚨 ADVERTENCIA 🚨${NC}"
echo -e "Este script eliminará TODAS las configuraciones del plugin Bricks API Integrator:"
echo "- Todos los endpoints configurados"
echo "- Todos los sources"
echo "- Todos los query types generados"
echo "- Todas las configuraciones y caché"
echo ""
echo -e "${RED}Esta acción NO se puede deshacer.${NC}"
echo ""
read -p "¿Estás seguro de que quieres continuar? (escribe 'SI' para confirmar): " confirmation

if [ "$confirmation" != "SI" ]; then
    echo -e "${GREEN}Operación cancelada. No se ha eliminado nada.${NC}"
    exit 0
fi

# Verificar que estamos en el directorio correcto
if [ ! -f "cleanup-all.php" ]; then
    echo -e "${RED}Error: No se encuentra el archivo cleanup-all.php${NC}"
    echo "Asegúrate de ejecutar este script desde el directorio del plugin"
    exit 1
fi

echo -e "${YELLOW}Iniciando proceso de limpieza...${NC}"

# Ejecutar el script de limpieza
php cleanup-all.php

echo -e "${GREEN}✅ Proceso de limpieza completado${NC}"
echo ""
echo -e "${YELLOW}Próximos pasos:${NC}"
echo "1. Ir al panel de administración de Bricks API Integrator"
echo "2. Configurar los nuevos endpoints"
echo "3. Crear los nuevos sources"
echo "4. Regenerar los query types y tags"
echo ""
echo -e "${GREEN}¡Listo para empezar desde cero!${NC}" 
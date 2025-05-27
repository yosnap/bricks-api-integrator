<?php
/**
 * Field Extractor - Extracción dinámica de campos
 * 
 * @package BricksAPIIntegrator
 * @version 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para extracción de campos
 */
trait FieldExtractor {
    
    /**
     * Extraer campos dinámicamente - VERSIÓN MEJORADA PARA DATOS COMPLEJOS
     */
    public function extract_fields_from_data($data, $prefix = '', $max_depth = 3) {
        $fields = [];
        
        if (!is_array($data) && !is_object($data)) {
            return $fields;
        }
        
        foreach ((array)$data as $key => $value) {
            $field_key = $prefix ? $prefix . '_' . $key : $key;
            $field_key = $this->sanitize_field_key($field_key);
            
            if (is_array($value)) {
                // Detectar tipo de array
                $array_type = $this->detect_array_type($value);
                
                switch ($array_type) {
                    case 'simple_list':
                        // Array simple: ["valor1", "valor2", "valor3"]
                        $fields[$field_key] = $this->format_field_label($key) . ' (Lista)';
                        $fields[$field_key . '_first'] = $this->format_field_label($key) . ' (Primero)';
                        $fields[$field_key . '_count'] = $this->format_field_label($key) . ' (Cantidad)';
                        $fields[$field_key . '_join'] = $this->format_field_label($key) . ' (Unido)';
                        break;
                        
                    case 'object_list':
                        // Array de objetos: [{"nombre": "Juan", "edad": 30}, {...}]
                        $fields[$field_key] = $this->format_field_label($key) . ' (Lista de Objetos)';
                        $fields[$field_key . '_count'] = $this->format_field_label($key) . ' (Cantidad)';
                        $fields[$field_key . '_join'] = $this->format_field_label($key) . ' (Unidos)';
                        
                        // Extraer TODOS los campos del primer objeto (no solo algunos)
                        if (!empty($value[0]) && (is_array($value[0]) || is_object($value[0]))) {
                            foreach ((array)$value[0] as $sub_key => $sub_value) {
                                $sub_field_key = $field_key . '_first_' . $this->sanitize_field_key($sub_key);
                                $fields[$sub_field_key] = $this->format_field_label($key) . ' > ' . $this->format_field_label($sub_key) . ' (Primero)';
                            }
                        }
                        
                        // NUEVO: Generar tags para acceder a elementos específicos por índice
                        if (count($value) > 1) {
                            // Tags para acceder a elementos específicos (útil para bucles)
                            foreach ((array)$value[0] as $sub_key => $sub_value) {
                                // Tag para acceder a cualquier elemento: {tag_item_campo}
                                $item_field_key = $field_key . '_item_' . $this->sanitize_field_key($sub_key);
                                $fields[$item_field_key] = $this->format_field_label($key) . ' > ' . $this->format_field_label($sub_key) . ' (Por Índice)';
                            }
                            
                            // Tags especiales para navegación
                            $fields[$field_key . '_last'] = $this->format_field_label($key) . ' (Último)';
                            foreach ((array)$value[0] as $sub_key => $sub_value) {
                                $last_field_key = $field_key . '_last_' . $this->sanitize_field_key($sub_key);
                                $fields[$last_field_key] = $this->format_field_label($key) . ' > ' . $this->format_field_label($sub_key) . ' (Último)';
                            }
                        }
                        break;
                        
                    case 'associative':
                        // Array asociativo: {"propiedad1": "valor1", "propiedad2": "valor2"}
                        $fields[$field_key] = $this->format_field_label($key) . ' (Objeto)';
                        
                        // Extraer TODOS los campos anidados (aumentado el límite)
                        if ($max_depth > 0) {
                            $nested_fields = $this->extract_fields_from_data($value, $field_key, $max_depth - 1);
                            $fields = array_merge($fields, $nested_fields); // Sin límite artificial
                        }
                        break;
                        
                    case 'mixed':
                        // Array mixto
                        $fields[$field_key] = $this->format_field_label($key) . ' (Datos Mixtos)';
                        $fields[$field_key . '_json'] = $this->format_field_label($key) . ' (JSON)';
                        break;
                }
                
            } elseif (is_object($value)) {
                // Objeto simple
                $fields[$field_key] = $this->format_field_label($key) . ' (Objeto)';
                
                if ($max_depth > 0) {
                    $nested_fields = $this->extract_fields_from_data($value, $field_key, $max_depth - 1);
                    $fields = array_merge($fields, $nested_fields); // Sin límite artificial
                }
                
            } else {
                // Campo simple (string, number, boolean)
                $fields[$field_key] = $this->format_field_label($key);
            }
        }
        
        return $fields;
    }
    
    /**
     * Detectar tipo de array para manejo específico
     */
    private function detect_array_type($array) {
        if (empty($array)) {
            return 'empty';
        }
        
        // Verificar si es array indexado vs asociativo
        $keys = array_keys($array);
        $is_indexed = ($keys === array_keys($keys));
        
        if (!$is_indexed) {
            return 'associative'; // Array asociativo: {"key": "value"}
        }
        
        // Es array indexado, verificar contenido
        $first_element = reset($array);
        $types = array_map('gettype', array_slice($array, 0, 3)); // Revisar primeros 3 elementos
        $unique_types = array_unique($types);
        
        if (count($unique_types) > 1) {
            return 'mixed'; // Tipos mixtos
        }
        
        $dominant_type = $unique_types[0];
        
        switch ($dominant_type) {
            case 'array':
            case 'object':
                return 'object_list'; // Array de objetos/arrays
            case 'string':
            case 'integer':
            case 'double':
            case 'boolean':
                return 'simple_list'; // Lista simple de valores
            default:
                return 'mixed';
        }
    }
    
    /**
     * Tags básicos para endpoint sin datos
     */
    public function get_basic_tags_for_endpoint($endpoint) {
        $basic_fields = [
            'id' => 'ID',
            'name' => 'Nombre',
            'title' => 'Título',
            'description' => 'Descripción',
            'content' => 'Contenido',
            'url' => 'URL',
            'image' => 'Imagen',
            'date' => 'Fecha',
            'status' => 'Estado'
        ];
        
        $tags = [];
        $endpoint_slug = sanitize_key($endpoint['name']);
        
        foreach ($basic_fields as $field => $label) {
            $tags[] = [
                'name' => '{api_' . $endpoint_slug . '_' . $field . '}',
                'label' => $label,
                'group' => $endpoint['name']
            ];
        }
        
        return $tags;
    }
    
    /**
     * Obtener valor de campo del objeto
     */
    public function get_field_value_from_object($object, $field) {
        if (!is_array($object)) {
            $object = (array) $object;
        }
        
        // Búsqueda directa
        if (isset($object[$field])) {
            return $this->format_field_output($object[$field]);
        }
        
        // Búsqueda normalizada
        foreach ($object as $key => $value) {
            if ($this->sanitize_field_key($key) === $field) {
                return $this->format_field_output($value);
            }
        }
        
        // Búsqueda anidada
        foreach ($object as $value) {
            if (is_array($value) || is_object($value)) {
                $result = $this->get_field_value_from_object($value, $field);
                if ($result !== '') {
                    return $result;
                }
            }
        }
        
        return '';
    }
    
    /**
     * UTILITY FUNCTIONS
     */
    
    private function sanitize_field_key($key) {
        return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $key));
    }
    
    private function format_field_label($key) {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }
    
    private function format_field_output($value) {
        if (is_array($value)) {
            return implode(', ', array_filter(array_slice($value, 0, 5)));
        } elseif (is_object($value)) {
            return json_encode($value);
        } elseif (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }
        
        return sanitize_text_field((string) $value);
    }
}

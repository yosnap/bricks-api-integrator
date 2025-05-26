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
     * Extraer campos dinámicamente
     */
    public function extract_fields_from_data($data, $prefix = '', $max_depth = 2) {
        $fields = [];
        
        if (!is_array($data) && !is_object($data)) {
            return $fields;
        }
        
        foreach ((array)$data as $key => $value) {
            $field_key = $prefix ? $prefix . '_' . $key : $key;
            $field_key = $this->sanitize_field_key($field_key);
            
            if ((is_array($value) || is_object($value)) && $max_depth > 0) {
                // Campo principal
                $fields[$field_key] = $this->format_field_label($key);
                
                // Campos anidados (limitados)
                if (is_array($value) && !empty($value) && !is_numeric(array_keys($value)[0])) {
                    $nested_fields = $this->extract_fields_from_data($value, $field_key, $max_depth - 1);
                    $fields = array_merge($fields, array_slice($nested_fields, 0, 3)); // Limitar a 3 campos anidados
                }
            } else {
                $fields[$field_key] = $this->format_field_label($key);
            }
        }
        
        return $fields;
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

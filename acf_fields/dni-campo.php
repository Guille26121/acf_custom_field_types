<?php

add_action('acf/include_field_types', 'registrar_campo_dni_personalizado');

function registrar_campo_dni_personalizado($version) {
    class ACF_Field_DNI extends acf_field {
        
        function __construct() {
            $this->name = 'dni';
            $this->label = __('DNI', 'acf');
            $this->category = 'basic';
            parent::__construct();
        }

		//Renderizado del campo, input sencillo
        function render_field($field) {
			$dni = isset($field['value']) ? strtoupper(str_replace(' ', '', $field['value'])) : '';

			echo '<div class="dni-group" style="display: flex; gap: 5px;">';
			echo '<input type="text" 
						 name="' . esc_attr($field['name']) . '" 
						 value="' . esc_attr($dni) . '" 
						 maxlength="9" 
						 size="9" 
						 required 
						 class="dni-input" />';
			echo '</div>';
		}

		
        // Guardar el valor correctamente
        function update_value($value, $post_id, $field) {
			return (string) preg_replace('/\s+/', '', strtoupper($value));
		}
		
		//Validación del DNI
		function validate_value($valid, $value, $field, $input)
		{
			if (!preg_match('/^[0-9]{8}[A-Za-z]$/', $value)) {
				return __("El DNI no cumple con el formato esperado");
			}

			$letra = substr($value, -1); 
			$numeros = substr($value, 0, 8); 

			$mod = $numeros % 23;

			$letras = "TRWAGMYFPDXBNJZSQVHLCKE";

			if (strtoupper($letra) !== $letras[$mod]) {
				return __("El DNI no cumple con el formato esperado");
			}

			return true;
		}

		
	} 
	new ACF_Field_DNI();
}

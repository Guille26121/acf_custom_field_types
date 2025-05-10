<?php

add_action('acf/include_field_types', 'registrar_campo_iban_personalizado');

function registrar_campo_iban_personalizado($version) {
    class ACF_Field_IBAN extends acf_field {
        
        function __construct() {
            $this->name = 'iban';
            $this->label = __('IBAN', 'acf');
            $this->category = 'basic';
            parent::__construct();
        }

        // Renderizar el campo IBAN
        function render_field($field) {
    $iban = isset($field['value']) ? str_split(str_replace(' ', '', $field['value']), 4) : [];
    	echo '<div class="iban-group" style="display: flex; gap: 5px;">';
       	for ($i = 0; $i < 6; $i++){ 
        echo '<input type="text" 
                     name="' . esc_attr($field['name']) . '[]" 
                     value="' . esc_attr($iban[$i] ?? '') . '" 
                     maxlength="4" 
                     size="4" 
                     required 
                     class="iban-input" />';
         } 
    //Script que maneja el cambio automático de input
    echo '</div>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    const ibanInputs = document.querySelectorAll(".iban-input");

    ibanInputs.forEach((input, index) => {
        input.addEventListener("keydown", (e) => {
            // Corregimos la referencia a input.value
            if (input.value.length === 3 && /^[A-Z0-9]$/i.test(e.key)) {
                e.preventDefault();
                input.value += e.key.toUpperCase();  // Usamos e.key y añadimos el valor al input
                // Enfocar al siguiente input si existe
                if (index < ibanInputs.length - 1) ibanInputs[index + 1].focus();
            }
            
            // Mover al input anterior si el input actual está vacío y se pulsa Backspace
            if (e.key === "Backspace" && input.value === "" && index > 0) {
                ibanInputs[index - 1].focus();
            }
        });
    });
});
    </script>';
		}
		
        // Guardar el valor correctamente
        function update_value($value, $post_id, $field) {
            if (is_array($value)) {
                $value = implode('', $value); // Unir los valores de los inputs
            }
            return (string) preg_replace('/\s+/', '', strtoupper($value)); // Limpiar espacios y formatear
        }
		
	} 
	new ACF_Field_IBAN();
}

//Validación de largaria y utilizando el módulo 
function validate_value_iban($valid, $value, $field, $input)
{

    if ($field["name"] == "iban") {
        if (is_array($value)) {
            $value = implode('', $value);
        }
        $value = (string) preg_replace('/\s+/', '', strtoupper($value));

        if (!preg_match('/^[A-Za-z]{2}[0-9]{13,31}$/', $value)) {
            return __("IBAN no cumple con el formato esperado");
        }
        $prefix = substr($value, 0, 4);
        $rest = substr($value, 4); // Resto del valor

        $letter1 = strtoupper($prefix[0]); // Primera letra
        $letter2 = strtoupper($prefix[1]); // Segunda letra
        $numPart = substr($prefix, 2, 2); // Números

        $alphabet = range('A', 'Z'); // Array con el alfabeto
        $num1 = array_search($letter1, $alphabet) + 10;
        $num2 = array_search($letter2, $alphabet) + 10;

        $newPrefix = $num1 . $num2 . $numPart;

        $newValue = $rest . $newPrefix;

        if (bcmod($newValue, 97) != 1) {
            return __("IBAN no cumple con los criterios de validación");
        }
    }
    return true;
}

add_filter('acf/validate_value/type=iban', 'validate_value_iban', 10, 4);



<?php

function registrar_campo_multifile($version) {
	class ACF_Field_Multi_File extends acf_field {

        function __construct() {
            $this->name = 'multi_file';
            $this->label = __('Archivos múltiples', 'acf');
            $this->category = 'content';
            parent::__construct();
        }

function render_field($field) {
    $value = is_array($field['value']) ? $field['value'] : [];

    $uploader = acf_maybe_get($field, 'uploader', acf_get_setting('uploader'));
    if ($uploader === 'wp') {
        acf_enqueue_uploader();
    }

    $div = array(
        'class' => 'acf-multi-file-uploader',
        'data-uploader' => $uploader,
        'data-library' => $field['library'] ?? 'all',
        'data-mime_types' => $field['mime_types'] ?? '',
        'data-multiple' => '1',
        'data-name' => $field['name'],
    );

    echo '<div ' . acf_esc_attrs($div) . '>';
    // ÚNICO input con todos los IDs separados por coma
    echo '<input type="hidden" class="acf-multi-file-input" name="' . esc_attr($field['name']) . '" value="' . esc_attr(implode(',', $value)) . '">';
	
    // Botón para añadir archivos
    echo '<div class="acf-actions">';
    echo '<a href="#" class="acf-button button" data-name="add">' . __('Add Files', 'acf') . '</a>';
    echo '</div>';

    // Mostrar archivos actuales
    echo '<ul class="acf-file-list" style="display:flex">';
    foreach ($value as $attachment_id) {
        $attachment = acf_get_attachment($attachment_id);
        if ($attachment) {
            echo '<li class="acf-file-item" data-id="' . esc_attr($attachment_id) . '">';
			echo '<a class="acf-link-file" target="_blank" href="'.esc_url($attachment['url']).'">';
            echo '<div class="file-icon"><img src="' . esc_url($attachment['icon']) . '" /></div>';
            echo '<div class="file-info"><strong>' . esc_html($attachment['filename']) . '</strong></div>';
			echo '</a>';
            echo '<a href="#" class="acf-icon -cancel dark" data-name="remove" title="' . esc_attr__('Remove', 'acf') . '"></a>';
            echo '</li>';
        }
    }
    echo '</ul>';

    echo '</div>';

    // JS para manejar todo en un único input y mostrar actualizaciones visuales (borrado, añadido). También el css
    ?>
	<style>
		ul.acf-file-list > li{
			margin-right: 25px;
			width: 90px;
			word-break: break-all;
		}
		ul.acf-file-list{
			flex-wrap: wrap; 
		}
	</style>
    <script>
    (function($){
        function initMultiUploader($el) {
            const $input = $el.find('.acf-multi-file-input');
            const $list = $el.find('.acf-file-list');

            function updateHiddenField() {
                const ids = [];
                $list.find('.acf-file-item').each(function() {
                    ids.push($(this).data('id'));
                });
                $input.val(ids.join(','));
            }

            const frame = wp.media({
                title: "<?php _e('Selecciona archivos', 'acf'); ?>",
                multiple: true,
                library: { type: $el.data('mime_types') || undefined }
            });

            frame.on('select', function() {
                const selection = frame.state().get('selection');
                selection.each(function(attachment) {
                    const id = attachment.id;

                    // Evitar duplicados
                    if ($list.find('.acf-file-item[data-id="' + id + '"]').length > 0) return;

                    const filename = attachment.get('filename');
                    const icon = attachment.attributes.icon || attachment.attributes.url;

                    const item = `
                        <li class="acf-file-item" data-id="${id}">
							<a class="acf-link-file" target="_blank" href="${attachment.attributes.url}">
                            <div class="file-icon"><img src="${icon}" /></div>
                            <div class="file-info"><strong>${filename}</strong></div>
							</a>
                            <a href="#" class="acf-icon -cancel dark" data-name="remove" title="Remove"></a>
                        </li>`;
                    $list.append(item);
                });

                updateHiddenField();
            });

            $el.on('click', '[data-name="add"]', function(e){
                e.preventDefault();
                frame.open();
            });

            $el.on('click', '[data-name="remove"]', function(e){
                e.preventDefault();
                $(this).closest('li').remove();
                updateHiddenField();
            });
        }

        $(document).ready(function(){
            $('.acf-multi-file-uploader').each(function(){
                initMultiUploader($(this));
            });
        });
    })(jQuery);
    </script>
    <?php
}

        

        function update_value($value, $post_id, $field) {
			if (!is_array($value)) {
				$value = explode(',', $value);
			}

			$value = array_filter($value, function($id) {
				return is_numeric($id) && get_post($id);
			});

			return array_values($value);
		}

    }

    new ACF_Field_Multi_File();
}
add_action('acf/include_field_types', 'registrar_campo_multifile');

// Validación de lista de archivos
function validate_value_multifile($valid, $value, $field, $input) {
    // Primero nos aseguramos que $value sea un array
    if (!is_array($value)) {
        $value = explode(',', $value);
    }

    // Verificar que haya al menos un archivo seleccionado
    if (empty($value)) {
        return __('Por favor, selecciona al menos un archivo.', 'acf');
    }

    // Verificar que todos los IDs sean válidos y existan
    $invalid_ids = array_filter($value, function($id) {
        if (!is_numeric($id)) return true;

        $post = get_post($id);
        return !$post || $post->post_type !== 'attachment';
    });

    if (!empty($invalid_ids)) {
        return __('Algunos de los archivos seleccionados no son válidos.', 'acf');
    }

    return $valid;
}


add_filter('acf/validate_value/type=multi_file', 'validate_value_multifile', 10, 4);

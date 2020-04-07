<?php
/**
 * Plugin Name:     fix-wp-wc-cpt-turmas
 * Plugin URI:      https://github.com/edinaldohvieira/fix-wp-wc-cpt-turmas
 * Description:     Usado junto com Woocommerce para selecionar os pedisos e formar novas turmas
 * Author:          edinaldohvieira
 * Author URI:      https://github.com/edinaldohvieira
 * Text Domain:     fix158617
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         fix158617
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

/* garantir que o jQuery seja carregado */
wp_enqueue_script( 'fix158617_ative_jquery', plugins_url('js/ative_jquery.js',__FILE__), array('jquery'), null, true );

/* interceptar o tipo de post "turma", modificar e inserir 2 shortcode */
add_filter( 'the_content', 'fix158617_filter_content' );
function fix158617_filter_content( $content ) {
 	if ( get_post_type() == 'turma' ) {
 		if ( is_single() ) {
 			global $post;
			
 			$ret = '<div style="border: 1px solid gray;border-radius: 5px;padding: 5px;">';
 			$ret .= $content;
 			$ret .= '<div>'.do_shortcode('[fix158617_curso_detalhes turma_id='.$post->ID.']').'</div>';
 			$ret .= '</div>';
 			$ret .= "
 			<div>
 				
 				<div>".do_shortcode('[fix158617_curso_alunos_list turma_id='.$post->ID.' ]')."</div>
 			</div>
			";
 			return $ret;
 		}
 	}
    return $content;
}













/* detalhes do curso (cpt) mostrar os campos criados com o ACF */
add_shortcode("fix158617_curso_detalhes", "fix158617_curso_detalhes");
function fix158617_curso_detalhes($atts, $content = null){
	extract(shortcode_atts(array(
		"turma_id" => ''
	), $atts));
	$post = $turma_id;

	ob_start();
	$data_inicio = get_field('data_inicio');
	$data_termino = get_field('data_termino');
	$carga_horaria = get_field('carga_horaria');
	$localcurso = get_field('localcurso');
	

	?>
	<style type="text/css">
		#fix158617_cc2_box {
			display: grid;
			grid-template-columns: 2fr 2fr 2fr 4fr 1fr;
			grid-gap: 2px;
		}
		#fix158617_cc2_box .fix_cp  {
			border: 1px solid gray;
			border-radius: 5px;
		}
		#fix158617_cc2_box .fix_label  {
			font-style: italic;
			font-size: 80%;
			color:gray;
		}
		#fix158617_cc2_box .fix_label span {
			/*padding: 0px 4px;*/
		}
		.fix158617_label_span {
			/*padding: 0px 4px;*/	
		}

		#fix158617_cc2_box .fix_value {
			text-align: center;
		}
	</style>
	<div style="font-size: 120%;"><strong>Detalhes da turma:</strong></div>
	<div id="fix158617_cc2_box">
		<div class="fix_cp">
			<div class="fix_label"><span style="padding: 0px 4px;">Data de início:</span></div>
			<div class="fix_value"><?= $data_inicio ?></div>
		</div>
		<div class="fix_cp">
			<div class="fix_label"><span style="padding: 0px 4px;">Data final:</span></div>
			<div class="fix_value"><?= $data_termino ?></div>
		</div>
		<div class="fix_cp">
			<div class="fix_label"><span style="padding: 0px 4px;">Carga horária:</span></div>
			<div class="fix_value"><?= $carga_horaria ?>hs</div>
		</div>
		<div class="fix_cp">
			<div class="fix_label"><span style="padding: 0px 4px;">Local do Curso:</span></div>
			<div class="fix_value"><?= $localcurso ?></div>
		</div>
		
		<div class="fix_cp">
			<div class="fix_label"><span style="padding: 0px 4px;">Inscritos:</span></div>
			<div id="inscritos" class="fix_value"><?= $inscritos ?></div>
		</div>


		
	</div>
	<?php


	return ob_get_clean();
}









/* listar os pedisos que pertença a turma atual - isso é feito em cada pedido no wc*/
add_shortcode("fix158617_curso_alunos_list", "fix158617_curso_alunos_list");
function fix158617_curso_alunos_list($atts, $content = null){
	extract(shortcode_atts(array(
		"turma_id" => ''
	), $atts));
	ob_start();

	$pedidos = get_posts( array(
		'numberposts' => -1,
		'meta_key'  => 'turma',
		'meta_value' => $turma_id,
		'post_type'  => wc_get_order_types(),
		'post_status' => array_keys( wc_get_order_statuses() ),
	) );
	$inscritos = count($pedidos);
	// echo '<pre>'.$inscritos.'</pre>';
	?>
	<script type="text/javascript">
		jQuery(function($){
			$('#inscritos').html('<?php echo $inscritos ?>');
		});
	</script>
	<style type="text/css">
		#fix158617_cc3_box {
			border: 1px solid gray;
			border-radius: 5px;
			padding: 5px;
			margin: 4px 0px;
		}
		#fix158617_cc3_box table {
			min-width: 100%;
		}
	</style>

	<div id="fix158617_cc3_box">
		<div style="font-size: 120%;"><strong>Alunos escritos nesta turma:</strong></div>
		<table>
			<tbody>
				<tr>
					<th>Pedido ID</th>
					<th>Aluno</th>
					<th>E-mail</th>
					<th>Telefone</th>
					<th>Valor</th>
				</tr>
				<?php foreach ($pedidos as $pedido) { ?>
					<?php 
					

					$pedido_metas = get_post_meta( $pedido->ID );
					$pedido_total = $pedido_metas['_order_total'][0];
					$pedido_user = $pedido_metas['_customer_user'][0];

					$user = get_user_by('id', $pedido_user);
					$pedido_email = $user->data->user_email;
					

					$user_meta = get_user_meta( $pedido_user );
					// echo '<pre>';
					// print_r($user_meta);
					// echo '</pre>';

					$pedido_aluno = $user_meta['first_name'][0] . " " . $user_meta['last_name'][0];
					


					?>
					<tr>
						<td><?=$pedido->ID?></td>
						<td><?=$pedido_aluno?></td>
						<td><?=$pedido_email?></td>
						<td><?=$pedido_fone?></td>
						<td><?=$pedido_total?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<?php
	return ob_get_clean();
}



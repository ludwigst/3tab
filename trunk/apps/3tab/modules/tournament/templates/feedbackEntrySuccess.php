<script type="text/javascript">
jQuery(document).ready(function () {	
	jQuery("#subnav").hide();
	jQuery(".nav-admin").click(function() {
		jQuery("#subnav").toggle(300);
	});
});
</script>
<?php
// auto-generated by sfPropelCrud
// date: 2008/04/20 19:00:01
?>

<?php use_helper('Object') ?>

<?php echo form_tag('tournament/EnterFeedback') ?>

<h1> Select the team where feedback is from: </h1>
<table id="form">
<tbody>
<tr>
	<td valign="top">
		<?php 
			echo select_tag('team', objects_for_select($teams, 'getId', 'getName'));
		?>
	</td>
	
</tr>


</tbody>
</table>
<?php echo input_hidden_tag('id', $round->getId()); ?>
<?php echo submit_tag('Select') ?>


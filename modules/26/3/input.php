<div class="row">
	<div class="col-xs-4">
		Veranstaltungen folgender Kategorie anzeigen:
	</div>
	<div class="col-xs-8">
		<?php
		$select_link = new rex_select(); 
		$select_link->setName('REX_INPUT_VALUE[1]'); 
		$select_link->setSize(1);
		$select_link->setAttribute('class', 'form-control');
		$select_link->setAttribute('id', 'selector');

		$categories = \D2U_Courses\Category::getAllNotParents();
		foreach ($categories as $category) {
			$select_link->addOption($category->name, $category->category_id); 
		}
		$select_link->setSelected("REX_VALUE[1]");

		echo $select_link->show();
		?>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[2]" value="true" <?php echo "REX_VALUE[2]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Kategoriename als Überschrift anzeigen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[3]" value="true" <?php echo "REX_VALUE[3]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Kategoriebeschreibung anzeigen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-4">
		<input type="checkbox" name="REX_INPUT_VALUE[4]" value="true" <?php echo "REX_VALUE[4]" == 'true' ? ' checked="checked"' : ''; ?> style="float: right;" />
	</div>
	<div class="col-xs-8">
		Kurzbeschreibung der Veranstaltung anzeigen
	</div>
</div>
<div class="row">
	<div class="col-xs-12">&nbsp;</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<p>Kurse, Kategorien, Einstellung, usw. werden im <a href="index.php?page=d2u_courses">D2U Kurse Addon</a> verwaltet.</p>
	</div>
</div>	
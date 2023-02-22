<fieldset>
	<legend>FAQs</legend>
	<ul>
		<li><b>Warum wird ein Kurs nicht angezeigt?</b><br>
			Mögliche Ursachen sind:<br>
			- Das Angebot muss auf online gestellt sein.<br>
			- Das Startdatum des Angebots liegt in der Vergangenheit. Dieses Verhalten
			kann in den Einstellungen unter "Kurse anzeigen bis ..." angepasst werden.
		</li>
		<li><b>Warum wird eine Kategorie nicht angezeigt?</b><br>
			Kategorien werden nur angezeigt, wenn auch ein online Kurs darin enthalten ist.
		</li>
		<li><b>Wie kann ich das Warenkorb Symbol in meinem Template einbinden?</b><br>
			<?= rex_string::highlight(<<<'EOF'
    print '<a href="'. rex_getUrl(rex_config::get('d2u_courses', 'article_id_shopping_cart')) .'" class="cart_link">';
    print '<div id="cart_symbol" class="desktop-inner">';
    print '<img src="'. rex_url::addonAssets('d2u_courses', 'cart_only.png') .'" alt="'.
    	rex_article::get(rex_config::get('d2u_courses', 'article_id_shopping_cart', 0))->getName() .'">';
    if(count(\D2U_Courses\Cart::getCourseIDs()) > 0) {
    	print '<div id="cart_info">'. count(\D2U_Courses\Cart::getCourseIDs()) .'</div>';
    }
    print '</div>';
    print '</a>';
    EOF
            ) ?>
		</li>

	</ul>
</fieldset>
<?php

// Быстрый хак, чтобы начать переводить модули на NeoSeo
require_once( 'soforp_model.php' );

class NeoSeoModel extends SoforpModel
{
	public function isAccesible(){
		return true;
	}

	public function setAccessLevels(){
		return true;
	}
}

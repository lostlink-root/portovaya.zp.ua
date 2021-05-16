<?php

require_once( DIR_SYSTEM . "/engine/neoseo_model.php");

class ModelExtensionModuleNeoSeoFilterTag extends NeoSeoModel
{

	public function __construct($registry)
	{
		parent::__construct($registry);
		$this->_module_code = "neoseo_filter";
		$this->_moduleSysName = "neoseo_filter";
		$this->_modulePostfix = "_tag"; // Постфикс для разных типов модуля, поэтому переходим на испольлзование $this->_moduleSysName()
		$this->_logFile = $this->_moduleSysName . ".log";
		$this->debug = $this->config->get($this->_moduleSysName . "_debug");

		$this->addAccessLevels();
	}

	public function install()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		return TRUE;
	}

	public function upgrade()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		return TRUE;
	}

	public function uninstall()
	{
		if (!$this->isAccesible(__FUNCTION__, true)) {
			return "";
		}

		return TRUE;
	}

	private function addAccessLevels()
	{
		$this->setAccessLevels(
				[
					'index' => 0,
					'install' => 0,
					'upgrade' => 0,
					'uninstall' => 0,
				]
		);
	}

}

?>
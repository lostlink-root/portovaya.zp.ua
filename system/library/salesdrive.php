<?php
/* OpenCart v.2.3, 3.0 */

class Salesdrive 
{
    public $prefix = 'https://'; //HTTP or HTTPS
    protected $_product_handler = '/product-handler/';
    protected $_category_handler = '/category-handler/';
    protected $_order_handler = '/handler/';
    
    protected $_errors = array();
   
	protected $_last_response = array();

	private $_domain;
    private $_key;
    
    public function __construct($domain = '', $key ='')
	{
		$this->_domain = $domain;
		$this->_key = $key;
	}

    //OUT:	returns number of errors
	public function hasErrors()
	{
		return count($this->_errors);
	}
	
	//OUT:	returns array of errors
	public function getErrors()
	{
		return $this->_errors;
	}

	public function getResponse()
	{
		return $this->_last_response;
	}

    /**
     * @param $data
     */
    public function addOrder($data)
    {
        $_values = $data;

        $_values['form'] = $this->_key;

        $_url = $this->createUrl($this->_order_handler);

        $this->execute($_url, $_values);
    }

    /**
     * @param $data
     */
	public function saveCategories($data)
    {
        
        $_values = array(
            'form' => $this->_key,
            'action' => 'update',
            'category' => $data,
        );
        $_url = $this->createUrl($this->_category_handler);
        
        $this->execute($_url, $_values);
    }
    
    /**
     * @param $data
     */
	public function saveProduct($data)
    {
        $_values = array(
            'form' => $this->_key,
            'action' => 'update',
            'product' => $data,
        );
        $_url = $this->createUrl($this->_product_handler);

        $this->execute($_url, $_values);
    }

    /**
     * Delete product from CRM
     * @param $product_id
     */
    public function deleteProduct($product_ids)
    {
        $_values = array(
            'form' => $this->_key,
            'action' => 'delete',
            'product' => $product_ids,
        );
        $_url = $this->createUrl($this->_product_handler);

        $this->execute($_url, $_values);

    }

    protected function execute($actionUrl, $params = array())
	{
		$this->_errors = array();

        //cURL POST
        $ch = curl_init($actionUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:application/json"));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);			
        $response = curl_exec($ch);
        curl_close($ch);

        $this->_last_response = json_decode($response);
        if ($this->_last_response == 'error') {
            $this->_errors = $this->_last_response['message'];
        }

        return $this->_last_response;		
	}

	private function createUrl($handler){
        $url = parse_url(trim($this->_domain));
		if(isset($url['host'])){
			$domain = $url['host'];
		}
		else{
			$domain = $url['path'];
		}
        return strtolower($this->prefix) . $domain. $handler;
    }
}
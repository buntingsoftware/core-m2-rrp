<?php
namespace Bunting\Core\Controller;

use Magento\Backend\App\Action\Context;
use Bunting\Core\Model\ResourceModel\Bunting\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Bunting\Core\Model\BuntingFactory;

abstract class AbstractSubmitAction extends \Bunting\Core\Controller\AbstractAction
{

    public function __construct(
        Context $context,
        CollectionFactory $buntingCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        BuntingFactory $buntingFactory
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_buntingFactory = $buntingFactory;
        parent::__construct($context, $buntingCollectionFactory);
    }

    protected function submitToBunting($action, $params) {
        $languages = [];
        $currencies = [];
        foreach ($this->_storeManager->getStores($withDefault = false) as $store) {
            $full_locale = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId());
            list($language, $locale) = explode('_',$full_locale);
            $languages[$language] = strtoupper($language);

            $currency_code = $store->getCurrentCurrencyCode();
            $currency_symbol = '£';//Mage::app()->getLocale()->currency( $currency_code )->getSymbol();
            $currencies[$currency_code] = [
                'currency' => $currency_code,
                'symbol' => html_entity_decode($currency_symbol)
            ];
        }

        $languages = array_values($languages);
        $currencies = array_values($currencies);

        $domain = preg_replace('#^https?://#', '', rtrim($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB),'/'));

        $timestamp = time();

        $feed_token = md5($this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()).$timestamp);

        $default_params = [
            'timestamp' => $timestamp,
            'hash' => hash_hmac('sha256', $timestamp, '5dKc763_f}E5%s-'),
            'plugin' => 'magento2',
            'domain_name' => $domain,
            'create_website_monitor' => 'yes',
            'website_name' => $this->_scopeConfig->getValue('general/store_information/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store->getId()),
            'languages' => $languages,
            'currencies' => $currencies,
            'website_platform' => 'Magento 2',
            'ecommerce' => 'yes',
            'cart_url' => $this->getUrl('checkout', ['_secure' => true]),
            'product_feed-url_protocol' => $store->isCurrentlySecure() ? 'https://' : 'http://',
            'product_feed-url' => $domain.'/bunting?feed_token='.$feed_token
        ];
        //$default_params['sandbox'] = 'success';
        $params = $params+$default_params;
        $params = $this->http_build_query_for_curl($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,'https://api.bunting.com/plugins/'.$action);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        $data = json_decode($response, true);
        if ($data['success']) {
            $data['email_address'] = $params['email_address'];
            $this->installBunting($data, $feed_token);
        }
        return $data;
    }

    /**
     * Takes a response from the Bunting API and converts it to a valid frontend module response
     *
     * @param $buntingData
     * @return mixed
     */
    protected function buntingResponse($buntingData) {
        $response = [];

        if ($buntingData['success']) {
            $response['message'] = $_SESSION['message'] = 'You can now login to Bunting.';
            return $this->sendJsonResponse($response);
        }

        $response['message'] = 'Please review the errors and try again.';

        if (!isset($buntingData['errors']) || !count($buntingData['errors'])) {
            $buntingData['errors'] = [];
            $response['message'] .= '<br><br>There was a problem connecting your shop to Bunting, please contact Bunting support.';
        }

        if (isset($buntingData['errors']['validation'])) {
            $response['message'] .= '<br><br>' . $buntingData['errors']['validation'];
        }

        $response['errors'] = $buntingData['errors'];
        return $this->sendJsonResponse($response);
    }

    /**
     * @param array $response
     * @return mixed
     */
    private function sendJsonResponse(array $response) {
        $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
        return $this->getResponse()->setBody(json_encode($response));
    }

    protected function http_build_query_for_curl( $arrays, &$new = array(), $prefix = null ) {
        if ( is_object( $arrays ) ) {
            $arrays = get_object_vars( $arrays );
        }

        foreach ( $arrays AS $key => $value ) {
            $k = isset( $prefix ) ? $prefix . '[' . $key . ']' : $key;
            if ( is_array( $value ) OR is_object( $value )  ) {
                $this->http_build_query_for_curl( $value, $new, $k );
            } else {
                $new[$k] = $value;
            }
        }
        return $new;
    }

    protected function installBunting($data, $feed_token) {
        $bunting_model = $this->_buntingFactory->create();
        $bunting_model->setData([
            'bunting_email' => $data['email_address'],
            'bunting_account_id' => $data['account_id'],
            'bunting_website_monitor_id' => $data['website_monitor_id'],
            'bunting_unique_code' => $data['unique_code'],
            'bunting_subdomain' => $data['subdomain'],
            'feed_token' => $feed_token,
            'password_api' => $data['password_api'],
            'server_region_subdomain_id' => $data['server_region_subdomain_id']
        ]);
        $bunting_model->save();
    }

}
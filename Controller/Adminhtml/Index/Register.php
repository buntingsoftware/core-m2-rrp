<?php
namespace Bunting\Core\Controller\Adminhtml\Index;
class Register extends \Bunting\Core\Controller\AbstractSubmitAction
{

    public function execute(){
        $store_id = $this->_storeManager->getStore()->getId();

        $full_locale = $this->_scopeConfig->getValue('general/locale/code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
        list($language, $locale) = explode('_',$full_locale);

        $address = $this->_scopeConfig->getValue('general/store_information/address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $store_id);
        $address = $this->processAddress($address);

        $submit_data = [
            'billing' => 'automatic',
            'email_address' => $this->getRequest()->getParam('register_email_address'),
            'password' => $this->getRequest()->getParam('register_password'),
            'confirm_password' => $this->getRequest()->getParam('password_confirmation'),
            'subdomain' => $this->getRequest()->getParam('register_bunting_subdomain'),
            'name' => $this->getRequest()->getParam('company_name'),
            'forename' => $this->getRequest()->getParam('forename'),
            'surname' => $this->getRequest()->getParam('surname'),
            'telephone_number' => $this->getRequest()->getParam('telephone_number'),
            'promotional_code' => $this->getRequest()->getParam('promotional_code'),
            'timezone' => date_default_timezone_get(),
            'country' => $locale,
            'agency' => 'no'
        ];

        $bunting_data = $this->submitToBunting('register', array_merge($submit_data, $address));
        $this->buntingResponse($bunting_data);
    }

    protected function processAddress($orig_address) {
        $comma_address = preg_replace('#\s+#',',',trim($orig_address));
        $address_array = explode(',', $comma_address);
        $address = [];
        if (!empty($address_array)) {
            $address['address_line_1'] = array_shift($address_array);
            $address['postcode'] = array_pop($address_array);
            $i = 2;
            foreach($address_array as $address_line) {
                $address['address_line_'.$i] = $address_line;
                if ($i < 5) {
                    $i++;
                }
                else {
                    break;
                }
            }
        }
        else {
            $address['address_line_1'] = $comma_address;
        }
        ksort($address);
        return $address;
    }
}
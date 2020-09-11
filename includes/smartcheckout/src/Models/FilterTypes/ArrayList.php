<?php
namespace SmartCheckoutSDK\Models\FilterTypes;

use nicoSWD\Rule\Rule;

class ArrayList
{
    private static $instance;
    private $result = true;

    public static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new ArrayList();
        }

        return self::$instance;
    }

    public function validate($filter, $data)
    {
        switch ($filter->position) {
            case 'customer':
            case 'custom':
            case 'cart':
                $rule = new Rule($filter->rule, $data);
                $this->result = $rule->isTrue();
                break;
            case 'item':
                $this->result = false;
                foreach ($data['cart_items'] as $item) {
                    if(!$this->result OR !isset($this->result)) {
                        $rule = new Rule($filter->rule, $item);
                        $this->result = $rule->isTrue();
                    }
                }
                break;
        }
    }

    public function result()
    {
        return $this->result;
    }
}
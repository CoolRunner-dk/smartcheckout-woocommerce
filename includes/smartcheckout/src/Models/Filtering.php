<?php
namespace SmartCheckoutSDK\Models;

use SmartCheckoutSDK\Models\FilterTypes\ArrayList;
use SmartCheckoutSDK\Models\FilterTypes\Boolean;
use SmartCheckoutSDK\Models\FilterTypes\Double;
use SmartCheckoutSDK\Models\FilterTypes\Number;
use SmartCheckoutSDK\Models\FilterTypes\Text;
use SmartCheckoutSDK\Models\FilterTypes\DateTime;

class Filtering
{
    private static $instance;

    public static function getInstance()
    {
        if(!self::$instance)
        {
            self::$instance = new Filtering();
        }

        return self::$instance;
    }
    
    public function validate($data, $products)
    {
        // Instantiate
        $macthingProducts = [];
        $errorValidation = [];
        $productCounted = 0;

        // Handle all products
        foreach ($products as $product) {
            // Handle all filter for selected product
            foreach ($product->conditions as $condition) {
                // Validation is standard true if no filters then show all products
                // This is used because to show a product you need to add an empty filter
                $validationResult = ['result' => true, 'validation-error' => '', 'filter-failed' => ''];
                foreach ($condition->filters as $filter) {

                    switch ($filter->type) {
                        case 'number':
                            if ($validationResult['result']) {
                                $numberFilterType = Number::getInstance();
                                $numberFilterType->validate($filter, $data);
                                $singleResult = $numberFilterType->result();

                                if(!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        case 'array':
                            if($validationResult['result']) {
                                $arrayListFilterType = ArrayList::getInstance();
                                $arrayListFilterType->validate($filter, $data);
                                $singleResult = $arrayListFilterType->result();

                                if (!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        case 'datetime':
                            if ($validationResult['result']) {
                                $dateFilterType = DateTime::getInstance();
                                $dateFilterType->validate($filter, $data);
                                $singleResult = $dateFilterType->result();

                                if(!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        case 'text':
                            if ($validationResult['result']) {
                                $textFilterType = Text::getInstance();
                                $textFilterType->validate($filter, $data);
                                $singleResult = $textFilterType->result();

                                if(!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        case 'double':
                            if ($validationResult['result']) {
                                $doubleFilterType = Double::getInstance();
                                $doubleFilterType->validate($filter, $data);
                                $singleResult = $doubleFilterType->result();

                                if(!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        case 'boolean':
                            if ($validationResult['result']) {
                                $booleanFilterType = Boolean::getInstance();
                                $booleanFilterType->validate($filter, $data);
                                $singleResult = $booleanFilterType->result();

                                if(!$singleResult) {
                                    $validationResult = ['result' => false, 'error' => 'Failed handling '. $filter->type .' filter type', 'filter-failed' => $filter];
                                }
                            }
                            break;
                        default:
                            $validationResult = ['result' => false, 'error' => 'Uncaught filtering type - Please contact CoolRunner at integration@coolrunner.dk', 'filter-failed' => $filter];
                            break;
                    }
                }

                // Product filters matched all criteria, and therefore should be returned
                if($validationResult['result']) {
                    $finalProduct = $product;
                    unset($finalProduct->conditions);

                    // Returns products and only the conditions that matches
                    if(!isset($macthingProducts[$productCounted])) {
                        $macthingProducts[$productCounted] = ['product' => $finalProduct, 'conditions' => [$condition]];
                    } else {
                        $macthingProducts[$productCounted]['conditions'][] = $condition;
                    }
                } else {
                    // Can be used to track validation errors
                    // Not used for anything as is
                    $errorValidation[] = $validationResult;
                }
            }

            $productCounted++;
        }

        return json_encode($macthingProducts);
    }
}
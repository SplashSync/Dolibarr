<?php
/**
 * This file is part of SplashSync Project.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 *  @author    Splash Sync <www.splashsync.com>
 *  @copyright 2015-2017 Splash Sync
 *  @license   GNU GENERAL PUBLIC LICENSE Version 3, 29 June 2007
 *
 **/

namespace   Splash\Local\Objects\Product;

use Splash\Models\Fields\FieldsManagerTrait;
use Splash\Core\SplashCore      as Splash;

/**
 * Dolibarr Products Multilang Fields
 */
trait MultilangTrait
{
    use FieldsManagerTrait;
    
    /**
    * Build Fields using FieldFactory
    */
    protected function buildMultilangFields()
    {
        global $conf,$langs;
        
        $groupName  =   $langs->trans("Description");
        
        //====================================================================//
        // Single Language Mode
        if (!$conf->global->MAIN_MULTILANGS) {
            return;
        }
        
        foreach ($this->getExtraLangauges() as $langCode) {
            //====================================================================//
            // Name (Default Language)
            $this->fieldsFactory()
                ->Create(SPL_T_VARCHAR)
                ->Identifier("label_" . $langCode)
                ->Name($langs->trans("ProductLabel"))
                ->isLogged()
                ->Group($groupName)
                ->addOption('language', $langCode)
                ->MicroData("http://schema.org/Product/" . $langCode, "name");

            //====================================================================//
            // Description (Default Language)
            $this->fieldsFactory()
                ->Create(SPL_T_VARCHAR)
                ->Identifier("description_" . $langCode)
                ->Name($langs->trans("Description"))
                ->isLogged()
                ->Group($groupName)
                ->addOption('language', $langCode)
                ->MicroData("http://schema.org/Product/" . $langCode, "description");
        }
    }

    /**
     * Read requested Field
     *
     * @param        string    $key                    Input List Key
     * @param        string    $fieldName              Field Identifier / Name
     *
     * @return      void
     */
    protected function getMultilangFields($key, $fieldName)
    {
        //====================================================================//
        // Read Multilang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->out[$fieldName] = $this->getMultilang("label", $langCode);
            unset($this->in[$key]);
        }
        
        //====================================================================//
        // Read Multilang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->out[$fieldName] = $this->getMultilang('description', $langCode);
            unset($this->in[$key]);
        }
    }

    /**
     * Write Given Fields
     *
     * @param        string    $fieldName              Field Identifier / Name
     * @param        mixed     $data                   Field Data
     *
     * @return         void
     */
    protected function setMultilangFields($fieldName, $data)
    {
        //====================================================================//
        // Read Multilang Label
        if (0 === strpos($fieldName, 'label_')) {
            $langCode = substr($fieldName, strlen('label_'));
            $this->setMultilangContent("label", $langCode, $data);
            unset($this->in[$fieldName]);
        }
        
        //====================================================================//
        // Read Multilang Description
        if (0 === strpos($fieldName, 'description_')) {
            $langCode = substr($fieldName, strlen('description_'));
            $this->setMultilangContent('description', $langCode, $data);
            unset($this->in[$fieldName]);
        }
    }
}

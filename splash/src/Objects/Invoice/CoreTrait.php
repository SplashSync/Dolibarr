<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Local\Objects\Invoice;

use DateTime;
use Facture;
use Splash\Core\SplashCore as Splash;
use Splash\Local\Local;
use Splash\Local\Objects\CreditNote;

/**
 * Dolibarr Customer Invoice Fields (Required)
 */
trait CoreTrait
{
    /**
     * Build Core Fields using FieldFactory
     *
     * @return void
     */
    protected function buildCoreFields(): void
    {
        global $langs;

        //====================================================================//
        // Order Date
        $this->fieldsFactory()->create(SPL_T_DATE)
            ->identifier("date")
            ->name($langs->trans("OrderDate"))
            ->microData("http://schema.org/Order", "orderDate")
            ->isRequired()
            ->isListed()
        ;
        //====================================================================//
        // Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref")
            ->name($langs->trans("InvoiceRef"))
            ->microData("http://schema.org/Invoice", "name")
            ->isReadOnly()
            ->isIndexed()
            ->isListed()
        ;
        //====================================================================//
        // Customer Reference
        // Since Dolibarr V20 -> ref_client is deprecated, uses ref_customer instead
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier((Local::dolVersionCmp("20.0.0") > 0) ? "ref_customer" : "ref_client")
            ->name($langs->trans("RefCustomer"))
            ->microData("http://schema.org/Invoice", "confirmationNumber")
            ->isIndexed()
            ->isListed()
        ;
        //====================================================================//
        // External Reference
        $this->fieldsFactory()->create(SPL_T_VARCHAR)
            ->identifier("ref_ext")
            ->name($langs->trans("ExternalRef"))
            ->microData("http://schema.org/Invoice", "alternateName")
            ->isIndexed()
            ->isListed()
        ;
        //====================================================================//
        // Source Invoice — credit notes only
        //
        // A credit note that does not say which invoice it corrects is an
        // orphan: the source invoice stays settled at its full amount, and
        // Dolibarr's own screens cannot offer to convert the credit note into
        // a discount, because that conversion needs the link.
        if ($this instanceof CreditNote) {
            $this->fieldsFactory()->create((string) self::objects()->encode("Invoice", SPL_T_ID))
                ->identifier("fk_facture_source")
                ->name($langs->trans("CorrectInvoice"))
                ->description($langs->trans("InvoiceReplacement"))
                ->microData("http://schema.org/Invoice", "referencesOrder")
                ->isIndexed()
            ;
        }
    }

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     */
    protected function getCoreFields(string $key, string $fieldName): void
    {
        //====================================================================//
        // READ Fields
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_client':
            case 'ref_customer':
            case 'ref_ext':
                $this->getSimple($fieldName);

                break;
                //====================================================================//
                // Order Official Date
            case 'date':
                $date = $this->object->date;
                $this->out[$fieldName] = !empty($date)?dol_print_date($date, '%Y-%m-%d'):null;

                break;
                //====================================================================//
                // Source Invoice — credit notes only
            case 'fk_facture_source':
                if (!$this instanceof CreditNote) {
                    return;
                }
                $sourceId = (int) ($this->object->fk_facture_source ?? 0);
                $this->out[$fieldName] = $sourceId
                    ? self::objects()->encode("Invoice", (string) $sourceId)
                    : null
                ;

                break;
            default:
                return;
        }

        unset($this->in[$key]);
    }

    /**
     * Write Given Fields
     *
     * @param string      $fieldName Field Identifier / Name
     * @param null|string $fieldData Field Data
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function setCoreFields(string $fieldName, ?string $fieldData)
    {
        //====================================================================//
        // WRITE Field
        switch ($fieldName) {
            //====================================================================//
            // Direct Readings
            case 'ref':
            case 'ref_client':
            case 'ref_customer':
                $this->setSimple($fieldName, $fieldData);

                break;
            case 'ref_ext':
                //====================================================================//
                //  Compare Field Data
                if ($this->object->{$fieldName} != $fieldData) {
                    //====================================================================//
                    //  Update Field Data
                    $this->object->setValueFrom($fieldName, $fieldData);
                    $this->needUpdate();
                }

                break;
                //====================================================================//
                // Order Official Date
            case 'date':
                $dateTime = new DateTime((string) $fieldData);
                $this->setSimple('date', $dateTime->getTimestamp());
                $this->setSimple('date_commande', $dateTime->getTimestamp());

                break;
                //====================================================================//
                // Source Invoice — credit notes only
            case 'fk_facture_source':
                if (!$this instanceof CreditNote) {
                    return;
                }
                $sourceId = $fieldData ? (int) self::objects()->id((string) $fieldData) : 0;
                //====================================================================//
                // Refuse a source that is not a real invoice of this entity, and
                // refuse a credit note pointing at itself.
                if ($sourceId && !$this->isValidSourceInvoice($sourceId)) {
                    Splash::log()->errTrace("Invoice ".$sourceId." cannot be used as credit note source.");

                    break;
                }
                if ((int) ($this->object->fk_facture_source ?? 0) !== $sourceId) {
                    $this->setSimple('fk_facture_source', $sourceId ?: null);
                }

                break;
            default:
                return;
        }
        unset($this->in[$fieldName]);
    }

    /**
     * Check an Invoice may be used as the source of this Credit Note
     *
     * Dolibarr stores the link as a bare id, with no foreign key: an invalid
     * value would be accepted silently and only surface much later, when the
     * source invoice fails to load. It is cheaper to refuse it here.
     *
     * @param int $sourceId Candidate source invoice id
     *
     * @return bool
     */
    private function isValidSourceInvoice(int $sourceId): bool
    {
        global $db;

        //====================================================================//
        // A credit note cannot correct itself
        if (!empty($this->object->id) && $sourceId === (int) $this->object->id) {
            return false;
        }
        //====================================================================//
        // The source must be an existing standard invoice of the same entity
        $sql = "SELECT f.rowid FROM ".MAIN_DB_PREFIX."facture f";
        $sql .= " WHERE f.rowid = ".$sourceId;
        $sql .= " AND f.type != ".Facture::TYPE_CREDIT_NOTE;
        $sql .= " AND f.entity IN (".getEntity('invoice').")";
        $result = $db->query($sql);

        return (bool) ($result && $db->num_rows($result));
    }
}

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

namespace Splash\Local\Objects\Invoice;

use Splash\Core\SplashCore      as Splash;

/**
 * @abstract    Dolibarr Customer Invoice Payments Fields
 */
trait PaymentsTrait {

    //====================================================================//
    // General Class Variables	
    //====================================================================//

    private     $Payments           = array();
    
    /**
     *  @abstract     Build Address Fields using FieldFactory
     */
    protected function buildPaymentsFields() {
        global $langs;
        
        $ListName = "" ;
        
        //====================================================================//
        // Payment Line Payment Method 
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)
                ->Identifier("mode")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("PaymentMode"))
                ->MicroData("http://schema.org/Invoice","PaymentMethod")
                ->AddChoice("ByBankTransferInAdvance"   , "By bank transfer in advance")
                ->AddChoice("CheckInAdvance"            , "Check in advance")
                ->AddChoice("COD"                       , "Cash On Delivery")
                ->AddChoice("Cash"                      , "Cash")
                ->AddChoice("PayPal"                    , "Online Payments (PayPal, more..)")
                ->AddChoice("DirectDebit"               , "Credit Card")
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested()
                ;        

        //====================================================================//
        // Payment Line Date
        $this->FieldsFactory()->Create(SPL_T_DATE)        
                ->Identifier("date")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("Date"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","validFrom")
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested();        

        //====================================================================//
        // Payment Line Payment Identifier
        $this->FieldsFactory()->Create(SPL_T_VARCHAR)        
                ->Identifier("number")
                ->InList("payments")
                ->Name( $ListName . $langs->trans('Numero'))
                ->MicroData("http://schema.org/Invoice","paymentMethodId")        
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested();        

        //====================================================================//
        // Payment Line Amount
        $this->FieldsFactory()->Create(SPL_T_DOUBLE)        
                ->Identifier("amount")
                ->InList("payments")
                ->Name( $ListName . $langs->trans("PaymentAmount"))
                ->MicroData("http://schema.org/PaymentChargeSpecification","price")
                ->Association("date@payments","mode@payments","amount@payments");        
//                ->NotTested();        

//        //====================================================================//
//        // Invoice Line Product Identifier
//        $this->FieldsFactory()->Create(self::ObjectId_Encode( "BankAccount" , SPL_T_ID))        
//                ->Identifier("accountid")
//                ->InList("payments")
//                ->Name( $ListName . $langs->trans("AccountToDebit"))
//                ->MicroData("http://schema.org/Invoice","accountId");
//                ->Association("desc@lines","qty@lines","price@lines");        
//                ->NotTested();             
        
    }
    
    /**
     *  @abstract     Fetch Invoive Payments List (Done after Load)

     *  @return         none
     */
    protected function loadPayments($InvoiceId)
    {
        global $db;
        
        //====================================================================//
        // Prepare SQL Request
	// Payments already done (from payment on this invoice)
	$sql = 'SELECT p.datep as date, p.num_paiement as number, p.rowid as id, p.fk_bank,';
	$sql .= ' c.code as code, c.libelle as payment_label,';
	$sql .= ' pf.amount as amount,';
	$sql .= ' ba.rowid as baid, ba.ref, ba.label';
	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_paiement as c, ' . MAIN_DB_PREFIX . 'paiement_facture as pf, ' . MAIN_DB_PREFIX . 'paiement as p';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank as b ON p.fk_bank = b.rowid';
	$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'bank_account as ba ON b.fk_account = ba.rowid';
	$sql .= ' WHERE pf.fk_facture = ' . $InvoiceId . ' AND p.fk_paiement = c.id AND pf.fk_paiement = p.rowid';
	$sql .= ' ORDER BY p.rowid';

        //====================================================================//
        // Execute SQL Request
	$Result = $db->query($sql);
	if (!$Result) {
            dol_print_error($db);
            return False;
        }
        //====================================================================//
        // Count Results
        $Count = $db->num_rows($Result);
        if ($Count == 0) {
            return True;
        }
	//====================================================================//
        // Fetch Results
        $i = 0;
        while ($i < $Count) {
            $this->Payments[$i] = $db->fetch_object($Result);
            //====================================================================//
            // Detect Payment Method Type from Default Payment "known" methods
            $this->Payments[$i]->method =   $this->IdentifySplashPaymentMethod($this->Payments[$i]->code);
            $i ++;
        }
	$db->free($Result);
        return True;
    }    
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getPaymentsFields($Key,$FieldName)
    {
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "payments", $FieldName );
        if ( !$FieldId ) {
            return;
        }      
        //====================================================================//
        // Fill List with Data
        foreach ($this->Payments as $key => $PaymentLine) {
            //====================================================================//
            // READ Fields
            switch ($FieldName)
            {
                //====================================================================//
                // Payment Line - Payment Mode
                case 'mode@payments':
                    $Value = $PaymentLine->method;
                    break;
                //====================================================================//
                // Payment Line - Payment Date
                case 'date@payments':
                    $Value = !empty($PaymentLine->date)?dol_print_date($PaymentLine->date, '%Y-%m-%d'):Null;
                    break;
                //====================================================================//
                // Payment Line - Payment Identification Number
                case 'number@payments':
                    $Value = $PaymentLine->number;
                    break;
                //====================================================================//
                // Payment Line - Payment Amount
                case 'amount@payments':
                    $Value = $PaymentLine->amount;
                    break;
                default:
                    return;
            }
            //====================================================================//
            // Insert Data in List
            self::Lists()->Insert( $this->Out, "payments", $FieldName, $key, $Value );            
        }
        unset($this->In[$Key]);
    }

    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    protected function setPaymentLineFields($FieldName,$Data) 
    {
        global $db;         
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "payments" ) {
            return True;
        }
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';        
        require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
        //====================================================================//
        // Verify Lines List & Update if Needed 
        foreach ($Data as $LineData) {
            $this->setPaymentLineData($LineData);
        } 
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Payments as $PaymentData) {
            //====================================================================//
            // Fetch Payment Line Entity
            $this->PaymentLine = new \Paiement($db);
            $this->PaymentLine->fetch($PaymentData->id);            
            //====================================================================//
            // Check If Payment impact another Bill
            if ( count($this->PaymentLine->getBillsArray()) > 1) {
                continue;
            }
            //====================================================================//
            // Try to delete Payment Line
            $this->PaymentLine->delete(); 
        }        
        
        unset($this->In[$FieldName]);
    }
    
    /**
     *  @abstract     Update a Payment line Data
     * 
     *  @param        array     $LineData          Line Data Array
     * 
     *  @return         none
     */
    protected function setPaymentLineData($LineData)
    {
        global $db,$langs,$conf,$user;         
        
        //====================================================================//
        // Read Next Payment Line
        $this->PaymentData = array_shift($this->Payments);

        //====================================================================//
        // Existing Line
        // 
        // => Update Date & Payment reference (Number)
        // => If Amount is Different, delete Payment & Re-Create
        // => If Payment method is different => Do nothing!!
        //====================================================================//
        if ( $this->PaymentData ) {

            $this->PaymentLine = new \Paiement($db);
            $this->PaymentLine->fetch($this->PaymentData->id);
            //====================================================================//
            // Update Payment Date
            if ( array_key_exists("date", $LineData) 
                && (dol_print_date($this->PaymentLine->datepaye, 'standard') !== $LineData["date"]) ) 
            {
                    $this->PaymentLine->update_date($LineData["date"]);
            }
            
            //====================================================================//
            // Update Payment Number
            if ( array_key_exists("number", $LineData) 
                && ($this->PaymentLine->num_paiement !== $LineData["number"]) ) 
            {
                    $this->PaymentLine->update_num($LineData["number"]);
            }
            
            //====================================================================//
            // Update Payment Method
            if ( array_key_exists("mode", $LineData) ) {
                //====================================================================//
                // Detect Payment Method Id
                $NewMethodId        = $this->IdentifyPaymentMethod($LineData["mode"]);
                $CurrentMethodId    = $this->IdentifyPaymentType($this->PaymentLine->type_code);
                if ($NewMethodId && ($CurrentMethodId !== $NewMethodId) ) {
                    $this->PaymentLine->setValueFrom("fk_paiement",$NewMethodId);
                }
                
            }
              
            //====================================================================//
            // Check If Payment impact another Bill => Too Late to Delete & recreate this payment
            if ( count($this->PaymentLine->getBillsArray()) > 1) {
                return;
            }
            
            //====================================================================//
            // Check If Payment Amount are Different
            if ( !array_key_exists("amount", $LineData) 
                || ( $this->PaymentLine->amount ==  $LineData["amount"]) )
            {
                return;
            }  
            
            //====================================================================//
            // Try to delete Payment
            if ( $this->PaymentLine->delete() <= 0) {
                return;
            }
        }

        //====================================================================//
        // Create New Line
        //====================================================================//

        //====================================================================//
        // Verify Minimal Fields Ar available
        if ( !array_key_exists("mode", $LineData) 
                || !array_key_exists("date" , $LineData)
                || !array_key_exists("amount" , $LineData)
                || empty( (double) $LineData["amount"]) ) {
            return;
        }
        $this->PaymentLine = new \Paiement($db);
        //====================================================================//
        // Setup Payment Invoice Id
        $this->PaymentLine->facid       =   $this->Object->id;
        //====================================================================//
        // Setup Payment Date
        $this->PaymentLine->datepaye    =   $LineData["date"];
        //====================================================================//
        // Setup Payment Method
        $this->PaymentLine->paiementid =   $this->IdentifyPaymentMethod($LineData["mode"]); 
        //====================================================================//
        // Setup Payment Refrence
        $this->PaymentLine->num_paiement=   $LineData["number"]; 
        //====================================================================//
        // Setup Payment Amount
        $this->PaymentLine->amounts[$this->PaymentLine->facid]    = $LineData["amount"];
        //====================================================================//
        // Create Payment Line
        if ( $this->PaymentLine->create($user) <= 0) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create Invoice Payment. ");
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->PaymentLine->error));
        }    

        //====================================================================//
        // Setup Payment Account Id
        $Result = $this->PaymentLine->addPaymentToBank($user,'payment','(Payment)',$conf->global->SPLASH_BANK,"","");
        if ( $Result < 0) {  
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to add Invoice Payment to Bank Account. ");
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$langs->trans($this->PaymentLine->error));
        }    
        
        return;
    }    
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function IdentifySplashPaymentMethod($MethodType) 
    {
        //====================================================================//
        // Detect Payment Method Type from Default Payment "known" methods
        switch ($MethodType){
            case "PRE":
            case "PRO":
            case "TIP":
            case "VIR":
                return "ByBankTransferInAdvance";
            case "CHQ":
                return "CheckInAdvance";
            case "FAC":
                return "COD";
            case "LIQ":
                return "Cash";
            case "CB":
                return "DirectDebit";
            case "VAD":
                return "PayPal";
            default:
                return "Unknown";
        }   
    }
    
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    private function IdentifyPaymentMethod($MethodType) 
    {
        global $conf;         
        
        //====================================================================//
        // Detect Payment Method Type from Default Payment "known/standard" methods
        switch ($MethodType){
            case "ByBankTransferInAdvance":
                return $this->IdentifyPaymentType("VIR");
            case "CheckInAdvance":
                return $this->IdentifyPaymentType("CHQ");
            case "COD":
                return $this->IdentifyPaymentType("FAC");
            case "Cash":
                return $this->IdentifyPaymentType("LIQ");
            case "PayPal":
                return $this->IdentifyPaymentType("VAD");
            case "CreditCard":
            case "DirectDebit":
                return $this->IdentifyPaymentType("CB");
        }        
        
        //====================================================================//
        // Return Default Payment Method or 0 (Default) 
        if ( isset($conf->global->SPLASH_DEFAULT_PAYMENT) && !empty($conf->global->SPLASH_DEFAULT_PAYMENT) ) {
            return $this->IdentifyPaymentType($conf->global->SPLASH_DEFAULT_PAYMENT);
        }
        return $this->IdentifyPaymentType("VAD");
    }
    
    /**
     *  @abstract     Identify Payment Method Id using Payment Method Code
     * 
     *  @param        string    $PaymentTypeCode        Payment Method Code
     * 
     *  @return       int
     */
    private function IdentifyPaymentType($PaymentTypeCode) 
    {
        global $db;         
        
        //====================================================================//
        // Include Object Dolibarr Class
        require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';      
        $Form = new \Form($db);
        $Form->load_cache_types_paiements();
        //====================================================================//
        // Safety Check
        if ( empty($Form->cache_types_paiements) ) {
            return 0;
        }    
        //====================================================================//
        // Detect Payment Method Id From Method Code
        foreach ($Form->cache_types_paiements as $Key => $PaymentMethod) {
            if ( $PaymentMethod["code"] === $PaymentTypeCode ) {
                return $Key;
            }   
        }
        //====================================================================//
        // Default Payment Method Id 
        return 0;
    }
    
    /**
     *  @abstract Fetch List of Invoices Payments Amounts
     *
     *  @param int  $PaiementId     Paiment Object Id
     * 
     *  @return   array             List Of Paiment Object Amounts
     */
    function getPaiementAmounts($PaiementId)
    {
        global $db;
        //====================================================================//
        // Init Result Array 
        $Amounts = array();
        //====================================================================//
        // SELECT SQL Request 
        $sql = 'SELECT fk_facture, amount';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture';
        $sql.= ' WHERE fk_paiement = '.$PaiementId;
        $resql = $db->query($sql);
        //====================================================================//
        // SQL Error 
        if (!$resql)
        {
            Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,$db->error());
            return $Amounts;
        }
        //====================================================================//
        // Populate Object
        for ($i=0; $i < $db->num_rows($resql); $i++)
        {
            $obj = $db->fetch_object($resql);
            $Amounts[$obj->fk_facture]    =   $obj->amount;
        }
        $db->free($resql);                    
        return $Amounts;
    }    
     
    /**
     *  @abstract Delete All Invoices Payments (Only Used for Debug in PhpUnit)
     *
     *  @param int  $InvoiceId     Invoice Object Id
     * 
     *  @return   array             List Of Paiment Object Amounts
     */
    function clearPayments($InvoiceId)
    {
        global $db;
        //====================================================================//
        // Load Invoice Payments
        $this->loadPayments($InvoiceId);
        //====================================================================//
        // Delete Remaining Lines
        foreach ($this->Payments as $PaymentData) {
            //====================================================================//
            // Fetch Payment Line Entity
            $this->PaymentLine = new \Paiement($db);
            $this->PaymentLine->fetch($PaymentData->id);            
            //====================================================================//
            // Check If Payment impact another Bill
            $BillArray  =   $this->PaymentLine->getBillsArray();
            if ( is_array($BillArray) && count($BillArray) > 1) {
                continue;
            }
            //====================================================================//
            // Try to delete Payment Line
            $this->PaymentLine->delete(); 
        }  
    }
        
}

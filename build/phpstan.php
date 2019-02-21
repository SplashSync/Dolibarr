<?php
/**
 * Bootstrap Dolibarr for Pḧpstan
 */

require dirname(__DIR__) . "/splash/vendor/autoload.php";

global $db,$langs,$conf,$user,$hookmanager;

//====================================================================//
// Initiate Dolibarr Global Envirement Variables
require_once(dirname(dirname(__DIR__)) . "/master.inc.php");

//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/ccountry.class.php';

//====================================================================//
// Objects Classes
include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/cpaiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
        
require_once DOL_DOCUMENT_ROOT."/variants/class/ProductAttribute.class.php";
require_once DOL_DOCUMENT_ROOT."/variants/class/ProductAttributeValue.class.php";
require_once DOL_DOCUMENT_ROOT."/variants/class/ProductCombination.class.php";
require_once DOL_DOCUMENT_ROOT."/variants/class/ProductCombination2ValuePair.class.php";
        
//====================================================================//
// Widgets Classes
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php');
include_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

//====================================================================//
// Splash Dolibarr Specific Classes
include_once dirname(__DIR__) . '/splash/core/modules/modSplash.class.php';
include_once dirname(__DIR__) . '/splash/core/triggers/interface_50_modSplash_Splash.class.php';

//====================================================================//
// Include Splash Constants Definitions
require_once(dirname(dirname(__FILE__)) . "/splash/vendor/splash/phpcore/inc/Splash.Inc.php");
require_once(dirname(dirname(__FILE__)) . "/splash/vendor/splash/phpcore/inc/defines.inc.php");
define("SPLASH_SERVER_MODE", 0);

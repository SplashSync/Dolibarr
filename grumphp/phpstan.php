<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2021 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

//====================================================================//
// PHP CS Overrides
// phpcs:disable PSR1.Files.SideEffects

require dirname(__DIR__)."/splash/vendor/autoload.php";

global $db, $langs, $conf, $user, $hookmanager;

//====================================================================//
// Initiate Dolibarr Global Environment Variables
define("NOLOGIN", "1");
require_once(dirname(dirname(__DIR__))."/main.inc.php");

//====================================================================//
// Functions Dolibarr
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/triggers/dolibarrtriggers.class.php");

//====================================================================//
// Objects Classes
include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/cpaiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

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
include_once dirname(__DIR__).'/splash/core/modules/modSplash.class.php';
include_once dirname(__DIR__).'/splash/core/triggers/interface_50_modSplash_Splash.class.php';

//====================================================================//
// Include Splash Constants Definitions
require_once(dirname(dirname(__FILE__))."/splash/vendor/splash/phpcore/inc/Splash.Inc.php");
require_once(dirname(dirname(__FILE__))."/splash/vendor/splash/phpcore/inc/defines.inc.php");
define("SPLASH_SERVER_MODE", 0);

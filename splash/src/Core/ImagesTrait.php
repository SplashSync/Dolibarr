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

namespace   Splash\Local\Core;

use Splash\Core\SplashCore      as Splash;


use EcmFiles;

/**
 * @abstract    Access to Dolibarr Objects Images 
 */
trait ImagesTrait {
    
    private     $MinVersion     =   "6.0.0";
    private     $ElementPath    =   array(
        "product"   =>  "produit",
    );

    /**
     *  @abstract     Build Images FieldFactory
     */
    protected function buildImagesFields()   {
        global $langs;
        
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if ( Splash::Local()->DolVersionCmp($this->MinVersion) < 0) {
            return;
        }         
        
        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("users");
        
        //====================================================================//
        // Product Images List
        $this->FieldsFactory()->Create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name($langs->trans("PhotoFile"))
                ->Group("Images")
                ->MicroData("http://schema.org/Product","image");
        
        //====================================================================//
        // Product Images => Is Cover
        $this->FieldsFactory()->Create(SPL_T_BOOL)
                ->Identifier("cover")
                ->InList("images")
                ->Name("Cover Image")
                ->MicroData("http://schema.org/Product","isCover")
                ->Group("Images")
                ->NotTested();     
        
    }
    
    //====================================================================//
    // Fields Reading Functions
    //====================================================================//
    
    /**
     *  @abstract     Read requested Field
     * 
     *  @param        string    $Key                    Input List Key
     *  @param        string    $FieldName              Field Identifier / Name
     * 
     *  @return         none
     */
    protected function getImagesFields($Key,$FieldName)
    {
        global $db, $conf;
        
        //====================================================================//
        // Safety Check
        if ( !isset($this->Out) ) {
            $this->Out = array();
        }   
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::Lists()->InitOutput( $this->Out, "images", $FieldName );
        if ( !$FieldId ) {
            return;
        }  
                
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if ( Splash::Local()->DolVersionCmp($this->MinVersion) < 0) {
            return;
        }         
        //====================================================================//
        // Load Object Files Path
        $Entity     =   $this->Object->entity ? $this->Object->entity : $conf->entity;
        $this->DolFilesDir = $conf->product->multidir_output[$Entity];
        $this->DolFilesDir.= '/'.get_exdir(0, 0, 0, 0, $this->Object, $this->Object->element);
        $this->DolFilesDir.= dol_sanitizeFileName($this->Object->ref);
        $this->RelFilesDir = $this->ElementPath[$this->Object->element];
        $this->RelFilesDir.= "/" . dol_sanitizeFileName($this->Object->ref);    	

        //====================================================================//
        // Fetch Object Attached Images
        $this->getImagesArrayFromDir($Key, $FieldName);
    }
        
    private function getImagesArrayFromDir($Key,$FieldName)
    {
        global $db;
        
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
        
        //====================================================================//
        // Refresh Object Attached Images (Manually, OR Ref Changed)
    	$DiskFileArray = \dol_dir_list($this->DolFilesDir, "files");   
        \completeFileArrayWithDatabaseInfo($DiskFileArray, $this->RelFilesDir);
        //====================================================================//
        // Fetch Object Attached Images from Database
    	$FileArray = \dol_dir_list_in_database($this->RelFilesDir, "", Null, "position");
        
        //====================================================================//
        // Create Images List
        foreach ($FileArray as $key => $File) {
            //====================================================================//
            // Filter No Images Files
            if ( !in_array(pathinfo($File["name"], PATHINFO_EXTENSION) , [ "gif", "jpg", "jpeg", "png", "bmp" ]) ) {
                continue;
            } 
            
            //====================================================================//
            // Insert Image in Output List
            $Image = self::Images()->Encode(
                    $File["name"], 
                    $File["name"], 
                    $File["path"] . "/", 
                    null );
            
            //====================================================================//
            // Insert Data in List
            self::Lists()->Insert( $this->Out, "images", $FieldName,    $File["position"], $Image );
            self::Lists()->Insert( $this->Out, "images", "cover",       $File["position"], $File["cover"] );
            
        }
           
        //====================================================================//
        // Sort Image Array to Update Images Positions
        ksort($this->Out["images"]);
    }
    
    protected function getImagesArrayFromEcm($Key,$FieldName)
    {
        global $db, $conf;
        
        //====================================================================//
        // Create EcmFiles Main Object
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
        $EcmFiles   =   new EcmFiles($db);
        
        //====================================================================//
        // Fetch Object Attached Images
        $Filters    =   [
                "entity"    =>  $this->Object->entity,
                "filepath"  =>  ( $this->ElementPath[$this->Object->element] . "/" . $this->Object->ref) 
            ];
        $EcmFiles->fetchAll( Null, Null, 0, 0, $Filters );
        $this->CatchDolibarrErrors();
        if ( empty($EcmFiles->lines) ) {
            return;
        }         
        
        //====================================================================//
        // Create Images List
        foreach ($EcmFiles->lines as $key => $EcmFileLine) {

            //====================================================================//
            // Filter No Images Files
            if ( !in_array(pathinfo($EcmFileLine->filename, PATHINFO_EXTENSION) , [ "gif", "jpg", "jpeg", "png", "bmp" ]) ) {
                continue;
            } 
            
            //====================================================================//
            // Insert Image in Output List
            $Image = self::Images()->Encode(
                    $EcmFileLine->description, 
                    $EcmFileLine->filename, 
                    $EcmFileLine->filepath . "/", 
                    null );

            //====================================================================//
            // Insert Data in List
            self::Lists()->Insert( $this->Out, "images", $FieldName, $key, $Image );
        }
        
    }
    
    //====================================================================//
    // Fields Writting Functions
    //====================================================================//
      
    /**
     *  @abstract     Write Given Fields
     * 
     *  @param        string    $FieldName              Field Identifier / Name
     *  @param        mixed     $Data                   Field Data
     * 
     *  @return         none
     */
    protected function setImagesFields($FieldName,$Data) 
    {
        global $db, $user;
        //====================================================================//
        // Safety Check
        if ( $FieldName !== "images" ) {
            return True;
        }        
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if ( Splash::Local()->DolVersionCmp($this->MinVersion) < 0) {
            return;
        }          

        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

        //====================================================================//
        // Load Current Image Array
        $this->getImagesFields(0,"image@images");
        
        //====================================================================//
        // Verify Images List & Update if Needed 
        $Position = 1;
        foreach ($Data as $Key => $ImageData) {
            //====================================================================//
            // Check if Cover Image
            $Cover  =   isset($ImageData['cover']) ? $ImageData['cover'] : False;
            //====================================================================//
            // Update Item Line
            $this->setImage($Position, $ImageData['image'], $Cover);
            $Position++;
        } 
        
        //====================================================================//
        // Delete Remaining Images
        
        foreach ($this->Out["images"] as $Key => $Image) {
            $EcmImage       =   new EcmFiles($db);
            $EcmImage->fetch(Null, Null, $this->RelFilesDir . "/" . $Image["image"]["filename"]);
            //====================================================================//
            // Delete Object In Database
            if ( $EcmImage->delete($user) <= 0) {    
                $this->CatchDolibarrErrors($EcmImage);
                Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to Delete Image File. ");
            }   
            //====================================================================//
            // Delete Object From Disk
            Splash::File()->DeleteFile( $this->DolFilesDir . "/" . $Image["image"]["filename"], $Image["image"]["md5"] );
            unset($this->Out["images"][$Key]);
        }          

        unset($this->In[$FieldName]);
    }

    /**
     *  @abstract     Write Data to Current Image
     * 
     *  @param        int       $Position       Input Image Position on List
     *  @param        array     $ImageData      Input Image Data Array
     *  @param        bool      $Cover          Input Image is Cover
     * 
     *  @return       none
     */
    private function setImage($Position, $ImageData, $Cover = False) {
        global $db, $user, $conf; 
        $ImageUpdated   =   False;
        
        //====================================================================//
        // Create EcmFiles Main Object
        $EcmImage       =   new EcmFiles($db);
        
        //====================================================================//
        // Load Image by CheckSum
        if ( empty($ImageData["md5"]) ) {
            Splash::Log()->War("Skipped Image Writing");
            return;
        }
        
        //====================================================================//
        // Image Already Exits
        foreach ($this->Out["images"] as $Key => $CurrentImage) {
            if ( ( $CurrentImage["image"]["md5"] === $ImageData["md5"] ) && ($CurrentImage["image"]["filename"] === $ImageData["filename"]) ) {
                $EcmImage->fetch(Null, Null, $this->RelFilesDir . "/" . $CurrentImage["image"]["filename"]);
                unset($this->Out["images"][$Key]);
                break;
            }
        }        
        
        //====================================================================//
        // Check Image CheckSum
        if ( $EcmImage->label != $ImageData["md5"] ) {
            //====================================================================//
            // Read File from Splash Server
            $NewImageFile    =   Splash::File()->getFile($ImageData["file"],$ImageData["md5"]);
            //====================================================================//
            // File Imported => Write it Here
            if ( $NewImageFile == False ) {
                return False;
            }
            //====================================================================//
            // Write Image On Folder
            Splash::File()->WriteFile($this->DolFilesDir . "/",$ImageData["filename"],$NewImageFile["md5"],$NewImageFile["raw"]); 
            $ImageUpdated   =   True;
        }

        //====================================================================//
        // Image Entity Is New
        if ( empty($EcmImage->id) ) {
            $EcmImage->label        =   $ImageData["md5"];
            $EcmImage->filepath     =   $this->RelFilesDir;
            $EcmImage->filename     =   $ImageData["filename"];
            $EcmImage->fullpath_orig=   $EcmImage->filepath;
            $EcmImage->fk_user_c    =   $user->rowid;
            $EcmImage->entity       =   $this->Object->entity ? $this->Object->entity : $conf->entity;
            $EcmImage->gen_or_uploaded=   "uploaded";            
            $ImageUpdated   =   True;
        }
        
        //====================================================================//
        // Check Image Filename
        if ( $EcmImage->position != $Position ) {
            $EcmImage->position = $Position;
            $ImageUpdated   =   True;
        }        
        
        //====================================================================//
        // Check Image Cover Flag
        if ( $EcmImage->cover != $Cover ) {
            $EcmImage->cover = $Cover;
            $ImageUpdated   =   True;
        }  
        
        //====================================================================//
        // Image Not Updated
        if ( !$ImageUpdated ) {
            return;
        } 
        
        if ( empty($EcmImage->id) ) {
            //====================================================================//
            // Create Object In Database
            if ( $EcmImage->create($user) <= 0) {    
                $this->CatchDolibarrErrors($EcmImage);
                return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to create new Image File. ");
            }  
            $EcmImage->position = $Position;
        }
        //====================================================================//
        // Update Object In Database
        if ( $EcmImage->update($user) <= 0) {    
            $this->CatchDolibarrErrors($EcmImage);
            return Splash::Log()->Err("ErrLocalTpl",__CLASS__,__FUNCTION__,"Unable to update Image File. ");
        }       
        
        $this->Object->addThumbs($this->DolFilesDir . "/" . $EcmImage->filename);

    }
    
    /**
     *  @abstract     Update Object Files Path if Ref Changed
     * 
     *  @param        int       $Position       Input Image Position on List
     *  @param        array     $ImageData      Input Image Data Array
     *  @param        bool      $Cover          Input Image is Cover
     * 
     *  @return       none
     */
    private function updateFilesPath($Element, $Oldref, $Newref) {
        global $db; 

        //====================================================================//
        // Check if Ref was Changed
        if ( empty($Element) || empty($Oldref) || empty($Newref) || ($Oldref == $Newref) ) {
            return;
        }
        
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
                
        //====================================================================//
        // Create EcmFiles Main Object
        $EcmImage       =   new EcmFiles($db);

        //====================================================================//
        // Prepare Update Request
        $sql  =   $sql = 'UPDATE ' . MAIN_DB_PREFIX . $EcmImage->table_element . ' SET';
        $sql .= ' filepath = "' . $Element . '/' . $Newref. '" ';
        $sql .= ' WHERE filepath="' . $Element . '/' . $Oldref. '"';
           
        //====================================================================//
        // Execute Update Request
        $db->begin();
        $resql = $db->query($sql);
        if (!$resql) {
            $EcmImage->errors[] = 'Error ' . $db->lasterror();
            $this->CatchDolibarrErrors($EcmImage);
            dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
            $db->rollback();
            return;
        }
        $db->commit();
    }
    
}

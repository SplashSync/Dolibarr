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
use Exception;

/**
 * @abstract    Access to Dolibarr Objects Images
 */
trait ImagesTrait
{
    
    private $MinVersion     =   "6.0.0";
    private $ElementPath    =   array(
        "product"   =>  "produit",
        "commande"  =>  "commande"
    );
    private $Extensions     =   [ "gif", "jpg", "jpeg", "png", "bmp" ];

    private $DolFilesDir;
    private $RelFilesDir;
    private $imgUpdated;

    /**
     *  @abstract     Build Images FieldFactory
     */
    protected function buildImagesFields()
    {
        global $langs;
        
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Splash::local()->dolVersionCmp($this->MinVersion) < 0) {
            return;
        }
        
        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("users");
        
        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
                ->Identifier("image")
                ->InList("images")
                ->Name($langs->trans("PhotoFile"))
                ->Group("Images")
                ->MicroData("http://schema.org/Product", "image");
        
        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("cover")
                ->InList("images")
                ->Name("Cover Image")
                ->MicroData("http://schema.org/Product", "isCover")
                ->Group("Images")
                ->isNotTested();
        
        //====================================================================//
        // Product Images => Is Visible
        $this->fieldsFactory()->create(SPL_T_BOOL)
                ->Identifier("visible")
                ->InList("images")
                ->Name("Visible Image")
                ->MicroData("http://schema.org/Product", "isVisibleImage")
                ->Group("Images")
                ->isNotTested();
        
        //====================================================================//
        // Product Images => Is Visible
        $this->fieldsFactory()->create(SPL_T_INT)
                ->Identifier("position")
                ->InList("images")
                ->Name("Position")
                ->MicroData("http://schema.org/Product", "positionImage")
                ->Group("Images")
                ->isReadOnly();
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
    protected function getImagesFields($Key, $FieldName)
    {
        global $conf;
        
        //====================================================================//
        // Safety Check
        if (!isset($this->Out)) {
            $this->Out = array();
        }
        //====================================================================//
        // Check if List field & Init List Array
        $FieldId = self::lists()->InitOutput($this->Out, "images", $FieldName);
        if (!$FieldId) {
            return;
        }
                
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Splash::local()->dolVersionCmp($this->MinVersion) < 0) {
            return;
        }
        //====================================================================//
        // Load Object Files Path
        $Entity     =   $this->Object->entity ? $this->Object->entity : $conf->entity;
        $Element    =   $this->Object->element;
        $this->DolFilesDir = $conf->$Element->multidir_output[$Entity];
//      In Next Releases we Will Use this function but now it's not generic
//        $this->DolFilesDir.= '/'.get_exdir(0, 0, 0, 0, $this->Object, $this->Object->element);
        $this->DolFilesDir.= '/'. dol_sanitizeFileName($this->Object->ref);
        $this->RelFilesDir = $this->ElementPath[$Element];
        $this->RelFilesDir.= "/" . dol_sanitizeFileName($this->Object->ref);
        
        //====================================================================//
        // Fetch Object Attached Images
        $this->getImagesArrayFromDir($FieldName);

        if (isset($this->In[$Key])) {
            unset($this->In[$Key]);
        }
    }
        
    private function getImagesArrayFromDir($FieldName)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
        
        //====================================================================//
        // Refresh Object Attached Images (Manually, OR Ref Changed)
        if (function_exists("completeFileArrayWithDatabaseInfo")) {
            $DiskFileArray = \dol_dir_list($this->DolFilesDir, "files");
            try {
                \completeFileArrayWithDatabaseInfo($DiskFileArray, $this->RelFilesDir);
            } catch (Exception $ex) {
                Splash::log()->deb("ErrLocalTpl", __CLASS__, __FUNCTION__, $ex->getMessage());
            }
        }
        //====================================================================//
        // Fetch Object Attached Images from Database
        $FileArray = \dol_dir_list_in_database($this->RelFilesDir, "", null, "position");
        //====================================================================//
        // Detect if List has Cover Image or Force it
        $this->detectCoverImage($FileArray);
        //====================================================================//
        // Create Images List
        foreach ($FileArray as $File) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($File["name"], PATHINFO_EXTENSION), $this->Extensions)) {
                continue;
            }
            //====================================================================//
            // File Not Found on Disk
            if (!file_exists($File["fullname"])) {
                continue;
            }
            //====================================================================//
            // Insert Image in Output List
            $Image = self::images()->Encode(
                $File["name"],
                $File["name"],
                $File["path"] . "/",
                null
            );
            
            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->Out, "images", $FieldName, $File["position"], $Image);
            self::lists()->Insert($this->Out, "images", "cover", $File["position"], $File["cover"]);
            self::lists()->Insert($this->Out, "images", "visible", $File["position"], true);
            self::lists()->Insert($this->Out, "images", "position", $File["position"], $File["position"]);
        }
           
        //====================================================================//
        // Sort Image Array to Update Images Positions
        ksort($this->Out["images"]);
    }

    /**
     * @abstract    Detect if List has Cover Image or Force First Image as Cover
     * @param   array   $FileArray
     * @return  void
     */
    private function detectCoverImage(&$FileArray)
    {
        $hasCover   =   false;
        //====================================================================//
        // Walk on Images List
        foreach ($FileArray as $File) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($File["name"], PATHINFO_EXTENSION), $this->Extensions)) {
                continue;
            }
            //====================================================================//
            // Check if Cover Flag is Set
            if (isset($File["cover"]) && $File["cover"]) {
                $hasCover   =   true;
            }
        }
        //====================================================================//
        // There is A Cover Image
        if ($hasCover) {
            return;
        }
        //====================================================================//
        // Set First Image as Cover
        foreach ($FileArray as $Key => $File) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($File["name"], PATHINFO_EXTENSION), $this->Extensions)) {
                continue;
            }
            //====================================================================//
            // Set Image Cover Flag
            $FileArray[$Key]["cover"] = true;
            return;
        }
    }
    
    protected function getImagesArrayFromEcm($FieldName)
    {
        global $db;
        
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
        $EcmFiles->fetchAll(null, null, 0, 0, $Filters);
        $this->catchDolibarrErrors();
        if (empty($EcmFiles->lines)) {
            return;
        }
        
        //====================================================================//
        // Create Images List
        foreach ($EcmFiles->lines as $key => $EcmFileLine) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($EcmFileLine->filename, PATHINFO_EXTENSION), $this->Extensions)) {
                continue;
            }
            
            //====================================================================//
            // Insert Image in Output List
            $Image = self::images()->Encode(
                $EcmFileLine->description,
                $EcmFileLine->filename,
                $EcmFileLine->filepath . "/",
                null
            );

            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->Out, "images", $FieldName, $key, $Image);
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
    protected function setImagesFields($FieldName, $Data)
    {
        //====================================================================//
        // Safety Check
        if ($FieldName !== "images") {
            return true;
        }
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Splash::local()->dolVersionCmp($this->MinVersion) < 0) {
            return;
        }

        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

        //====================================================================//
        // Load Current Image Array
        $this->getImagesFields(0, "image@images");
       
        //====================================================================//
        // Verify Images List & Update if Needed
        $Position = 1;
        foreach ($Data as $Key => $ImageData) {
            //====================================================================//
            // Check if Visible Image
            if (isset($ImageData['visible']) && empty($ImageData['visible'])) {
                continue;
            }
            //====================================================================//
            // Check if Cover Image
            $Cover  =   isset($ImageData['cover']) ? $ImageData['cover'] : false;
            //====================================================================//
            // Update Item Line
            $this->setImage($Position, $ImageData['image'], $Cover);
            $Position++;
        }
        
        //====================================================================//
        // Delete Remaining Images
        $this->deleteRemainingImages();


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
    private function setImage($Position, $ImageData, $Cover = false)
    {
        global $db;
        
        $this->imgUpdated   =   false;
        
        //====================================================================//
        // Create EcmFiles Main Object
        $EcmImage       =   new EcmFiles($db);
        
        //====================================================================//
        // Load Image by CheckSum
        if (empty($ImageData["md5"])) {
            Splash::log()->war("Skipped Image Writing");
            return;
        }
        
        //====================================================================//
        // Check if Image Already Exits
        $this->identifyImage($EcmImage, $ImageData);

        //====================================================================//
        // Image New but May Be A Duplicate
        if (empty($EcmImage->id) && $this->isExistingEcmFile($this->RelFilesDir . "/" . $ImageData["filename"])) {
            Splash::log()->war("Skipped Duplicate Image Writing");
            return;
        }
        
        //====================================================================//
        // Check Image CheckSum
        if ($EcmImage->label != $ImageData["md5"]) {
            //====================================================================//
            // Read File from Splash Server
            $NewImageFile    =   Splash::file()->getFile($ImageData["file"], $ImageData["md5"]);
            //====================================================================//
            // File Imported => Write it Here
            if ($NewImageFile == false) {
                return false;
            }
            //====================================================================//
            // Write Image On Folder
            Splash::file()->WriteFile(
                $this->DolFilesDir . "/",
                $ImageData["filename"],
                $NewImageFile["md5"],
                $NewImageFile["raw"]
            );
            $this->imgUpdated   =   true;
        }

        //====================================================================//
        // Update Image Data
        $this->setEcmFileData($EcmImage, $Position, $ImageData, $Cover);
        
        //====================================================================//
        // Image Not Updated
        if (!$this->imgUpdated) {
            return;
        }
        
        return $this->saveEcmFile($EcmImage, $Position);
    }
    
    /**
     *  @abstract     Delete Unexpected Images
     *
     *  @return       none
     */
    private function deleteRemainingImages()
    {
        global $db, $user;
        
        foreach ($this->Out["images"] as $Key => $Image) {
            $EcmImage       =   new EcmFiles($db);
            $EcmImage->fetch(null, null, $this->RelFilesDir . "/" . $Image["image"]["filename"]);
            //====================================================================//
            // Delete Object In Database
            if (!empty($EcmImage->label) && ($EcmImage->delete($user) <= 0)) {
                $this->catchDolibarrErrors($EcmImage);
                Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to Delete Image File. ");
            }
            //====================================================================//
            // Delete Object From Disk
            Splash::file()->DeleteFile($this->DolFilesDir . "/" . $Image["image"]["filename"], $Image["image"]["md5"]);
            unset($this->Out["images"][$Key]);
        }
    }
    
    /**
     *  @abstract     Search for this Image in Current DataSet & Fetch EcmFile if Found
     *
     *  @param        EcmFiles  $EcmImage       Dolibarr File Object
     *  @param        array     $ImageData      Input Image Data Array
     *
     *  @return       void
     */
    private function identifyImage(&$EcmImage, $ImageData)
    {
        foreach ($this->Out["images"] as $Key => $CurrentImage) {
            if (( $CurrentImage["image"]["md5"] === $ImageData["md5"] )
                    && ($CurrentImage["image"]["filename"] === $ImageData["filename"])) {
                $EcmImage->fetch(null, null, $this->RelFilesDir . "/" . $CurrentImage["image"]["filename"]);
                unset($this->Out["images"][$Key]);
                break;
            }
        }
    }
        
    /**
     *  @abstract     Write Data to Current Image
     *
     *  @param        EcmFiles  $EcmImage       Dolibarr File Object
     *  @param        int       $Position       Input Image Position on List
     *  @param        array     $ImageData      Input Image Data Array
     *  @param        bool      $Cover          Input Image is Cover
     *
     *  @return       none
     */
    private function setEcmFileData($EcmImage, $Position, $ImageData, $Cover)
    {
        global $user, $conf;

        //====================================================================//
        // Image Entity Is New
        if (empty($EcmImage->id)) {
            $EcmImage->label        =   $ImageData["md5"];
            $EcmImage->filepath     =   $this->RelFilesDir;
            $EcmImage->filename     =   $ImageData["filename"];
            $EcmImage->fullpath_orig=   $EcmImage->filepath;
            $EcmImage->fk_user_c    =   $user->rowid;
            $EcmImage->entity       =   $this->Object->entity ? $this->Object->entity : $conf->entity;
            $EcmImage->gen_or_uploaded=   "uploaded";
            $this->imgUpdated   =   true;
        }
        
        //====================================================================//
        // Check Image Filename
        if ($EcmImage->position != $Position) {
            $EcmImage->position = $Position;
            $this->imgUpdated   =   true;
        }
        
        //====================================================================//
        // Check Image Cover Flag
        if ($EcmImage->cover != $Cover) {
            $EcmImage->cover = $Cover;
            $this->imgUpdated   =   true;
        }
    }
    
    /**
     *  @abstract     Save EcmImage to DataBase
     *
     *  @param        EcmFiles  $EcmImage       Dolibarr File Object
     *  @param        int       $Position       Input Image Position on List
     *
     *  @return       bool
     */
    private function saveEcmFile($EcmImage, $Position)
    {
        global $user;
        
        if (empty($EcmImage->id)) {
            //====================================================================//
            // Search for Already Created/Updated Image In Database
            if ($this->isExistingEcmFile($EcmImage->filepath . "/" . $EcmImage->filename)) {
                return true;
            }
            //====================================================================//
            // Create Object In Database
            if ($EcmImage->create($user) <= 0) {
                $this->catchDolibarrErrors($EcmImage);
                return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to create new Image File. ");
            }
            $EcmImage->position = $Position;
        }
        //====================================================================//
        // Update Object In Database
        if ($EcmImage->update($user) <= 0) {
            $this->catchDolibarrErrors($EcmImage);
            return Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, "Unable to update Image File. ");
        }
        
        //====================================================================//
        // Update Object Images Thumbs
        $this->Object->addThumbs($this->DolFilesDir . "/" . $EcmImage->filename);
        
        return true;
    }
            
    /**
     *  @abstract     Update Object Files Path if Ref Changed
     *
     *  @param        string    $Element
     *  @param        string    $Oldref
     *  @param        string    $Newref
     *
     *  @return       none
     */
    private function updateFilesPath($Element, $Oldref, $Newref)
    {
        global $db;

        //====================================================================//
        // Check if Ref was Changed
        if (empty($Element) || empty($Oldref) || empty($Newref) || ($Oldref == $Newref)) {
            return;
        }
        
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Splash::local()->dolVersionCmp($this->MinVersion) < 0) {
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
            $this->catchDolibarrErrors($EcmImage);
            dol_syslog(__METHOD__ . ' ' . implode(',', $EcmImage->errors), LOG_ERR);
            $db->rollback();
            return;
        }
        $db->commit();
    }
    
    /**
     *  @abstract     Check if A File Already Exists in Ecm Files
     *
     *  @param        string    $FullPath
     *
     *  @return       bool
     */
    private function isExistingEcmFile($FullPath)
    {
        global $db;
        $EcmFile    =   new EcmFiles($db);
        return (bool) $EcmFile->fetch(null, null, $FullPath);
    }
}

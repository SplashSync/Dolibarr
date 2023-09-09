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

namespace   Splash\Local\Core;

use EcmFiles;
use EcmFilesLine;
use Exception;
use Splash\Core\SplashCore      as Splash;
use Splash\Local\Local;

/**
 * Access to Dolibarr Objects Images
 */
trait ImagesTrait
{
    /**
     * @var string
     */
    private string $minVersion = "6.0.0";

    /**
     * @var array<string, string>
     */
    private array $elementPath = array(
        "product" => "produit",
        "commande" => "commande"
    );

    /**
     * @var array
     */
    private array $extensions = array( "gif", "jpg", "jpeg", "png", "bmp" );

    /**
     * @var string
     */
    private string $dolFilesDir;

    /**
     * @var string
     */
    private string $relFilesDir;

    /**
     * @var bool
     */
    private bool $imgUpdated = false;

    /**
     * Build Images FieldFactory
     *
     * @return void
     */
    protected function buildImagesFields(): void
    {
        global $langs;

        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Local::dolVersionCmp($this->minVersion) < 0) {
            return;
        }

        //====================================================================//
        // Load Required Dolibarr Translation Files
        $langs->load("users");

        //====================================================================//
        // Product Images List
        $this->fieldsFactory()->create(SPL_T_IMG)
            ->identifier("image")
            ->inList("images")
            ->name($langs->trans("PhotoFile"))
            ->group("Images")
            ->setPreferWrite()
            ->microData("http://schema.org/Product", "image")
        ;
        //====================================================================//
        // Product Images => Is Cover
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("cover")
            ->inList("images")
            ->name("Cover Image")
            ->setPreferWrite()
            ->microData("http://schema.org/Product", "isCover")
            ->group("Images")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Images => Is Visible
        $this->fieldsFactory()->create(SPL_T_BOOL)
            ->identifier("visible")
            ->inList("images")
            ->name("Visible Image")
            ->setPreferWrite()
            ->microData("http://schema.org/Product", "isVisibleImage")
            ->group("Images")
            ->isNotTested()
        ;
        //====================================================================//
        // Product Images => Position in List
        $this->fieldsFactory()->create(SPL_T_INT)
            ->identifier("position")
            ->inList("images")
            ->name("Position")
            ->setPreferNone()
            ->microData("http://schema.org/Product", "positionImage")
            ->group("Images")
            ->isReadOnly()
        ;
    }

    //====================================================================//
    // Fields Reading Functions
    //====================================================================//

    /**
     * Read requested Field
     *
     * @param string $key       Input List Key
     * @param string $fieldName Field Identifier / Name
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function getImagesFields(string $key, string $fieldName): void
    {
        global $conf;

        //====================================================================//
        // Safety Check
        if (!isset($this->out)) {
            $this->out = array();
        }
        //====================================================================//
        // Check if List field & Init List Array
        $fieldId = self::lists()->initOutput($this->out, "images", $fieldName);
        if (!$fieldId) {
            return;
        }

        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Local::dolVersionCmp($this->minVersion) < 0) {
            return;
        }
        //====================================================================//
        // Load Object Files Path
        $entity = $this->object->entity ?: $conf->entity;
        $element = $this->object->element;
        $this->dolFilesDir = "";
        if ($conf->{$element}->multidir_output ?? false) {
            $this->dolFilesDir = $conf->{$element}->multidir_output[$entity] ?? "";
        }
        //====================================================================//
        // For backward compatibility
        if ('product' == $element && !empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
            $this->relFilesDir = '/'.get_exdir($this->object->id, 2, 0, 0, $this->object, $element);
            $this->relFilesDir .= $this->object->id."/photos/";
            $this->dolFilesDir .= $this->relFilesDir;
        } else {
            $this->dolFilesDir .= '/'.dol_sanitizeFileName($this->object->ref);
            $this->relFilesDir = ($entity > 1) ? $entity."/" : "";
            $this->relFilesDir .= $this->elementPath[$element];
            $this->relFilesDir .= "/".dol_sanitizeFileName($this->object->ref);
        }

        //====================================================================//
        // Fetch Object Attached Images
        $this->getImagesArrayFromDir($fieldName);

        if (isset($this->in[$key])) {
            unset($this->in[$key]);
        }
    }

    /**
     * @param string $fieldName
     *
     * @return void
     */
    protected function getImagesArrayFromEcm($fieldName)
    {
        global $db;

        //====================================================================//
        // Create EcmFiles Main Object
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
        $ecmFiles = new EcmFiles($db);

        //====================================================================//
        // Fetch Object Attached Images
        $filters = array(
            "entity" => $this->object->entity,
            "filepath" => ($this->elementPath[$this->object->element]."/".$this->object->ref)
        );
        $ecmFiles->fetchAll('', '', 0, 0, $filters);
        $this->catchDolibarrErrors();
        if (empty($ecmFiles->lines)) {
            return;
        }

        //====================================================================//
        // Create Images List
        /** @var EcmFilesLine $ecmFileLine */
        foreach ($ecmFiles->lines as $key => $ecmFileLine) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($ecmFileLine->filename, PATHINFO_EXTENSION), $this->extensions, true)) {
                continue;
            }

            //====================================================================//
            // Insert Image in Output List
            $image = self::images()->Encode(
                $ecmFileLine->description,
                $ecmFileLine->filename,
                $ecmFileLine->filepath."/",
                null
            );

            //====================================================================//
            // Insert Data in List
            self::lists()->Insert($this->out, "images", $fieldName, $key, $image);
        }
    }

    //====================================================================//
    // Fields Writing Functions
    //====================================================================//

    /**
     * Write Given Fields
     *
     * @param string     $fieldName Field Identifier / Name
     * @param null|array $fieldData Field Data
     *
     * @return void
     */
    protected function setImagesFields(string $fieldName, ?array $fieldData): void
    {
        //====================================================================//
        // Safety Check
        if ("images" !== $fieldName) {
            return;
        }
        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Local::dolVersionCmp($this->minVersion) < 0) {
            return;
        }

        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

        //====================================================================//
        // Load Current Image Array
        $this->getImagesFields("", "image@images");

        //====================================================================//
        // Verify Images List & Update if Needed
        $position = 1;
        foreach ($fieldData ?? array() as $imageData) {
            //====================================================================//
            // Check if Visible Image
            if (isset($imageData['visible']) && empty($imageData['visible'])) {
                continue;
            }
            //====================================================================//
            // Check if Cover Image
            $isCover = $imageData['cover'] ?? false;
            //====================================================================//
            // Update Item Line
            $this->setImage($position, $imageData['image'], $isCover);
            $position++;
        }

        //====================================================================//
        // Delete Remaining Images
        $this->deleteRemainingImages();

        unset($this->in[$fieldName]);
    }

    /**
     * Update Object Files Path if Ref Changed
     *
     * @param string $element
     * @param string $oldRef
     * @param string $newRef
     *
     * @return void
     */
    protected function updateFilesPath(string $element, string $oldRef, string $newRef)
    {
        global $db;

        //====================================================================//
        // Check if Ref was Changed
        if (empty($element) || empty($oldRef) || empty($newRef) || ($oldRef == $newRef)) {
            return;
        }

        //====================================================================//
        // Ensure Dolibarr Version is Compatible
        if (Local::dolVersionCmp($this->minVersion) < 0) {
            return;
        }

        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

        //====================================================================//
        // Create EcmFiles Main Object
        $ecmImage = new EcmFiles($db);

        //====================================================================//
        // Prepare Update Request
        $sql = $sql = 'UPDATE '.MAIN_DB_PREFIX.$ecmImage->table_element.' SET';
        $sql .= ' filepath = "'.$element.'/'.$newRef.'" ';
        $sql .= ' WHERE filepath="'.$element.'/'.$oldRef.'"';

        //====================================================================//
        // Execute Update Request
        $db->begin();
        $resql = $db->query($sql);
        if (!$resql) {
            Splash::log()->err("ErrLocalTpl", __CLASS__, __FUNCTION__, $db->lasterror());
            $db->rollback();

            return;
        }
        $db->commit();
    }

    /**
     * @param string $fieldName
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getImagesArrayFromDir(string $fieldName)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

        //====================================================================//
        // Refresh Object Attached Images (Manually, OR Ref Changed)
        if (function_exists("completeFileArrayWithDatabaseInfo")) {
            $diskArray = \dol_dir_list($this->dolFilesDir, "files");

            try {
                \completeFileArrayWithDatabaseInfo($diskArray, $this->relFilesDir);
            } catch (Exception $ex) {
                Splash::log()->deb("ErrLocalTpl", __CLASS__, __FUNCTION__, $ex->getMessage());
            }
        }
        //====================================================================//
        // For backward compatibility
        $element = $this->object->element;
        if (isset($diskArray) && ('product' == $element) && !empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
            $fileArray = $diskArray;
        } else {
            //====================================================================//
            // Fetch Object Attached Images from Database
            $fileArray = \dol_dir_list_in_database($this->relFilesDir, "", null, "position");
        }
        //====================================================================//
        // Detect if List has Cover Image or Force it
        $this->detectCoverImage($fileArray);
        //====================================================================//
        // Create Images List
        foreach ($fileArray as $file) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), $this->extensions, true)) {
                continue;
            }
            //====================================================================//
            // File Not Found on Disk
            if (!file_exists($file["fullname"])) {
                continue;
            }
            //====================================================================//
            // Insert Image in Output List
            $image = self::images()->Encode(
                $file["name"],
                $file["name"],
                $file["path"]."/",
                null
            );

            //====================================================================//
            // Insert Data in List
            self::lists()->insert($this->out, "images", $fieldName, $file["position"], $image);
            self::lists()->insert($this->out, "images", "cover", $file["position"], $file["cover"]);
            self::lists()->insert($this->out, "images", "visible", $file["position"], true);
            self::lists()->insert($this->out, "images", "position", $file["position"], $file["position"]);
        }

        //====================================================================//
        // Sort Image Array to Update Images Positions
        if (is_array($this->out["images"])) {
            ksort($this->out["images"]);
        }
    }

    /**
     * Detect if List has Cover Image or Force First Image as Cover
     *
     * @param array $fileArray
     *
     * @return void
     */
    private function detectCoverImage(array &$fileArray): void
    {
        $hasCover = false;
        //====================================================================//
        // Walk on Images List
        foreach ($fileArray as $file) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), $this->extensions, true)) {
                continue;
            }
            //====================================================================//
            // Check if Cover Flag is Set
            if (isset($file["cover"]) && $file["cover"]) {
                $hasCover = true;
            }
        }
        //====================================================================//
        // There is A Cover Image
        if ($hasCover) {
            return;
        }
        //====================================================================//
        // Set First Image as Cover
        foreach ($fileArray as $key => $file) {
            //====================================================================//
            // Filter No Images Files
            if (!in_array(pathinfo($file["name"], PATHINFO_EXTENSION), $this->extensions, true)) {
                continue;
            }
            //====================================================================//
            // Set Image Cover Flag
            $fileArray[$key]["cover"] = true;

            return;
        }
    }

    /**
     * Write Data to Current Image
     *
     * @param int   $position  Input Image Position on List
     * @param array $imageData Input Image Data Array
     * @param bool  $isCover   Input Image is Cover
     *
     * @return bool
     */
    private function setImage(int $position, array $imageData, bool $isCover = false): bool
    {
        global $db;

        $this->imgUpdated = false;

        //====================================================================//
        // Create EcmFiles Main Object
        $ecmImage = new EcmFiles($db);

        //====================================================================//
        // Load Image by CheckSum
        if (empty($imageData["md5"])) {
            return Splash::log()->war("Skipped Image Writing");
        }

        //====================================================================//
        // Check if Image Already Exits
        $this->identifyImage($ecmImage, $imageData);
        //====================================================================//
        // Safety Check
        if (!($ecmImage instanceof EcmFiles)) {
            return Splash::log()->err("Ann Error Occurred on Image Identify");
        }
        //====================================================================//
        // Image New but May Be A Duplicate
        if (empty($ecmImage->id) && $this->isExistingEcmFile($this->relFilesDir."/".$imageData["filename"])) {
            return Splash::log()->deb("Skipped Duplicate Image Writing");
        }

        //====================================================================//
        // Check Image CheckSum
        if ($ecmImage->label != $imageData["md5"]) {
            //====================================================================//
            // Read File from Splash Server
            $newImageFile = Splash::file()->getFile($imageData["file"], $imageData["md5"]);
            //====================================================================//
            // File Imported => Write it Here
            if (!$newImageFile) {
                return false;
            }
            //====================================================================//
            // Write Image On Folder
            Splash::file()->WriteFile(
                $this->dolFilesDir."/",
                $imageData["filename"],
                $newImageFile["md5"],
                $newImageFile["raw"]
            );
            $this->imgUpdated = true;
        }

        //====================================================================//
        // Update Image Data
        $this->setEcmFileData($ecmImage, $position, $imageData, $isCover);

        //====================================================================//
        // Image Not Updated
        if (!$this->imgUpdated) {
            return true;
        }

        return $this->saveEcmFile($ecmImage, $position);
    }

    /**
     * Delete Unexpected Images
     *
     * @return void
     */
    private function deleteRemainingImages(): void
    {
        global $db, $user;

        //====================================================================//
        // Safety Check
        if (!is_array($this->out["images"])) {
            return;
        }
        //====================================================================//
        // Walk on Remaining Images
        /**
         * @var string $key
         * @var array  $image
         */
        foreach ($this->out["images"] as $key => $image) {
            $ecmImage = new EcmFiles($db);
            $ecmImage->fetch(0, '', $this->relFilesDir."/".$image["image"]["filename"]);
            //====================================================================//
            // Delete Object In Database
            if (!empty($ecmImage->label) && ($ecmImage->delete($user) <= 0)) {
                $this->catchDolibarrErrors($ecmImage);
                Splash::log()->errTrace("Unable to Delete Image File. ");
            }
            //====================================================================//
            // Delete Object From Disk
            Splash::file()->DeleteFile($this->dolFilesDir."/".$image["image"]["filename"], $image["image"]["md5"]);
            unset($this->out["images"][$key]);
        }
    }

    /**
     * Search for this Image in Current DataSet & Fetch EcmFile if Found
     *
     * @param EcmFiles $ecmImage  Dolibarr File Object
     * @param array    $imageData Input Image Data Array
     *
     * @return void
     */
    private function identifyImage(EcmFiles &$ecmImage, array $imageData): void
    {
        //====================================================================//
        // Safety Check
        if (!is_array($this->out["images"])) {
            return;
        }
        //====================================================================//
        // Walk on Current Images
        /**
         * @var string $key
         * @var array  $currentImage
         */
        foreach ($this->out["images"] as $key => $currentImage) {
            if (($currentImage["image"]["md5"] === $imageData["md5"])
                && ($currentImage["image"]["filename"] === $imageData["filename"])) {
                $ecmImage->fetch(0, '', $this->relFilesDir."/".$currentImage["image"]["filename"]);
                unset($this->out["images"][$key]);

                break;
            }
        }
    }

    /**
     * Write Data to Current Image
     *
     * @param EcmFiles $ecmImage  Dolibarr File Object
     * @param int      $position  Input Image Position on List
     * @param array    $imageData Input Image Data Array
     * @param bool     $isCover   Input Image is Cover
     *
     * @return void
     */
    private function setEcmFileData(EcmFiles $ecmImage, int $position, array $imageData, bool $isCover): void
    {
        global $user, $conf;

        //====================================================================//
        // Image Entity Is New
        if (empty($ecmImage->id)) {
            $ecmImage->label = $imageData["md5"];
            $ecmImage->filepath = $this->relFilesDir;
            $ecmImage->filename = $imageData["filename"];
            $ecmImage->fullpath_orig = $ecmImage->filepath;
            $ecmImage->fk_user_c = $user->id;
            $ecmImage->entity = $this->object->entity ? $this->object->entity : $conf->entity;
            $ecmImage->gen_or_uploaded = "uploaded";
            $this->imgUpdated = true;
        }

        //====================================================================//
        // Check Image Filename
        if ($ecmImage->position != $position) {
            $ecmImage->position = $position;
            $this->imgUpdated = true;
        }

        //====================================================================//
        // Check Image Cover Flag
        if ($ecmImage->cover != $isCover) {
            $ecmImage->cover = $isCover ? "1" : "";
            $this->imgUpdated = true;
        }
    }

    /**
     * Save EcmImage to DataBase
     *
     * @param EcmFiles $ecmImage Dolibarr File Object
     * @param int      $position Input Image Position on List
     *
     * @return bool
     */
    private function saveEcmFile(EcmFiles $ecmImage, int $position): bool
    {
        global $user;

        if (empty($ecmImage->id)) {
            //====================================================================//
            // Search for Already Created/Updated Image In Database
            if ($this->isExistingEcmFile($ecmImage->filepath."/".$ecmImage->filename)) {
                return true;
            }
            //====================================================================//
            // Create Object In Database
            if ($ecmImage->create($user) <= 0) {
                $this->catchDolibarrErrors($ecmImage);

                return Splash::log()->errTrace("Unable to create new Image File. ");
            }
            $ecmImage->position = $position;
        }
        //====================================================================//
        // Update Object In Database
        if ($ecmImage->update($user) <= 0) {
            $this->catchDolibarrErrors($ecmImage);

            return Splash::log()->errTrace("Unable to update Image File. ");
        }

        //====================================================================//
        // Update Object Images Thumbs
        $this->object->addThumbs($this->dolFilesDir."/".$ecmImage->filename);

        return true;
    }

    /**
     * Check if A File Already Exists in Ecm Files
     *
     * @param string $fullPath
     *
     * @return bool
     */
    private function isExistingEcmFile(string $fullPath): bool
    {
        global $db, $user;
        $ecmFile = new EcmFiles($db);
        $ecmFile->fetch(0, '', $fullPath);
        //====================================================================//
        // File Not Found on Database
        if (empty($ecmFile->id)) {
            return false;
        }
        //====================================================================//
        // File Not Found on Disk
        if (!file_exists($this->dolFilesDir."/".$ecmFile->filename)) {
            Splash::log()->war("Deleted Not Found Emc File: ".$this->dolFilesDir."/".$ecmFile->filename);
            $ecmFile->delete($user);

            return false;
        }

        return true;
    }
}

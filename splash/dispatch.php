<?php

function copyr($source, $dest)
{
    //====================================================================//
    // Simple copy for a file
    if (is_file($source)) {
      return copy($source, $dest);
    }

    //====================================================================//
    // Make destination directory
    if (!is_dir($dest)) {
       mkdir($dest);
    }

    //====================================================================//
    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
       // Skip pointers
       if ($entry == '.' || $entry == '..') {
          continue;
       }

       // Deep copy directories
       if ($dest !== "$source/$entry") {
          copyr("$source/$entry", "$dest/$entry");
       }
    }

    //====================================================================//
    // Clean up
    $dir->close();
    return true;
}

copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Dol-3.5/splash");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Dol-3.6/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Dol-3.7/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Dol-3.8/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Dol-3.9/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/LP-Addict/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/var/www/Dolibarr/Develop/htdocs/splash/");
copyr("/home/nanard33/WebDesign/MOD-Splash/Dolibarr/splash/","/home/nanard33/WebDesign/Dolibarr/Develop/htdocs/splash/");
  



?>